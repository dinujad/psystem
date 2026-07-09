<?php

namespace App\Services;

use App\Jobs\SendWhatsappReplyJob;
use App\WhatsappConversation;
use App\WhatsappConversationLog;
use App\WhatsappFlow;
use App\WhatsappFlowStep;
use Illuminate\Support\Facades\Log;

/**
 * Stateful WhatsApp bot automation engine.
 *
 * Entry point: handleIncomingMessage($phoneNumber, $messageText)
 *
 * Responsibilities:
 *  - Track a live session per contact (whatsapp_conversations).
 *  - Match incoming text to flow trigger keywords (when idle) or to the
 *    current step's logic (when a flow is active).
 *  - Render step messages (with {{variable}} substitution + numbered menus).
 *  - Queue outbound replies through SendWhatsappReplyJob (cooldown + async).
 *  - Log every inbound/outbound message into whatsapp_conversation_logs.
 *
 * The Node service requires no changes — it keeps forwarding inbound messages
 * and sending whatever Laravel hands to WhatsappService::sendMessage().
 */
class WhatsappBotEngine
{
    public function handleIncomingMessage(string $phoneNumber, ?string $messageText): void
    {
        $phoneNumber = preg_replace('/\D/', '', $phoneNumber);
        $messageText = trim((string) $messageText);

        if ($phoneNumber === '') {
            return;
        }

        $conversation = $this->findOrCreateConversation($phoneNumber);

        // Always record the inbound message + refresh activity timestamp.
        $this->logMessage($conversation, 'in', $messageText, $conversation->current_step_key);
        $conversation->last_interaction_at = now();
        $conversation->save();

        // A human agent is handling this contact — bot stays silent.
        if ($conversation->isHumanTakeover()) {
            return;
        }

        // Empty inbound (e.g. media-only) with no active step: nothing to match on.
        if ($messageText === '' && ! $conversation->hasActiveStep()) {
            return;
        }

        try {
            if ($conversation->hasActiveStep()) {
                $this->advanceActiveStep($conversation, $messageText);
            } else {
                $this->startFromKeyword($conversation, $messageText);
            }
        } catch (\Throwable $e) {
            Log::error('WhatsappBotEngine error: '.$e->getMessage(), [
                'phone' => $phoneNumber,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    //  New / idle conversation → match a trigger keyword or fall back
    // ─────────────────────────────────────────────────────────────────────
    protected function startFromKeyword(WhatsappConversation $conversation, string $messageText): void
    {
        $needle = mb_strtolower(trim($messageText));

        $flow = $this->matchFlowByKeyword($needle);

        if (! $flow) {
            $flow = WhatsappFlow::where('is_active', true)
                ->where('is_default_fallback', true)
                ->first();
        }

        if (! $flow) {
            // No matching flow and no fallback configured — stay idle silently.
            return;
        }

        $firstStep = $flow->firstStep();
        if (! $firstStep) {
            Log::warning("WhatsApp flow #{$flow->id} has no steps; cannot start.");
            return;
        }

        $conversation->current_flow_id  = $flow->id;
        $conversation->status           = WhatsappConversation::STATUS_BOT_ACTIVE;
        $conversation->save();

        $this->enterStep($conversation, $flow, $firstStep);
    }

    protected function matchFlowByKeyword(string $needle): ?WhatsappFlow
    {
        if ($needle === '') {
            return null;
        }

        $flows = WhatsappFlow::where('is_active', true)
            ->where('is_default_fallback', false)
            ->get();

        foreach ($flows as $flow) {
            foreach ((array) $flow->trigger_keywords as $keyword) {
                if (mb_strtolower(trim((string) $keyword)) === $needle) {
                    return $flow;
                }
            }
        }

        return null;
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Active step → interpret the reply based on step_type
    // ─────────────────────────────────────────────────────────────────────
    protected function advanceActiveStep(WhatsappConversation $conversation, string $messageText): void
    {
        $flow = $conversation->currentFlow;
        if (! $flow) {
            $conversation->resetSession();
            return;
        }

        $step = $flow->stepByKey($conversation->current_step_key);
        if (! $step) {
            // Step disappeared (flow edited) — reset so next message starts fresh.
            $conversation->resetSession();
            return;
        }

        switch ($step->step_type) {
            case 'menu':
                $this->handleMenuStep($conversation, $flow, $step, $messageText);
                break;

            case 'text_input':
                $this->handleTextInputStep($conversation, $flow, $step, $messageText);
                break;

            case 'final':
            default:
                // A "final" step shouldn't normally be the current step (we end the
                // session when entering one). If we somehow get here, reset.
                $conversation->resetSession(WhatsappConversation::STATUS_COMPLETED);
                break;
        }
    }

    protected function handleMenuStep(WhatsappConversation $conversation, WhatsappFlow $flow, WhatsappFlowStep $step, string $messageText): void
    {
        $choice = mb_strtolower(trim($messageText));
        $matched = null;

        foreach ((array) $step->options as $option) {
            $match = mb_strtolower(trim((string) ($option['match'] ?? '')));
            if ($match !== '' && $match === $choice) {
                $matched = $option;
                break;
            }
        }

        if (! $matched) {
            // Invalid option — re-send the same menu with an apologetic prefix.
            $this->dispatchReply(
                $conversation,
                "Sorry, that's not a valid option.\n\n".$this->renderStepMessage($step, $conversation),
                $step->step_key
            );
            return;
        }

        $nextStep = $flow->stepByKey($matched['next_step_key'] ?? null);
        if (! $nextStep) {
            // Option leads nowhere — end the session gracefully.
            $conversation->resetSession(WhatsappConversation::STATUS_COMPLETED);
            return;
        }

        $this->enterStep($conversation, $flow, $nextStep);
    }

    protected function handleTextInputStep(WhatsappConversation $conversation, WhatsappFlow $flow, WhatsappFlowStep $step, string $messageText): void
    {
        // Persist the raw reply under the configured variable name.
        if (! empty($step->save_input_as)) {
            $variables = (array) $conversation->variables;
            $variables[$step->save_input_as] = $messageText;
            $conversation->variables = $variables;
            $conversation->save();
        }

        $nextStep = $flow->stepByKey($step->next_step_key);
        if (! $nextStep) {
            // No next step — flow ends here.
            $conversation->resetSession(WhatsappConversation::STATUS_COMPLETED);
            return;
        }

        $this->enterStep($conversation, $flow, $nextStep);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Enter a step: set it current (unless final), render + queue its message
    // ─────────────────────────────────────────────────────────────────────
    protected function enterStep(WhatsappConversation $conversation, WhatsappFlow $flow, WhatsappFlowStep $step): void
    {
        // A step can hand the conversation off to a human agent.
        if ($step->triggers_human_takeover) {
            $conversation->status = WhatsappConversation::STATUS_HUMAN_TAKEOVER;
            $conversation->current_step_key = null;
            $conversation->save();

            $this->dispatchReply($conversation, $this->renderStepMessage($step, $conversation), $step->step_key);
            return;
        }

        if ($step->step_type === 'final') {
            // Send the final message, then close out the session.
            $this->dispatchReply($conversation, $this->renderStepMessage($step, $conversation), $step->step_key);
            $conversation->resetSession(WhatsappConversation::STATUS_COMPLETED);
            return;
        }

        // menu / text_input: park here and wait for the next reply.
        $conversation->current_step_key = $step->step_key;
        $conversation->status           = WhatsappConversation::STATUS_BOT_ACTIVE;
        $conversation->save();

        $this->dispatchReply($conversation, $this->renderStepMessage($step, $conversation), $step->step_key);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Rendering
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Render a step's outbound text: substitute {{variable}} placeholders and,
     * for menu steps, append the numbered option list (Baileys is text-only).
     */
    public function renderStepMessage(WhatsappFlowStep $step, WhatsappConversation $conversation): string
    {
        $message = $this->substituteVariables($step->message_text, (array) $conversation->variables);

        if ($step->step_type === 'menu' && ! empty($step->options)) {
            $lines = [];
            foreach ((array) $step->options as $option) {
                $label = trim((string) ($option['label'] ?? ''));
                if ($label !== '') {
                    $lines[] = $label;
                }
            }
            if ($lines) {
                $message = rtrim($message)."\n\n".implode("\n", $lines);
            }
        }

        return $message;
    }

    protected function substituteVariables(string $text, array $variables): string
    {
        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', function ($m) use ($variables) {
            return array_key_exists($m[1], $variables) ? (string) $variables[$m[1]] : '';
        }, $text);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Helpers
    // ─────────────────────────────────────────────────────────────────────
    protected function findOrCreateConversation(string $phoneNumber): WhatsappConversation
    {
        return WhatsappConversation::firstOrCreate(
            ['phone_number' => $phoneNumber],
            [
                'status'              => WhatsappConversation::STATUS_IDLE,
                'variables'           => [],
                'last_interaction_at' => now(),
            ]
        );
    }

    /**
     * Queue an outbound reply. The job enforces a per-contact cooldown and the
     * actual WhatsApp send + outbound logging, so the webhook returns fast.
     */
    protected function dispatchReply(WhatsappConversation $conversation, string $message, ?string $stepKey): void
    {
        if (trim($message) === '') {
            return;
        }

        SendWhatsappReplyJob::dispatch(
            $conversation->phone_number,
            $message,
            $conversation->id,
            $stepKey
        );
    }

    public function logMessage(WhatsappConversation $conversation, string $direction, string $message, ?string $stepKey = null): void
    {
        WhatsappConversationLog::create([
            'conversation_id' => $conversation->id,
            'direction'       => $direction,
            'message'         => $message,
            'step_key'        => $stepKey,
            'created_at'      => now(),
        ]);
    }
}

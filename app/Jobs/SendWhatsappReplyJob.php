<?php

namespace App\Jobs;

use App\Services\WhatsappService;
use App\WhatsappConversation;
use App\WhatsappConversationLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Sends a single outbound WhatsApp bot reply.
 *
 * Why a job?
 *  - The webhook can return 200 immediately and offload the send.
 *  - We enforce a per-contact cooldown (max 1 message / second) so the linked
 *    WhatsApp account is never flagged for spamming.
 *
 * NOTE: For true asynchronous processing set QUEUE_CONNECTION to "database" or
 * "redis" and run `php artisan queue:work`. With the default "sync" driver the
 * job runs inline (still correct, just not offloaded).
 */
class SendWhatsappReplyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Minimum gap between two messages to the same contact (milliseconds). */
    protected const COOLDOWN_MS = 1000;

    public int $tries = 3;
    public int $backoff = 5;

    public function __construct(
        public string $phoneNumber,
        public string $message,
        public ?int $conversationId = null,
        public ?string $stepKey = null
    ) {
    }

    public function handle(WhatsappService $whatsappService): void
    {
        $this->respectCooldown();

        $result = $whatsappService->sendMessage($this->phoneNumber, $this->message);

        // Remember when we last sent to this contact for the next cooldown check.
        Cache::put($this->cooldownKey(), now()->valueOf(), now()->addMinutes(5));

        // Log the outbound reply against the bot conversation thread.
        if ($this->conversationId) {
            WhatsappConversationLog::create([
                'conversation_id' => $this->conversationId,
                'direction'       => 'out',
                'message'         => $this->message,
                'step_key'        => $this->stepKey,
                'created_at'      => now(),
            ]);
        }

        if (empty($result['success'])) {
            Log::warning('SendWhatsappReplyJob: send failed', [
                'phone'  => $this->phoneNumber,
                'result' => $result,
            ]);
        }
    }

    /**
     * Block until at least COOLDOWN_MS has elapsed since the last send to this
     * contact. Capped at COOLDOWN_MS so we never sleep longer than one window.
     */
    protected function respectCooldown(): void
    {
        $last = Cache::get($this->cooldownKey());
        if (! $last) {
            return;
        }

        $elapsed = now()->valueOf() - (int) $last;
        $wait    = self::COOLDOWN_MS - $elapsed;

        if ($wait > 0) {
            usleep(min($wait, self::COOLDOWN_MS) * 1000);
        }
    }

    protected function cooldownKey(): string
    {
        return 'wa_bot_cooldown_'.$this->phoneNumber;
    }
}

<?php

namespace App\Http\Controllers;

use App\Jobs\SendWhatsappReplyJob;
use App\WhatsappConversation;
use Illuminate\Http\Request;

class WhatsappBotController extends Controller
{
    protected function authorizeAccess(): void
    {
        if (! auth()->user()->can('send_notifications')) {
            abort(403, 'Unauthorized action.');
        }
    }

    // ── Live conversations inbox ────────────────────────────────────────
    public function index()
    {
        $this->authorizeAccess();

        $conversations = $this->conversationList();

        return view('whatsapp.bot.conversations', compact('conversations'));
    }

    public function poll()
    {
        $this->authorizeAccess();

        return response()->json(['conversations' => $this->conversationList()]);
    }

    protected function conversationList()
    {
        return WhatsappConversation::query()
            ->orderByDesc('last_interaction_at')
            ->orderByDesc('id')
            ->limit(200)
            ->get()
            ->map(function (WhatsappConversation $c) {
                $last = $c->logs()->latest('id')->first();

                return [
                    'id'                  => $c->id,
                    'phone_number'        => $c->phone_number,
                    'status'              => $c->status,
                    'last_interaction_at' => optional($c->last_interaction_at)->format('Y-m-d H:i:s'),
                    'last_message'        => $last ? \Illuminate\Support\Str::limit($last->message, 40) : '',
                    'last_direction'      => $last->direction ?? null,
                ];
            });
    }

    // ── Single conversation thread ──────────────────────────────────────
    public function show(WhatsappConversation $conversation)
    {
        $this->authorizeAccess();

        $logs = $conversation->logs()->orderBy('id')->get();

        return view('whatsapp.bot.thread', compact('conversation', 'logs'));
    }

    public function pollThread(Request $request, WhatsappConversation $conversation)
    {
        $this->authorizeAccess();

        $after = (int) $request->query('after', 0);

        $logs = $conversation->logs()
            ->when($after, fn ($q) => $q->where('id', '>', $after))
            ->orderBy('id')
            ->get()
            ->map(fn ($l) => [
                'id'         => $l->id,
                'direction'  => $l->direction,
                'message'    => $l->message,
                'step_key'   => $l->step_key,
                'created_at' => optional($l->created_at)->format('Y-m-d H:i:s'),
            ]);

        return response()->json([
            'logs'   => $logs,
            'status' => $conversation->fresh()->status,
        ]);
    }

    // ── Manual reply (agent takeover) ───────────────────────────────────
    public function reply(Request $request, WhatsappConversation $conversation)
    {
        $this->authorizeAccess();

        $request->validate([
            'message' => ['required', 'string', 'max:4096'],
        ]);

        // Sending a manual reply means a human is now handling this contact;
        // flip to human_takeover so the bot stops auto-responding.
        $conversation->status           = WhatsappConversation::STATUS_HUMAN_TAKEOVER;
        $conversation->current_step_key = null;
        $conversation->last_interaction_at = now();
        $conversation->save();

        // Reuse the same cooldown-protected send + conversation logging path.
        SendWhatsappReplyJob::dispatch(
            $conversation->phone_number,
            $request->input('message'),
            $conversation->id,
            null
        );

        return response()->json(['success' => true, 'status' => $conversation->status]);
    }

    public function returnToBot(WhatsappConversation $conversation)
    {
        $this->authorizeAccess();

        // Hand control back to the bot — next inbound message starts fresh.
        $conversation->resetSession(WhatsappConversation::STATUS_IDLE);

        return response()->json(['success' => true, 'status' => $conversation->status]);
    }
}

<?php

namespace App\Console\Commands;

use App\WhatsappConversation;
use Illuminate\Console\Command;

/**
 * Resets stale bot sessions so a returning contact starts a fresh flow.
 *
 * Any conversation still in "bot_active" whose last interaction is older than
 * the inactivity window is moved back to "idle" (flow/step cleared). Collected
 * variables are intentionally preserved for reference.
 */
class WhatsappResetIdleSessions extends Command
{
    protected $signature = 'whatsapp:reset-idle-sessions {--minutes=30 : Inactivity threshold in minutes}';

    protected $description = 'Reset bot_active WhatsApp conversations idle beyond the threshold back to idle.';

    public function handle(): int
    {
        $minutes   = (int) $this->option('minutes');
        $threshold = now()->subMinutes($minutes);

        $stale = WhatsappConversation::where('status', WhatsappConversation::STATUS_BOT_ACTIVE)
            ->where(function ($q) use ($threshold) {
                $q->whereNull('last_interaction_at')
                  ->orWhere('last_interaction_at', '<', $threshold);
            })
            ->get();

        $count = 0;
        foreach ($stale as $conversation) {
            $conversation->resetSession(WhatsappConversation::STATUS_IDLE);
            $count++;
        }

        $this->info("Reset {$count} idle WhatsApp bot session(s) (threshold: {$minutes} min).");

        return self::SUCCESS;
    }
}

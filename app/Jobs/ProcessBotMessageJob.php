<?php

namespace App\Jobs;

use App\Services\WhatsappBotEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Runs the bot automation engine for one inbound message off the webhook
 * request cycle, so the Node service's webhook call returns a fast 200 OK.
 *
 * Set QUEUE_CONNECTION=database (or redis) + run `php artisan queue:work` for
 * true async processing. With the default "sync" driver it runs inline.
 */
class ProcessBotMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(
        public string $phoneNumber,
        public ?string $messageText
    ) {
    }

    public function handle(WhatsappBotEngine $engine): void
    {
        $engine->handleIncomingMessage($this->phoneNumber, $this->messageText);
    }
}

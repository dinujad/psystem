<?php

namespace App\Console\Commands;

use App\Services\WhatsappLidResolver;
use Illuminate\Console\Command;

class MergeWhatsappLids extends Command
{
    protected $signature = 'whatsapp:merge-lids';

    protected $description = 'Merge WhatsApp LID numbers to real phone numbers using lid_map.json';

    public function handle(): int
    {
        $this->info('Merging LID phone numbers from lid_map.json...');
        $count = WhatsappLidResolver::mergeAllFromMap();
        $this->info("Done. {$count} message row(s) updated.");

        return self::SUCCESS;
    }
}

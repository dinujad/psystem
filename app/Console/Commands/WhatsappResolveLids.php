<?php

namespace App\Console\Commands;

use App\WhatsappMessage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class WhatsappResolveLids extends Command
{
    protected $signature = 'whatsapp:resolve-lids
                            {--dry-run : Show what would be updated without making changes}';

    protected $description = 'Resolve WhatsApp LID identifiers to real phone numbers in the database';

    public function handle(): int
    {
        $serviceUrl = rtrim(config('services.whatsapp.url'), '/');
        $apiKey     = config('services.whatsapp.api_key');

        if (! $serviceUrl || ! $apiKey) {
            $this->error('WHATSAPP_SERVICE_URL / WHATSAPP_API_KEY not configured.');
            return 1;
        }

        // Fetch all distinct phone_numbers that look like LIDs (>12 digits, uncommon for real phones)
        // Real international numbers: 8–12 digits. LIDs tend to be 15 digits.
        $threads = WhatsappMessage::selectRaw('DISTINCT phone_number')
            ->whereRaw('LENGTH(phone_number) >= 13')  // LIDs are typically 15 digits
            ->pluck('phone_number')
            ->toArray();

        if (empty($threads)) {
            $this->info('No LID-like phone numbers found in the database.');
            return 0;
        }

        $this->info('Found ' . count($threads) . ' LID-like phone number(s): ' . implode(', ', $threads));
        $this->line('');
        $this->info('To resolve LIDs we need to know the real phone numbers.');
        $this->line('Please enter the real phone number for each LID below.');
        $this->line('Press ENTER to skip a LID.');
        $this->line('');

        $updates = [];

        foreach ($threads as $lid) {
            $real = $this->ask("Real phone number for LID [{$lid}] (e.g. 94763237574)", null);
            if ($real) {
                $real = preg_replace('/\D/', '', $real);
                if (strlen($real) >= 8) {
                    $updates[$lid] = $real;
                    $this->line("  → Will update [{$lid}] → [{$real}]");
                } else {
                    $this->warn("  → Skipped (too short)");
                }
            } else {
                $this->line('  → Skipped');
            }
        }

        if (empty($updates)) {
            $this->info('Nothing to update.');
            return 0;
        }

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN — no changes made.');
            return 0;
        }

        foreach ($updates as $lid => $real) {
            $count = WhatsappMessage::where('phone_number', $lid)->count();
            WhatsappMessage::where('phone_number', $lid)->update(['phone_number' => $real]);
            $this->info("Updated {$count} message(s): {$lid} → {$real}");
        }

        // Also update lid_map.json in the Node.js service directory
        $lidMapPath = base_path('../whatsapp-service/lid_map.json');
        if (file_exists($lidMapPath)) {
            $map = json_decode(file_get_contents($lidMapPath), true) ?: [];
        } else {
            $map = [];
        }

        foreach ($updates as $lid => $real) {
            $map[$lid . '@lid'] = $real . '@s.whatsapp.net';
        }

        file_put_contents($lidMapPath, json_encode($map, JSON_PRETTY_PRINT));
        $this->info('LID map JSON updated at ' . $lidMapPath);

        $this->info('');
        $this->info('Done. Refresh the WhatsApp inbox to see changes.');
        return 0;
    }
}

<?php

namespace App\Console\Commands;

use App\Utils\CreditReportImporter;
use Illuminate\Console\Command;

class ClearCustomerBalances extends Command
{
    protected $signature = 'credit:clear-balances';

    protected $description = 'Clear all customer opening balances and imported credit data';

    public function handle(): int
    {
        try {
            $result = (new CreditReportImporter())->clearAllCustomerBalances();

            $this->table(
                ['Item', 'Count'],
                [
                    ['Removed imported transactions', $result['removed_imported_transactions']],
                    ['Removed opening balances', $result['removed_opening_balances']],
                    ['Contacts balance reset to 0', $result['contacts_reset']],
                ]
            );

            $this->info('All customer opening balances cleared.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Clear failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}

<?php

namespace App\Console\Commands;

use App\Utils\CreditReportImporter;
use Illuminate\Console\Command;

class ReimportCreditReport extends Command
{
    protected $signature = 'credit:reimport';

    protected $description = 'Re-import all credit_report2 customers and previous balances';

    public function handle(): int
    {
        if (! $this->confirm('This will remove imported credit invoices and re-import all customer details. Continue?', true)) {
            $this->info('Cancelled.');

            return self::SUCCESS;
        }

        try {
            $result = (new CreditReportImporter())->reimportAll();

            $this->table(
                ['Item', 'Count'],
                [
                    ['Removed old imported invoices', $result['removed_transactions']],
                    ['Customers created', $result['customers_created']],
                    ['Customers updated', $result['customers_updated']],
                    ['Opening balances imported', $result['opening_balances_imported']],
                    ['Opening balances skipped', $result['opening_balances_skipped']],
                ]
            );

            $this->info('Credit report re-import completed successfully.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Re-import failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}

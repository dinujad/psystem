<?php

namespace App\Utils;

use App\Utils\TransactionUtil;
use Illuminate\Support\Facades\DB;

class CreditReportImporter
{
    public const IMPORT_NOTE = 'Imported from credit_report2';

    public const LEGACY_IMPORT_NOTE = 'Imported from credit_report';

    public const SOURCE_TABLE = 'credit_report2';

    public function reimportAll(): array
    {
        $business = DB::table('business')->first();

        if (! $business) {
            throw new \RuntimeException('Business not found.');
        }

        $location = DB::table('business_locations')->where('business_id', $business->id)->first();

        if (! $location) {
            throw new \RuntimeException('Business location not found.');
        }

        if (! DB::getSchemaBuilder()->hasTable(self::SOURCE_TABLE)) {
            throw new \RuntimeException('credit_report2 table not found. Run migrations first.');
        }

        $business_id = (int) $business->id;
        $created_by = (int) $business->owner_id;
        $location_id = (int) $location->id;

        return DB::transaction(function () use ($business_id, $created_by, $location_id) {
            $removedTransactions = $this->removeImportedTransactions();
            $customerStats = $this->syncAllCustomers($business_id, $created_by);
            $balanceStats = $this->importOpeningBalances($business_id, $created_by);

            return [
                'removed_transactions' => $removedTransactions,
                'customers_created' => $customerStats['created'],
                'customers_updated' => $customerStats['updated'],
                'opening_balances_imported' => $balanceStats['imported'],
                'opening_balances_skipped' => $balanceStats['skipped'],
            ];
        });
    }

    public function clearAllCustomerBalances(): array
    {
        $business = DB::table('business')->first();

        if (! $business) {
            throw new \RuntimeException('Business not found.');
        }

        $business_id = (int) $business->id;

        return DB::transaction(function () use ($business_id) {
            $removedImported = $this->removeImportedTransactions();

            $openingBalanceIds = DB::table('transactions')
                ->where('business_id', $business_id)
                ->where('type', 'opening_balance')
                ->pluck('id');

            $removedOpeningBalances = 0;

            if ($openingBalanceIds->isNotEmpty()) {
                DB::table('transaction_payments')->whereIn('transaction_id', $openingBalanceIds)->delete();
                $removedOpeningBalances = DB::table('transactions')->whereIn('id', $openingBalanceIds)->delete();
            }

            $contactsReset = DB::table('contacts')
                ->where('business_id', $business_id)
                ->whereIn('type', ['customer', 'both'])
                ->update([
                    'balance' => 0,
                    'updated_at' => now(),
                ]);

            return [
                'removed_imported_transactions' => $removedImported,
                'removed_opening_balances' => $removedOpeningBalances,
                'contacts_reset' => $contactsReset,
            ];
        });
    }

    public function removeImportedTransactions(): int
    {
        $transactionIds = DB::table('transactions')
            ->whereIn('additional_notes', [self::IMPORT_NOTE, self::LEGACY_IMPORT_NOTE])
            ->pluck('id');

        if ($transactionIds->isEmpty()) {
            return 0;
        }

        DB::table('transaction_payments')->whereIn('transaction_id', $transactionIds)->delete();
        DB::table('transactions')->whereIn('id', $transactionIds)->delete();

        return $transactionIds->count();
    }

    public function syncAllCustomers(int $business_id, int $created_by): array
    {
        $created = 0;
        $updated = 0;

        $customers = DB::table(self::SOURCE_TABLE)
            ->select(
                'customer_name',
                DB::raw('MAX(CASE WHEN tp IS NOT NULL AND tp != "" AND tp != "0" THEN tp END) as tp')
            )
            ->groupBy('customer_name')
            ->orderBy('customer_name')
            ->get();

        foreach ($customers as $customer) {
            $name = trim($customer->customer_name);
            $mobile = $this->normalizeMobile($customer->tp);

            $contact = DB::table('contacts')
                ->where('business_id', $business_id)
                ->whereIn('type', ['customer', 'both'])
                ->whereNull('deleted_at')
                ->whereRaw('UPPER(TRIM(name)) = ?', [strtoupper($name)])
                ->orderBy('id')
                ->first();

            if ($contact) {
                DB::table('contacts')
                    ->where('id', $contact->id)
                    ->update([
                        'name' => $name,
                        'first_name' => $name,
                        'mobile' => $mobile ?? $contact->mobile,
                        'contact_status' => 'active',
                        'updated_at' => now(),
                    ]);
                $updated++;

                continue;
            }

            DB::table('contacts')->insert([
                'business_id' => $business_id,
                'type' => 'customer',
                'name' => $name,
                'first_name' => $name,
                'mobile' => $mobile ?? '',
                'contact_id' => $this->generateContactReference($business_id),
                'created_by' => $created_by,
                'is_default' => 0,
                'contact_status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $created++;
        }

        return compact('created', 'updated');
    }

    public function importOpeningBalances(int $business_id, int $created_by): array
    {
        $transactionUtil = new TransactionUtil();
        $imported = 0;
        $skipped = 0;

        $records = DB::table(self::SOURCE_TABLE)->orderBy('date')->orderBy('invoice_no')->get();

        foreach ($records as $record) {
            $opening_balance = (float) $record->bill_amount;

            if ($opening_balance <= 0) {
                $skipped++;

                continue;
            }

            $contact = $this->findContactByName($business_id, $record->customer_name);

            if (! $contact) {
                $skipped++;

                continue;
            }

            $invoice_no = (string) $record->invoice_no;
            $transaction_date = $record->date.' 12:00:00';

            $alreadyImported = DB::table('transactions')
                ->where('business_id', $business_id)
                ->where('contact_id', $contact->id)
                ->where('type', 'opening_balance')
                ->where('additional_notes', self::IMPORT_NOTE)
                ->where('staff_note', 'Invoice: '.$invoice_no)
                ->exists();

            if ($alreadyImported) {
                $skipped++;

                continue;
            }

            $transactionUtil->createOpeningBalanceTransaction(
                $business_id,
                $contact->id,
                $opening_balance,
                $created_by,
                false
            );

            DB::table('transactions')
                ->where('business_id', $business_id)
                ->where('contact_id', $contact->id)
                ->where('type', 'opening_balance')
                ->orderByDesc('id')
                ->limit(1)
                ->update([
                    'transaction_date' => $transaction_date,
                    'additional_notes' => self::IMPORT_NOTE,
                    'staff_note' => 'Invoice: '.$invoice_no,
                    'source' => 'credit_report2',
                    'updated_at' => now(),
                ]);

            $imported++;
        }

        return compact('imported', 'skipped');
    }

    private function findContactByName(int $business_id, string $customer_name): ?object
    {
        $name = trim($customer_name);

        return DB::table('contacts')
            ->where('business_id', $business_id)
            ->whereIn('type', ['customer', 'both'])
            ->whereNull('deleted_at')
            ->whereRaw('UPPER(TRIM(name)) = ?', [strtoupper($name)])
            ->orderBy('id')
            ->first();
    }

    private function normalizeMobile(?string $mobile): ?string
    {
        if ($mobile === null) {
            return null;
        }

        $mobile = trim($mobile);

        if ($mobile === '' || $mobile === '0') {
            return null;
        }

        return $mobile;
    }

    private function generateContactReference(int $business_id): string
    {
        $ref = DB::table('reference_counts')
            ->where('business_id', $business_id)
            ->where('ref_type', 'contacts')
            ->first();

        if ($ref) {
            $count = $ref->ref_count + 1;
            DB::table('reference_counts')
                ->where('id', $ref->id)
                ->update([
                    'ref_count' => $count,
                    'updated_at' => now(),
                ]);
        } else {
            $count = 1;
            DB::table('reference_counts')->insert([
                'ref_type' => 'contacts',
                'business_id' => $business_id,
                'ref_count' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $business = DB::table('business')->where('id', $business_id)->first();
        $prefixes = json_decode($business->ref_no_prefixes, true);
        $prefix = $prefixes['contacts'] ?? 'CO';

        return $prefix.str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }
}

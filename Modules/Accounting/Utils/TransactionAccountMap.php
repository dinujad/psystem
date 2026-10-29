<?php

namespace Modules\Accounting\Utils;

use App\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Entities\ChartOfAccount;
use Modules\Accounting\Entities\JournalEntry;
use Modules\Accounting\Entities\PaymentDetail;

class TransactionAccountMap
{
    public function categoryFromMapping(string $mappingFor): ?string
    {
        if (in_array($mappingFor, ['invoice', 'payment', 'sale'], true)) {
            return 'sale';
        }
        if (in_array($mappingFor, ['purchase_payment', 'purchase'], true)) {
            return 'purchase';
        }
        if ($mappingFor === 'purchase_order') {
            return 'purchase_order';
        }
        if (in_array($mappingFor, ['expense', 'expense_refund'], true)) {
            return 'expense';
        }

        return null;
    }

    public function categoryFromType(?string $type): ?string
    {
        if ($type === 'sell') {
            return 'sale';
        }
        if (in_array($type, ['expense', 'expense_refund'], true)) {
            return 'expense';
        }
        if ($type === 'purchase') {
            return 'purchase';
        }
        if ($type === 'purchase_order') {
            return 'purchase_order';
        }

        return null;
    }

    public function remember(int $businessId, string $category, string $mapType, int $accountId): void
    {
        $row = DB::table('business')->where('id', $businessId)->first();
        $settings = [];
        if ($row && ! empty($row->accounting_settings)) {
            $decoded = json_decode($row->accounting_settings, true);
            $settings = is_array($decoded) ? $decoded : [];
        }

        $settings['transaction_map'][$category] = [
            'chart_of_account_id' => $accountId,
            'map_type' => $mapType,
        ];

        DB::table('business')->where('id', $businessId)->update([
            'accounting_settings' => json_encode($settings),
        ]);
    }

    public function mapNew($transaction): void
    {
        if (empty($transaction) || ! empty($transaction->journal_entry_id)) {
            return;
        }
        if (in_array($transaction->status, ['draft', 'quotation'], true)) {
            return;
        }

        $category = $this->categoryFromType($transaction->type);
        if (empty($category) || empty($transaction->business_id)) {
            return;
        }

        $default = $this->defaultFor((int) $transaction->business_id, $category);
        if (empty($default)) {
            return;
        }

        try {
            $this->mapOne(
                $transaction,
                (int) $default['chart_of_account_id'],
                (string) $default['map_type'],
                null,
                $category
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function mapUnmapped(int $businessId, string $category, string $mapType, int $accountId, ?string $notes): int
    {
        $count = 0;

        $this->unmappedQuery($businessId, $category)
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($accountId, $mapType, $notes, $category, &$count) {
                foreach ($rows as $row) {
                    try {
                        $this->mapOne($row, $accountId, $mapType, $notes, $category);
                        $count++;
                    } catch (\Throwable $e) {
                        report($e);
                    }
                }
            });

        return $count;
    }

    public function linkedAccount(int $businessId, string $category): array
    {
        $default = $this->defaultFor($businessId, $category);
        if (empty($default['chart_of_account_id'])) {
            return ['id' => null, 'name' => null];
        }

        return [
            'id' => (int) $default['chart_of_account_id'],
            'name' => ChartOfAccount::where('id', $default['chart_of_account_id'])->value('name'),
        ];
    }

    public function mapOne($transaction, int $accountId, string $mapType, ?string $notes, string $category): void
    {
        $transaction = $transaction instanceof Transaction
            ? $transaction
            : Transaction::find($transaction->id ?? null);

        if (empty($transaction) || ! empty($transaction->journal_entry_id) || empty($transaction->location_id)) {
            return;
        }

        $account = ChartOfAccount::find($accountId);
        if (empty($account)) {
            return;
        }
        if (! empty($account->business_id) && (int) $account->business_id !== (int) $transaction->business_id) {
            return;
        }

        $currencyId = DB::table('business')->where('id', $transaction->business_id)->value('currency_id');
        $transactionType = 'map_'.$category.'_transaction_to_journal_entry';
        $rawDate = ! empty($transaction->transaction_date) ? strtotime($transaction->transaction_date) : false;
        $today = $rawDate ? date('Y-m-d', $rawDate) : date('Y-m-d');
        $date = explode('-', $today);
        if ($notes === null || $notes === '') {
            $ref = $transaction->invoice_no ?: $transaction->ref_no;
            $labels = [
                'sale' => 'Sale',
                'expense' => 'Expense',
                'purchase' => 'Purchase',
                'purchase_order' => 'Purchase order',
            ];
            $notes = trim(($labels[$category] ?? 'Transaction').' '.$ref);
        }

        $paymentDetail = new PaymentDetail();
        $paymentDetail->created_by_id = Auth::id() ?: $transaction->created_by;
        $paymentDetail->payment_type_id = 1;
        $paymentDetail->transaction_type = $transactionType;
        $paymentDetail->save();

        $journalEntry = new JournalEntry();
        $journalEntry->created_by_id = Auth::id() ?: $transaction->created_by;
        $journalEntry->transaction_number = get_uniqid();
        $journalEntry->payment_detail_id = $paymentDetail->id;
        $journalEntry->location_id = $transaction->location_id;
        $journalEntry->currency_id = $currencyId;
        $journalEntry->chart_of_account_id = $accountId;
        $journalEntry->transaction_type = $transactionType;
        $journalEntry->date = $today;
        $journalEntry->month = $date[1];
        $journalEntry->year = $date[0];
        if ($mapType === 'credit') {
            $journalEntry->credit = $transaction->final_total;
        } else {
            $journalEntry->debit = $transaction->final_total;
        }
        $journalEntry->manual_entry = 0;
        $journalEntry->notes = $notes;
        $journalEntry->save();

        $transaction->journal_entry_id = $journalEntry->id;
        $transaction->save();
    }

    private function defaultFor(int $businessId, string $category): ?array
    {
        $raw = DB::table('business')->where('id', $businessId)->value('accounting_settings');
        $settings = json_decode($raw ?? '', true);
        if (! is_array($settings) || empty($settings['transaction_map'][$category]['chart_of_account_id'])) {
            return null;
        }

        return $settings['transaction_map'][$category];
    }

    public function mapRefund($sellReturn): void
    {
        if (empty($sellReturn) || $sellReturn->type !== 'sell_return' || empty($sellReturn->business_id)) {
            return;
        }

        $accountId = null;
        $saleSide = 'credit';
        $default = $this->defaultFor((int) $sellReturn->business_id, 'sale');
        if (! empty($default['chart_of_account_id'])) {
            $accountId = (int) $default['chart_of_account_id'];
            $saleSide = ($default['map_type'] ?? 'credit') === 'debit' ? 'debit' : 'credit';
        }

        if (empty($accountId) && ! empty($sellReturn->return_parent_id)) {
            $parent = DB::table('transactions')
                ->where('id', $sellReturn->return_parent_id)
                ->value('journal_entry_id');
            if (! empty($parent)) {
                $entry = DB::table('journal_entries')->where('id', $parent)->first();
                if (! empty($entry->chart_of_account_id)) {
                    $accountId = (int) $entry->chart_of_account_id;
                    $saleSide = ((float) $entry->debit > 0 && (float) $entry->credit <= 0) ? 'debit' : 'credit';
                }
            }
        }

        if (empty($accountId)) {
            return;
        }

        $refundSide = $saleSide === 'credit' ? 'debit' : 'credit';
        $note = trim('Refund '.($sellReturn->invoice_no ?: ''));

        if (! empty($sellReturn->journal_entry_id)) {
            $entry = JournalEntry::find($sellReturn->journal_entry_id);
            if (! empty($entry)) {
                $entry->chart_of_account_id = $accountId;
                $entry->debit = $refundSide === 'debit' ? $sellReturn->final_total : 0;
                $entry->credit = $refundSide === 'credit' ? $sellReturn->final_total : 0;
                $entry->notes = $note;
                $entry->reversed = 0;
                $entry->save();
            }

            return;
        }

        $this->mapOne($sellReturn, $accountId, $refundSide, $note, 'sale_refund');
    }

    public function unmapRefund($sellReturn): void
    {
        if (empty($sellReturn->journal_entry_id)) {
            return;
        }

        $entry = JournalEntry::find($sellReturn->journal_entry_id);
        if (! empty($entry)) {
            $entry->reversed = 1;
            $entry->save();
        }
    }

    private function unmappedQuery(int $businessId, string $category)
    {
        $query = Transaction::where('business_id', $businessId)
            ->whereNull('journal_entry_id')
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhereNotIn('status', ['draft', 'quotation']);
            });

        if ($category === 'sale') {
            $query->where('type', 'sell');
        } elseif ($category === 'expense') {
            $query->whereIn('type', ['expense', 'expense_refund']);
        } elseif ($category === 'purchase') {
            $query->where('type', 'purchase');
        } elseif ($category === 'purchase_order') {
            $query->where('type', 'purchase_order');
        } else {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }
}

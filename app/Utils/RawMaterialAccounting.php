<?php

namespace App\Utils;

use App\BusinessLocation;
use App\InventoryPurchase;
use App\InventoryPurchasePayment;
use App\Transaction;
use App\TransactionPayment;
use Illuminate\Support\Facades\Schema;

class RawMaterialAccounting
{
    public static function backfill(int $businessId): void
    {
        if (! Schema::hasColumn('inventory_purchases', 'transaction_id')) {
            return;
        }

        $purchases = InventoryPurchase::where('business_id', $businessId)
            ->whereNull('transaction_id')
            ->with('payments')
            ->limit(100)
            ->get();

        foreach ($purchases as $purchase) {
            self::syncPurchase($purchase);
            $purchase->refresh();
            foreach ($purchase->payments as $payment) {
                self::syncPayment($purchase, $payment);
            }
        }

        if (! Schema::hasColumn('inventory_purchase_payments', 'transaction_payment_id')) {
            return;
        }

        $payments = InventoryPurchasePayment::whereNull('transaction_payment_id')
            ->whereHas('purchase', function ($query) use ($businessId) {
                $query->where('business_id', $businessId)->whereNotNull('transaction_id');
            })
            ->with('purchase')
            ->limit(100)
            ->get();

        foreach ($payments as $payment) {
            self::syncPayment($payment->purchase, $payment);
        }
    }

    public static function syncPurchase(InventoryPurchase $purchase, array $chosenAccounts = []): void
    {
        if (! Schema::hasColumn('inventory_purchases', 'transaction_id')) {
            return;
        }

        $existing = $purchase->transaction_id
            ? Transaction::where('business_id', $purchase->business_id)->where('id', $purchase->transaction_id)->first()
            : null;

        $locationId = $existing
            ? $existing->location_id
            : BusinessLocation::where('business_id', $purchase->business_id)->orderBy('id')->value('id');

        if (empty($locationId)) {
            return;
        }

        $payload = [
            'business_id' => $purchase->business_id,
            'location_id' => $locationId,
            'type' => 'purchase',
            'sub_type' => 'raw_material',
            'status' => $purchase->status === 'ordered' ? 'ordered' : 'received',
            'payment_status' => $purchase->payment_status ?: 'due',
            'contact_id' => $purchase->contact_id,
            'ref_no' => $purchase->ref_no,
            'transaction_date' => $purchase->purchase_date ? $purchase->purchase_date->format('Y-m-d').' 00:00:00' : now(),
            'total_before_tax' => $purchase->total_amount,
            'final_total' => $purchase->total_amount,
            'exchange_rate' => 1,
            'additional_notes' => 'Raw material purchase',
            'created_by' => $purchase->created_by,
        ];

        if ($existing) {
            $existing->update($payload);
            $transaction = $existing;
        } else {
            $transaction = Transaction::create($payload);
            $purchase->transaction_id = $transaction->id;
            $purchase->save();
        }

        self::applyDefaultMap(
            'purchase',
            (int) $transaction->id,
            (int) $purchase->business_id,
            (int) (auth()->id() ?: $purchase->created_by),
            (int) $locationId,
            $chosenAccounts
        );
    }

    public static function syncPayment(InventoryPurchase $purchase, InventoryPurchasePayment $payment): void
    {
        if (! Schema::hasColumn('inventory_purchase_payments', 'transaction_payment_id')) {
            return;
        }

        if (empty($purchase->transaction_id)) {
            self::syncPurchase($purchase);
            $purchase->refresh();
        }

        if (empty($purchase->transaction_id)) {
            return;
        }

        $method = $payment->method === 'bank' ? 'bank_transfer' : $payment->method;
        if (! in_array($method, ['cash', 'card', 'cheque', 'bank_transfer', 'other'], true)) {
            $method = 'other';
        }

        $payload = [
            'transaction_id' => $purchase->transaction_id,
            'business_id' => $purchase->business_id,
            'amount' => $payment->amount,
            'method' => $method,
            'paid_on' => $payment->paid_on ? $payment->paid_on->format('Y-m-d').' 00:00:00' : now(),
            'created_by' => $payment->created_by,
            'payment_for' => $purchase->contact_id,
            'cheque_number' => $payment->cheque_number,
            'note' => $payment->note,
            'payment_ref_no' => $payment->reference_no ?: ('RMP-PAY-'.$payment->id),
        ];

        $existing = $payment->transaction_payment_id
            ? TransactionPayment::where('business_id', $purchase->business_id)->where('id', $payment->transaction_payment_id)->first()
            : null;

        if ($existing) {
            $existing->update($payload);
            $transactionPayment = $existing;
        } else {
            $transactionPayment = TransactionPayment::create($payload);
            $payment->transaction_payment_id = $transactionPayment->id;
            $payment->save();
        }

        Transaction::where('id', $purchase->transaction_id)->update([
            'payment_status' => $purchase->payment_status ?: 'due',
        ]);

        $locationId = Transaction::where('id', $purchase->transaction_id)->value('location_id');
        if (empty($locationId)) {
            return;
        }

        self::applyDefaultMap(
            'purchase_payment',
            (int) $transactionPayment->id,
            (int) $purchase->business_id,
            (int) (auth()->id() ?: $payment->created_by),
            (int) $locationId
        );
    }

    private static function applyDefaultMap(string $type, int $id, int $businessId, int $userId, int $locationId, array $chosenAccounts = []): void
    {
        if (! class_exists(\Modules\Accounting\Utils\AccountingUtil::class)) {
            return;
        }

        $depositTo = $chosenAccounts['deposit_to'] ?? null;
        $paymentAccount = $chosenAccounts['payment_account'] ?? null;
        if (empty($depositTo) || empty($paymentAccount)) {
            $location = BusinessLocation::find($locationId);
            if (! $location || empty($location->accounting_default_map)) {
                return;
            }

            $map = json_decode($location->accounting_default_map, true);
            if (! is_array($map)) {
                return;
            }

            $key = $type === 'purchase_payment' ? 'purchase_payment' : 'purchases';
            $depositTo = $map[$key]['deposit_to'] ?? null;
            $paymentAccount = $map[$key]['payment_account'] ?? null;
        }
        if (empty($depositTo) || empty($paymentAccount)) {
            return;
        }

        try {
            (new \Modules\Accounting\Utils\AccountingUtil())->saveMap($type, $id, $userId, $businessId, $depositTo, $paymentAccount);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InventoryPurchasePayment extends Model
{
    protected $table = 'inventory_purchase_payments';

    protected $fillable = [
        'inventory_purchase_id',
        'amount',
        'paid_on',
        'method',
        'account_id',
        'cheque_number',
        'cheque_date',
        'cheque_cleared_at',
        'reference_no',
        'attachment',
        'note',
        'created_by',
        'transaction_payment_id',
    ];

    protected $casts = [
        'amount' => 'float',
        'paid_on' => 'date',
        'cheque_date' => 'date',
        'cheque_cleared_at' => 'datetime',
    ];

    public function purchase()
    {
        return $this->belongsTo(InventoryPurchase::class, 'inventory_purchase_id');
    }

    public function isChequePending(): bool
    {
        return $this->method === 'cheque' && empty($this->cheque_cleared_at);
    }

    public function isChequeCleared(): bool
    {
        return $this->method !== 'cheque' || ! empty($this->cheque_cleared_at);
    }
}

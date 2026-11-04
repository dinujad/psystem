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
    ];

    public function purchase()
    {
        return $this->belongsTo(InventoryPurchase::class, 'inventory_purchase_id');
    }
}

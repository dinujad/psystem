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
        'note',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'float',
        'paid_on' => 'date',
    ];

    public function purchase()
    {
        return $this->belongsTo(InventoryPurchase::class, 'inventory_purchase_id');
    }
}

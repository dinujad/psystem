<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InventoryPurchase extends Model
{
    protected $table = 'inventory_purchases';

    protected $fillable = [
        'business_id',
        'ref_no',
        'contact_id',
        'purchase_date',
        'status',
        'total_amount',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'total_amount' => 'float',
    ];

    public function lines()
    {
        return $this->hasMany(InventoryPurchaseLine::class, 'inventory_purchase_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}

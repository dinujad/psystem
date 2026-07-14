<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InventoryPurchaseLine extends Model
{
    protected $table = 'inventory_purchase_lines';

    protected $fillable = [
        'inventory_purchase_id',
        'material_id',
        'quantity',
        'unit_cost',
        'line_total',
    ];

    protected $casts = [
        'quantity' => 'float',
        'unit_cost' => 'float',
        'line_total' => 'float',
    ];

    public function purchase()
    {
        return $this->belongsTo(InventoryPurchase::class, 'inventory_purchase_id');
    }

    public function material()
    {
        return $this->belongsTo(InventoryMaterial::class, 'material_id');
    }
}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InventoryMaterial extends Model
{
    protected $table = 'inventory_materials';
    protected $fillable = [
        'name', 'sku', 'category_id', 'unit_id', 'price_per_unit',
        'current_stock', 'reorder_level', 'description',
    ];

    protected $casts = [
        'price_per_unit' => 'float',
        'current_stock'  => 'float',
        'reorder_level'  => 'float',
    ];

    public function category()
    {
        return $this->belongsTo(InventoryCategory::class, 'category_id');
    }

    public function unit()
    {
        return $this->belongsTo(InventoryUnit::class, 'unit_id');
    }

    public function jobUsages()
    {
        return $this->hasMany(ProductionJobMaterial::class, 'material_id');
    }

    public function hasEnoughStock(float $qty): bool
    {
        return $this->current_stock >= $qty;
    }

    public function isLowStock(): bool
    {
        if ($this->reorder_level <= 0) {
            return $this->current_stock <= 0;
        }

        return $this->current_stock <= $this->reorder_level;
    }

    public function stockValue(): float
    {
        return (float) $this->current_stock * (float) $this->price_per_unit;
    }
}

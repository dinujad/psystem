<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InventoryUnit extends Model
{
    protected $table = 'inventory_units';
    protected $fillable = ['name', 'abbreviation'];

    public function materials()
    {
        return $this->hasMany(InventoryMaterial::class, 'unit_id');
    }
}

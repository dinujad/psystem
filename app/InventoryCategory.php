<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InventoryCategory extends Model
{
    protected $table = 'inventory_categories';
    protected $fillable = ['name', 'description'];

    public function materials()
    {
        return $this->hasMany(InventoryMaterial::class, 'category_id');
    }
}

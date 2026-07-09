<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductionJobMaterial extends Model
{
    protected $table = 'production_job_materials';
    protected $fillable = ['job_id', 'stage', 'material_id', 'quantity', 'unit_price', 'added_by'];

    protected $casts = [
        'quantity'   => 'float',
        'unit_price' => 'float',
    ];

    public function job()
    {
        return $this->belongsTo(ProductionJob::class, 'job_id');
    }

    public function material()
    {
        return $this->belongsTo(InventoryMaterial::class, 'material_id');
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function getSubtotalAttribute(): float
    {
        return round($this->quantity * $this->unit_price, 2);
    }
}

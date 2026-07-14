<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductionMaterialRequest extends Model
{
    protected $table = 'production_material_requests';

    protected $fillable = [
        'job_id',
        'material_id',
        'quantity',
        'status',
        'requested_by',
        'notes',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'issued_usage_id',
    ];

    protected $casts = [
        'quantity' => 'float',
        'reviewed_at' => 'datetime',
    ];

    public function job()
    {
        return $this->belongsTo(ProductionJob::class, 'job_id');
    }

    public function material()
    {
        return $this->belongsTo(InventoryMaterial::class, 'material_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function issuedUsage()
    {
        return $this->belongsTo(ProductionJobMaterial::class, 'issued_usage_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}

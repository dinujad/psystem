<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductionStageApproval extends Model
{
    protected $table = 'production_stage_approvals';

    protected $fillable = [
        'job_id',
        'from_stage',
        'to_stage',
        'status',
        'requested_by',
        'notes',
        'payload',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected $casts = [
        'payload' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function job()
    {
        return $this->belongsTo(ProductionJob::class, 'job_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
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

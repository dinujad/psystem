<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductionJobTask extends Model
{
    protected $table = 'production_job_tasks';

    protected $fillable = [
        'job_id', 'stage', 'title', 'estimated_minutes',
        'sort_order', 'is_completed', 'completed_at', 'completed_by',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function job()
    {
        return $this->belongsTo(ProductionJob::class, 'job_id');
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function formattedEstimate(): ?string
    {
        if (! $this->estimated_minutes) {
            return null;
        }

        $mins = (int) $this->estimated_minutes;
        if ($mins >= 60) {
            $h = intdiv($mins, 60);
            $m = $mins % 60;

            return $m > 0 ? "{$h}h {$m}m" : "{$h}h";
        }

        return "{$mins}m";
    }
}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductionJobStage extends Model
{
    public $timestamps = false;
    protected $table = 'production_job_stages';

    protected $fillable = [
        'job_id', 'stage', 'notes', 'moved_by', 'started_at', 'completed_at',
        'task_started_at', 'task_ended_at',
        'stage_rate', 'stage_rate_notes',
        'quality_rating', 'quality_comment',
        'incoming_rating', 'incoming_comment', 'rated_by',
    ];

    protected $casts = [
        'started_at'      => 'datetime',
        'completed_at'    => 'datetime',
        'task_started_at' => 'datetime',
        'task_ended_at'   => 'datetime',
        'stage_rate'      => 'float',
    ];

    public function getTaskDurationAttribute(): ?int
    {
        if ($this->task_started_at && $this->task_ended_at) {
            return (int) $this->task_started_at->diffInMinutes($this->task_ended_at);
        }
        return null;
    }

    public function job()
    {
        return $this->belongsTo(ProductionJob::class, 'job_id');
    }

    public function movedBy()
    {
        return $this->belongsTo(User::class, 'moved_by');
    }

    public function ratedBy()
    {
        return $this->belongsTo(User::class, 'rated_by');
    }
}

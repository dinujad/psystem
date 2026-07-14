<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductionJob extends Model
{
    protected $table = 'production_jobs';

    protected $fillable = [
        'job_number', 'inquiry_id', 'customer_name', 'customer_phone',
        'title', 'description', 'google_drive_url',
        'current_stage', 'priority', 'due_date', 'created_by',
        'started_at', 'customer_notified_at_start',
        'product_id', 'variation_id', 'converted_qty', 'converted_at', 'converted_by',
    ];

    protected $casts = [
        'due_date' => 'date',
        'converted_at' => 'datetime',
        'started_at' => 'datetime',
        'customer_notified_at_start' => 'boolean',
    ];

    public static function allStages(): array
    {
        return [
            'design'     => 'Design Team',
            'printing'   => 'Printing',
            'production' => 'Workshop', // DB key stays production
            'quality'    => 'Quality & Packing',
            'dispatch'   => 'Dispatch',
            'completed'  => 'Completed',
        ];
    }

    public static function nextStage(string $current): ?string
    {
        $keys = array_keys(self::allStages());
        $idx  = array_search($current, $keys, true);
        return ($idx !== false && isset($keys[$idx + 1])) ? $keys[$idx + 1] : null;
    }

    public static function prevStage(string $current): ?string
    {
        $keys = array_keys(self::allStages());
        $idx  = array_search($current, $keys, true);
        return ($idx !== false && $idx > 0 && isset($keys[$idx - 1])) ? $keys[$idx - 1] : null;
    }

    public static function stageColor(string $stage): string
    {
        $map = [
            'design'     => '#7c5cfc',
            'printing'   => '#0ea5e9',
            'production' => '#3b82f6', // Workshop
            'quality'    => '#f59e0b',
            'dispatch'   => '#10b981',
            'completed'  => '#6b7280',
        ];
        return $map[$stage] ?? '#6b7280';
    }

    public static function priorityColor(string $priority): string
    {
        $map = [
            'urgent' => '#ef4444',
            'high'   => '#f97316',
            'normal' => '#3b82f6',
            'low'    => '#9ca3af',
        ];
        return $map[$priority] ?? '#9ca3af';
    }

    public static function generateJobNumber(): string
    {
        $year  = date('Y');
        $count = static::whereYear('created_at', $year)->count() + 1;
        return 'PRN-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    public function inquiry()
    {
        return $this->belongsTo(WhatsappChatAssignment::class, 'inquiry_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function files()
    {
        return $this->hasMany(ProductionJobFile::class, 'job_id')->orderByDesc('created_at');
    }

    public function stageHistory()
    {
        return $this->hasMany(ProductionJobStage::class, 'job_id')->orderByDesc('started_at');
    }

    public function materials()
    {
        return $this->hasMany(ProductionJobMaterial::class, 'job_id')->with('material.unit');
    }

    public function sectionPlans()
    {
        return $this->hasMany(ProductionJobSectionPlan::class, 'job_id');
    }

    public function tasks()
    {
        return $this->hasMany(ProductionJobTask::class, 'job_id')->orderBy('stage')->orderBy('sort_order');
    }

    public function tasksForStage(string $stage)
    {
        return $this->tasks()->where('stage', $stage);
    }

    public function assignments()
    {
        return $this->hasMany(ProductionJobAssignment::class, 'job_id');
    }

    public function assignmentsForStage(string $stage)
    {
        return $this->assignments()->where('stage', $stage)->with('user');
    }

    public function getTotalMaterialCostAttribute(): float
    {
        return (float) $this->materials->sum(fn ($m) => $m->quantity * $m->unit_price);
    }

    public function getTotalStageRateAttribute(): float
    {
        return (float) $this->stageHistory->sum('stage_rate');
    }

    public function getGrandTotalAttribute(): float
    {
        return $this->total_material_cost + $this->total_stage_rate;
    }

    public function latestStage()
    {
        return $this->hasOne(ProductionJobStage::class, 'job_id')->orderByDesc('started_at');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function convertedBy()
    {
        return $this->belongsTo(User::class, 'converted_by');
    }

    public function isConverted(): bool
    {
        return ! empty($this->product_id) && ! empty($this->converted_at);
    }

    public function stageApprovals()
    {
        return $this->hasMany(ProductionStageApproval::class, 'job_id');
    }

    public function pendingStageApproval()
    {
        return $this->hasOne(ProductionStageApproval::class, 'job_id')
            ->where('status', 'pending')
            ->latest('id');
    }

    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}

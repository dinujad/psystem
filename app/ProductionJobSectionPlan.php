<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductionJobSectionPlan extends Model
{
    protected $table = 'production_job_section_plans';

    protected $fillable = ['job_id', 'stage', 'estimated_minutes', 'notes'];

    public function job()
    {
        return $this->belongsTo(ProductionJob::class, 'job_id');
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

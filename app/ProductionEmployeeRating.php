<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductionEmployeeRating extends Model
{
    protected $table = 'production_employee_ratings';

    protected $fillable = [
        'job_id', 'rated_user_id', 'rated_stage', 'rater_stage',
        'rated_by', 'rating', 'comment',
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    public function job()
    {
        return $this->belongsTo(ProductionJob::class, 'job_id');
    }

    public function ratedUser()
    {
        return $this->belongsTo(User::class, 'rated_user_id');
    }

    public function ratedBy()
    {
        return $this->belongsTo(User::class, 'rated_by');
    }
}

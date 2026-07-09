<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductionJobAssignment extends Model
{
    protected $table = 'production_job_assignments';

    protected $fillable = ['job_id', 'stage', 'user_id', 'assigned_by'];

    public function job()
    {
        return $this->belongsTo(ProductionJob::class, 'job_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public static function userDisplayName(?User $user): string
    {
        if (! $user) {
            return 'Unknown';
        }

        $name = trim(($user->surname ?? '').' '.($user->first_name ?? '').' '.($user->last_name ?? ''));

        return $name ?: ($user->username ?? 'User');
    }
}

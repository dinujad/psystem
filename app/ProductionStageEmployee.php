<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductionStageEmployee extends Model
{
    protected $table = 'production_stage_employees';

    protected $fillable = ['stage', 'user_id', 'whatsapp_number', 'is_head', 'assigned_by'];

    protected $casts = [
        'is_head' => 'boolean',
    ];

    public static function workableStages(): array
    {
        return ['design', 'printing', 'production', 'quality', 'dispatch'];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public static function headForStage(string $stage): ?self
    {
        return static::where('stage', $stage)
            ->where('is_head', true)
            ->whereNotNull('whatsapp_number')
            ->where('whatsapp_number', '!=', '')
            ->first();
    }

    public static function stagesForUser(int $userId): array
    {
        return static::where('user_id', $userId)->pluck('stage')->all();
    }

    /**
     * Team members without admin or production.access — section dashboard only.
     */
    public static function isSectionOnlyUser(?User $user = null): bool
    {
        $user = $user ?? auth()->user();
        if (! $user) {
            return false;
        }

        if ($user->can('send_notifications') || $user->can('production.access')) {
            return false;
        }

        return static::where('user_id', $user->id)->exists();
    }

    public static function primarySection(?int $userId = null): ?string
    {
        $userId = $userId ?? auth()->id();
        $stages = static::stagesForUser($userId);

        return $stages[0] ?? null;
    }

    public static function dashboardUrl(?int $userId = null): ?string
    {
        $stage = static::primarySection($userId);

        return $stage ? route('production.section', $stage) : null;
    }

    public static function groupedByStage(): array
    {
        $stages = ProductionJob::allStages();
        $grouped = [];

        foreach (array_keys($stages) as $stage) {
            $grouped[$stage] = collect();
        }

        $rows = static::with('user')->orderBy('stage')->orderBy('id')->get();
        foreach ($rows as $row) {
            if (isset($grouped[$row->stage])) {
                $grouped[$row->stage]->push($row);
            }
        }

        return $grouped;
    }
}

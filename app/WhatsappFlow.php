<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsappFlow extends Model
{
    protected $table = 'whatsapp_flows';

    protected $fillable = [
        'name',
        'trigger_keywords',
        'is_default_fallback',
        'is_active',
    ];

    protected $casts = [
        'trigger_keywords'    => 'array',
        'is_default_fallback' => 'boolean',
        'is_active'           => 'boolean',
    ];

    public function steps(): HasMany
    {
        return $this->hasMany(WhatsappFlowStep::class, 'flow_id')->orderBy('sort_order')->orderBy('id');
    }

    /**
     * The step a flow begins at. Prefers the explicit is_first_step flag; if none
     * is set (e.g. older data), falls back to the lowest sort_order / id step.
     */
    public function firstStep(): ?WhatsappFlowStep
    {
        return $this->steps->firstWhere('is_first_step', true)
            ?? $this->steps->first();
    }

    public function stepByKey(?string $key): ?WhatsappFlowStep
    {
        if ($key === null) {
            return null;
        }

        return $this->steps->firstWhere('step_key', $key);
    }

    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}

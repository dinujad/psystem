<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappFlowStep extends Model
{
    protected $table = 'whatsapp_flow_steps';

    protected $fillable = [
        'flow_id',
        'step_key',
        'message_text',
        'step_type',
        'options',
        'next_step_key',
        'save_input_as',
        'is_first_step',
        'triggers_human_takeover',
        'sort_order',
    ];

    protected $casts = [
        'options'                 => 'array',
        'is_first_step'           => 'boolean',
        'triggers_human_takeover' => 'boolean',
        'sort_order'              => 'integer',
    ];

    public function flow(): BelongsTo
    {
        return $this->belongsTo(WhatsappFlow::class, 'flow_id');
    }

    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsappConversation extends Model
{
    protected $table = 'whatsapp_conversations';

    public const STATUS_BOT_ACTIVE     = 'bot_active';
    public const STATUS_HUMAN_TAKEOVER = 'human_takeover';
    public const STATUS_COMPLETED      = 'completed';
    public const STATUS_IDLE           = 'idle';

    protected $fillable = [
        'phone_number',
        'current_flow_id',
        'current_step_key',
        'variables',
        'status',
        'last_interaction_at',
    ];

    protected $casts = [
        'variables'           => 'array',
        'last_interaction_at' => 'datetime',
    ];

    public function currentFlow(): BelongsTo
    {
        return $this->belongsTo(WhatsappFlow::class, 'current_flow_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WhatsappConversationLog::class, 'conversation_id');
    }

    public function isHumanTakeover(): bool
    {
        return $this->status === self::STATUS_HUMAN_TAKEOVER;
    }

    public function hasActiveStep(): bool
    {
        return $this->current_flow_id !== null && $this->current_step_key !== null;
    }

    /** Clear the live session but keep the contact row + collected variables. */
    public function resetSession(string $status = self::STATUS_IDLE): void
    {
        $this->current_flow_id  = null;
        $this->current_step_key = null;
        $this->status          = $status;
        $this->save();
    }

    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}

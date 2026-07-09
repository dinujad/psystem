<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappConversationLog extends Model
{
    protected $table = 'whatsapp_conversation_logs';

    // Only created_at is tracked on this table (no updated_at column)
    public const UPDATED_AT = null;

    protected $fillable = [
        'conversation_id',
        'direction',
        'message',
        'step_key',
        'created_at',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WhatsappConversation::class, 'conversation_id');
    }

    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}

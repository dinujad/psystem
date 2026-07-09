<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WhatsappMessage extends Model
{
    protected $table = 'whatsapp_messages';

    protected $fillable = [
        'direction',
        'phone_number',
        'message',
        'status',
        'read_at',
        'message_id',
        'media_type',
        'media_path',
        'media_filename',
        'media_mimetype',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function getMediaUrlAttribute(): ?string
    {
        if (! $this->media_path) return null;
        return route('whatsapp.media', ['path' => $this->media_path]);
    }

    /**
     * Serialize dates in the app's local timezone (Asia/Kolkata, +5:30) without
     * converting to UTC, so the inbox shows real Sri Lankan time consistently.
     */
    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}

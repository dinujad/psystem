<?php

namespace App;

use App\Services\WhatsappLidResolver;
use Illuminate\Database\Eloquent\Model;

class WhatsappContact extends Model
{
    protected $table = 'whatsapp_contacts';

    protected $fillable = ['phone_number', 'name', 'wa_name', 'profile_picture', 'notes'];

    public function labels()
    {
        return $this->belongsToMany(WhatsappLabel::class, 'whatsapp_contact_label', 'contact_id', 'label_id');
    }

    public function displayName(): string
    {
        if ($this->name) {
            return $this->name;
        }
        if ($this->wa_name) {
            return $this->wa_name;
        }

        if (WhatsappLidResolver::isLikelyLid((string) $this->phone_number)) {
            return 'Unknown Contact';
        }

        return '+'.$this->phone_number;
    }

    public function hasProfilePicture(): bool
    {
        return ! empty($this->profile_picture)
            && file_exists(storage_path('app/public/'.$this->profile_picture));
    }

    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}

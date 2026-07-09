<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WhatsappLabel extends Model
{
    protected $table = 'whatsapp_labels';

    protected $fillable = ['name', 'color'];

    public function contacts()
    {
        return $this->belongsToMany(WhatsappContact::class, 'whatsapp_contact_label', 'label_id', 'contact_id');
    }

    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}

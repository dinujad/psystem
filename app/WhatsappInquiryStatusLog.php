<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WhatsappInquiryStatusLog extends Model
{
    protected $table = 'whatsapp_inquiry_status_logs';

    protected $fillable = [
        'assignment_id',
        'from_status',
        'to_status',
        'payment_amount',
        'payment_method',
        'payment_reference',
        'notes',
        'updated_by',
    ];

    public function assignment()
    {
        return $this->belongsTo(WhatsappChatAssignment::class, 'assignment_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}

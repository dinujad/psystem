<?php

namespace App;

use App\User;
use Illuminate\Database\Eloquent\Model;

class WhatsappChatAssignment extends Model
{
    protected $table = 'whatsapp_chat_assignments';

    protected $fillable = [
        'phone_number',
        'assigned_to',
        'assigned_by',
        'status',
        'notes',
        'customer_name',
        'inquiry_category',
        'inquiry_notes',
        'inquiry_status',
        'payment_amount',
        'payment_method',
        'payment_reference',
        'status_updated_by',
        'status_updated_at',
        'closed_by',
        'closed_at',
    ];

    protected $dates = ['closed_at', 'status_updated_at'];

    public function statusUpdatedBy()
    {
        return $this->belongsTo(User::class, 'status_updated_by');
    }

    public function statusLogs()
    {
        return $this->hasMany(WhatsappInquiryStatusLog::class, 'assignment_id')->orderByDesc('created_at');
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function isAssigned(): bool
    {
        return ! is_null($this->assigned_to) && $this->status === 'open';
    }

    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}

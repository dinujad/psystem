<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DeliveryParcel extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'amount' => 'float',
        'exchange' => 'integer',
        'api_status_code' => 'integer',
        'api_response' => 'array',
        'last_update_time' => 'datetime',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isSynced(): bool
    {
        return ! empty($this->waybill_no) && (int) $this->api_status_code === 200;
    }

    public static function statusBadgeClass(?string $status): string
    {
        $s = strtolower(trim((string) $status));

        if ($s === '' || $s === 'pending') {
            return 'pending';
        }
        if (str_contains($s, 'deliver') || str_contains($s, 'complet')) {
            return 'delivered';
        }
        if (str_contains($s, 'cancel') || str_contains($s, 'return') || str_contains($s, 'fail')) {
            return 'failed';
        }
        if (str_contains($s, 'transit') || str_contains($s, 'ship') || str_contains($s, 'out') || str_contains($s, 'pick')) {
            return 'transit';
        }

        return 'transit';
    }
}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DeliveryParcel extends Model
{
    protected $guarded = ['id'];

    public const COURIER_NAME = 'Fardar Express Domestic';

    protected $casts = [
        'amount' => 'float',
        'exchange' => 'integer',
        'api_status_code' => 'integer',
        'api_response' => 'array',
        'status_history' => 'array',
        'last_update_time' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function (DeliveryParcel $parcel) {
            if (empty($parcel->tracking_token)) {
                $parcel->tracking_token = Str::random(40);
            }
        });
    }

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

    public function trackingUrl(): string
    {
        if (empty($this->tracking_token)) {
            $this->tracking_token = Str::random(40);
            $this->save();
        }

        return url('/track/'.$this->tracking_token);
    }

    public function pushStatusHistory(string $status, $at = null): void
    {
        $history = is_array($this->status_history) ? $this->status_history : [];
        $atStr = $at
            ? (is_string($at) ? $at : $at->format('Y-m-d H:i:s'))
            : now()->format('Y-m-d H:i:s');

        $last = end($history);
        if ($last && strcasecmp((string) ($last['status'] ?? ''), $status) === 0) {
            return;
        }

        $history[] = [
            'status' => $status,
            'at' => $atStr,
        ];
        $this->status_history = $history;
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
        if (str_contains($s, 'cancel') || str_contains($s, 'return') || str_contains($s, 'fail') || str_contains($s, 'removed')) {
            return 'failed';
        }
        if (str_contains($s, 'transit') || str_contains($s, 'ship') || str_contains($s, 'out') || str_contains($s, 'pick') || str_contains($s, 'wait')) {
            return 'transit';
        }

        return 'transit';
    }
}

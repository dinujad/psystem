<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OnePayPayment extends Model
{
    protected $table = 'onepay_payments';

    protected $guarded = ['id'];

    protected $casts = [
        'amount' => 'float',
        'refund_amount' => 'float',
        'is_refunded' => 'boolean',
        'paid_on' => 'datetime',
        'request_payload' => 'array',
        'response_payload' => 'array',
        'callback_payload' => 'array',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function transactionPayment()
    {
        return $this->belongsTo(TransactionPayment::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function isPaid(): bool
    {
        return in_array(strtolower((string) $this->status), ['paid', 'success', '1'], true)
            || (int) $this->status === 1;
    }
}

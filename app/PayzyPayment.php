<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PayzyPayment extends Model
{
    protected $table = 'payzy_payments';

    protected $guarded = ['id'];

    protected $casts = [
        'base_amount' => 'float',
        'tax_amount' => 'float',
        'charge_amount' => 'float',
        'is_refunded' => 'boolean',
        'paid_on' => 'datetime',
        'request_payload' => 'array',
        'response_payload' => 'array',
        'return_payload' => 'array',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function transactionPayment()
    {
        return $this->belongsTo(TransactionPayment::class);
    }

    public function installmentAmounts(): array
    {
        $count = max(1, (int) ($this->installment_count ?: 4));
        $total = round((float) $this->charge_amount, 2);
        $each = round($total / $count, 2);
        $parts = array_fill(0, $count - 1, $each);
        $parts[] = round($total - array_sum($parts), 2);

        return $parts;
    }
}

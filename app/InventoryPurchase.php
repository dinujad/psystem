<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class InventoryPurchase extends Model
{
    protected $table = 'inventory_purchases';

    protected $fillable = [
        'business_id',
        'ref_no',
        'contact_id',
        'purchase_date',
        'pay_term_number',
        'pay_term_type',
        'status',
        'discount_type',
        'discount_amount',
        'tax_id',
        'tax_amount',
        'total_before_tax',
        'total_amount',
        'payment_status',
        'notes',
        'created_by',
        'transaction_id',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'total_amount' => 'float',
        'discount_amount' => 'float',
        'tax_amount' => 'float',
        'total_before_tax' => 'float',
        'pay_term_number' => 'integer',
    ];

    public function lines()
    {
        return $this->hasMany(InventoryPurchaseLine::class, 'inventory_purchase_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments()
    {
        return $this->hasMany(InventoryPurchasePayment::class, 'inventory_purchase_id');
    }

    public function tax()
    {
        return $this->belongsTo(TaxRate::class, 'tax_id');
    }

    public function amountPaid(): float
    {
        return round((float) $this->payments()->sum('amount'), 4);
    }

    public function amountDue(): float
    {
        return round(max(0, (float) $this->total_amount - $this->amountPaid()), 4);
    }

    public function dueDate(): ?Carbon
    {
        if (empty($this->purchase_date) || empty($this->pay_term_number) || empty($this->pay_term_type)) {
            return null;
        }

        $date = $this->purchase_date->copy();
        if ($this->pay_term_type === 'months') {
            return $date->addMonths((int) $this->pay_term_number);
        }

        return $date->addDays((int) $this->pay_term_number);
    }

    public function isOverdue(): bool
    {
        if ($this->payment_status === 'paid') {
            return false;
        }
        $due = $this->dueDate();

        return $due ? $due->lt(Carbon::today()) : false;
    }

    public function refreshPaymentStatus(): void
    {
        $paid = $this->amountPaid();
        $total = round((float) $this->total_amount, 4);

        if ($paid <= 0) {
            $status = 'due';
        } elseif ($paid + 0.009 >= $total) {
            $status = 'paid';
        } else {
            $status = 'partial';
        }

        $this->update(['payment_status' => $status]);
    }

    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}

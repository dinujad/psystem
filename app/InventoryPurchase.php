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
        // Uncleared cheques do not count as paid until manually marked.
        $query = $this->payments();
        try {
            if (
                \Schema::hasTable('inventory_purchase_payments')
                && \Schema::hasColumn('inventory_purchase_payments', 'cheque_cleared_at')
            ) {
                $query->where(function ($q) {
                    $q->where('method', '!=', 'cheque')
                        ->orWhereNotNull('cheque_cleared_at');
                });
            }
        } catch (\Throwable $e) {
            // Fall back to summing all payments if schema check fails.
        }

        return round((float) $query->sum('amount'), 4);
    }

    /**
     * Pending (issued but not yet cleared) cheque total.
     */
    public function amountPendingCheques(): float
    {
        try {
            if (
                ! \Schema::hasTable('inventory_purchase_payments')
                || ! \Schema::hasColumn('inventory_purchase_payments', 'cheque_cleared_at')
            ) {
                return 0.0;
            }
        } catch (\Throwable $e) {
            return 0.0;
        }

        return round((float) $this->payments()
            ->where('method', 'cheque')
            ->whereNull('cheque_cleared_at')
            ->sum('amount'), 4);
    }

    /**
     * Room left for new payments / cheques (excludes already-cleared + pending cheques).
     */
    public function amountAvailableToAllocate(): float
    {
        return round(max(0, (float) $this->total_amount - $this->amountPaid() - $this->amountPendingCheques()), 4);
    }

    public function amountDue(): float
    {
        // Still due until cheque is manually marked paid/cleared.
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

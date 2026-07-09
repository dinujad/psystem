<?php

use App\Product;
use App\ProductionJob;
use App\PurchaseLine;
use App\Transaction;
use App\Variation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackfillProductionJobStockTransactions extends Migration
{
    public function up()
    {
        if (! Schema::hasColumn('transactions', 'production_job_id')) {
            return;
        }

        ProductionJob::whereNotNull('converted_at')
            ->whereNotNull('variation_id')
            ->whereNotNull('product_id')
            ->get()
            ->each(function (ProductionJob $job) {
                if (Transaction::where('production_job_id', $job->id)->exists()) {
                    return;
                }

                $product = Product::find($job->product_id);
                if (! $product) {
                    return;
                }

                $variation = Variation::find($job->variation_id);
                if (! $variation) {
                    return;
                }

                $purchasePrice = (float) ($variation->default_purchase_price ?? 0);
                $purchasePriceIncTax = (float) ($variation->dpp_inc_tax ?? $purchasePrice);
                if ($purchasePriceIncTax <= 0) {
                    $purchasePriceIncTax = $purchasePrice;
                }

                $qty = (float) ($job->converted_qty ?? 0);
                if ($qty <= 0) {
                    return;
                }

                $notes = $job->title;
                if ($job->customer_name) {
                    $notes .= ' | Customer: ' . $job->customer_name;
                }

                $locationId = DB::table('variation_location_details')
                    ->where('variation_id', $job->variation_id)
                    ->where('product_id', $job->product_id)
                    ->value('location_id');

                if (! $locationId) {
                    $locationId = DB::table('product_locations')
                        ->where('product_id', $job->product_id)
                        ->value('location_id');
                }

                if (! $locationId) {
                    return;
                }

                $transaction = Transaction::create([
                    'type'               => 'production_purchase',
                    'status'             => 'received',
                    'business_id'        => $product->business_id,
                    'transaction_date'   => $job->converted_at ?? now(),
                    'location_id'        => $locationId,
                    'ref_no'             => $job->job_number,
                    'additional_notes'   => $notes,
                    'total_before_tax'   => $purchasePriceIncTax * $qty,
                    'final_total'        => $purchasePriceIncTax * $qty,
                    'payment_status'     => 'paid',
                    'created_by'         => $job->converted_by,
                    'production_job_id'  => $job->id,
                ]);

                $purchaseLine = new PurchaseLine();
                $purchaseLine->product_id = $job->product_id;
                $purchaseLine->variation_id = $job->variation_id;
                $purchaseLine->quantity = $qty;
                $purchaseLine->pp_without_discount = $purchasePrice;
                $purchaseLine->purchase_price = $purchasePrice;
                $purchaseLine->purchase_price_inc_tax = $purchasePriceIncTax;
                $purchaseLine->item_tax = max(0, $purchasePriceIncTax - $purchasePrice);
                $transaction->purchase_lines()->save($purchaseLine);
            });
    }

    public function down()
    {
        Transaction::where('type', 'production_purchase')
            ->whereNotNull('production_job_id')
            ->each(function (Transaction $transaction) {
                $transaction->purchase_lines()->delete();
                $transaction->delete();
            });
    }
}

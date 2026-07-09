<?php

namespace App\Services;

use App\ProductionJob;
use App\TransactionSellLine;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductionCostService
{
    public function materialCost(ProductionJob $job): float
    {
        if ($job->relationLoaded('materials')) {
            return (float) $job->materials->sum(fn ($m) => $m->quantity * $m->unit_price);
        }

        return (float) $job->materials()->sum(DB::raw('quantity * unit_price'));
    }

    public function stageCost(ProductionJob $job): float
    {
        if ($job->relationLoaded('stageHistory')) {
            return (float) $job->stageHistory->sum('stage_rate');
        }

        return (float) $job->stageHistory()->sum('stage_rate');
    }

    public function totalCost(ProductionJob $job): float
    {
        return round($this->materialCost($job) + $this->stageCost($job), 2);
    }

    public function stageCostBreakdown(ProductionJob $job): array
    {
        $history = $job->relationLoaded('stageHistory')
            ? $job->stageHistory
            : $job->stageHistory()->get();

        $breakdown = [];
        foreach (ProductionJob::allStages() as $key => $label) {
            if ($key === 'completed') {
                continue;
            }
            $rate = (float) $history->where('stage', $key)->sum('stage_rate');
            if ($rate > 0) {
                $breakdown[$key] = [
                    'label' => $label,
                    'amount' => $rate,
                ];
            }
        }

        return $breakdown;
    }

    public function jobCompletedAt(ProductionJob $job): ?Carbon
    {
        if ($job->current_stage !== 'completed') {
            return null;
        }

        $history = $job->relationLoaded('stageHistory')
            ? $job->stageHistory
            : $job->stageHistory()->get();

        $completed = $history->max('completed_at');

        return $completed ? Carbon::parse($completed) : $job->updated_at;
    }

    /**
     * Revenue: inquiry payment, then actual POS sales of converted product.
     */
    public function jobRevenue(ProductionJob $job): float
    {
        $revenue = 0.0;

        if ($job->relationLoaded('inquiry') && $job->inquiry && $job->inquiry->payment_amount) {
            $revenue += (float) $job->inquiry->payment_amount;
        } elseif ($job->inquiry_id) {
            $job->loadMissing('inquiry');
            if ($job->inquiry && $job->inquiry->payment_amount) {
                $revenue += (float) $job->inquiry->payment_amount;
            }
        }

        if ($job->product_id) {
            $revenue += $this->productSalesTotal($job->product_id, $job->variation_id);
        }

        return round($revenue, 2);
    }

    public function jobProfit(ProductionJob $job): float
    {
        return round($this->jobRevenue($job) - $this->totalCost($job), 2);
    }

    public function productSalesTotal(int $productId, ?int $variationId = null): float
    {
        $query = TransactionSellLine::query()
            ->join('transactions as t', 't.id', '=', 'transaction_sell_lines.transaction_id')
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->where('transaction_sell_lines.product_id', $productId);

        if ($variationId) {
            $query->where('transaction_sell_lines.variation_id', $variationId);
        }

        return (float) $query->sum(DB::raw(
            '(transaction_sell_lines.quantity - COALESCE(transaction_sell_lines.quantity_returned, 0)) * transaction_sell_lines.unit_price_inc_tax'
        ));
    }

    public function buildJobCostRow(ProductionJob $job): array
    {
        $material = $this->materialCost($job);
        $stage    = $this->stageCost($job);
        $total    = $material + $stage;
        $revenue  = $this->jobRevenue($job);
        $profit   = $revenue - $total;

        return [
            'job'              => $job,
            'material_cost'    => round($material, 2),
            'stage_cost'       => round($stage, 2),
            'total_cost'       => round($total, 2),
            'revenue'          => $revenue,
            'profit'           => round($profit, 2),
            'stage_breakdown'  => $this->stageCostBreakdown($job),
            'completed_at'     => $this->jobCompletedAt($job),
        ];
    }

    /**
     * @return array{rows: Collection, totals: array}
     */
    public function getCostsReport(
        ?string $startDate,
        ?string $endDate,
        string $status = 'all',
        ?string $stage = null
    ): array {
        $query = ProductionJob::with([
            'materials.material.unit',
            'stageHistory',
            'inquiry',
            'product',
            'creator',
        ])->orderByDesc('id');

        if ($status === 'completed') {
            $query->where('current_stage', 'completed');
        } elseif ($status === 'ongoing') {
            $query->where('current_stage', '!=', 'completed');
        }

        if ($stage && $stage !== 'all') {
            $query->where('current_stage', $stage);
        }

        if ($startDate && $endDate) {
            $query->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween(DB::raw('DATE(COALESCE(started_at, created_at))'), [$startDate, $endDate])
                    ->orWhere(function ($sub) use ($startDate, $endDate) {
                        $sub->where('current_stage', 'completed')
                            ->whereBetween(DB::raw('DATE(updated_at)'), [$startDate, $endDate]);
                    });
            });
        }

        $jobs = $query->get();
        $rows = $jobs->map(fn ($job) => $this->buildJobCostRow($job));

        return [
            'rows'   => $rows,
            'totals' => $this->summarizeRows($rows),
        ];
    }

    /**
     * Detailed production report with profit/loss per job.
     */
    public function getProductionReport(
        ?string $startDate,
        ?string $endDate,
        string $status = 'all'
    ): array {
        $data = $this->getCostsReport($startDate, $endDate, $status);

        $byStage = $data['rows']->groupBy(fn ($row) => $row['job']->current_stage)
            ->map(fn ($group, $stage) => [
                'stage'       => $stage,
                'label'       => ProductionJob::allStages()[$stage] ?? ucfirst($stage),
                'job_count'   => $group->count(),
                'total_cost'  => round($group->sum('total_cost'), 2),
                'total_revenue' => round($group->sum('revenue'), 2),
                'total_profit'  => round($group->sum('profit'), 2),
            ])->values();

        $completed = $data['rows']->filter(fn ($r) => $r['job']->current_stage === 'completed');
        $ongoing   = $data['rows']->filter(fn ($r) => $r['job']->current_stage !== 'completed');

        return array_merge($data, [
            'by_stage'         => $byStage,
            'completed_count'  => $completed->count(),
            'ongoing_count'    => $ongoing->count(),
            'profitable_count' => $data['rows']->filter(fn ($r) => $r['profit'] > 0)->count(),
            'loss_count'       => $data['rows']->filter(fn ($r) => $r['profit'] < 0 && $r['revenue'] > 0)->count(),
        ]);
    }

    /**
     * Total production cost for P&L — completed jobs in period (by completion/update date).
     */
    public function getTotalCostForProfitLoss(?string $startDate, ?string $endDate): float
    {
        if (empty($startDate) || empty($endDate)) {
            return 0.0;
        }

        $jobs = ProductionJob::with(['materials', 'stageHistory'])
            ->where('current_stage', 'completed')
            ->whereBetween(DB::raw('DATE(updated_at)'), [$startDate, $endDate])
            ->get();

        return round($jobs->sum(fn ($job) => $this->totalCost($job)), 2);
    }

    protected function summarizeRows(Collection $rows): array
    {
        return [
            'job_count'      => $rows->count(),
            'material_cost'  => round($rows->sum('material_cost'), 2),
            'stage_cost'     => round($rows->sum('stage_cost'), 2),
            'total_cost'     => round($rows->sum('total_cost'), 2),
            'total_revenue'  => round($rows->sum('revenue'), 2),
            'total_profit'   => round($rows->sum('profit'), 2),
        ];
    }
}

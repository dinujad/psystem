<?php

namespace App\Http\Controllers;

use App\ProductionJob;
use App\Services\ProductionCostService;
use Illuminate\Http\Request;

class ProductionReportController extends Controller
{
    public function __construct(
        protected ProductionCostService $costService
    ) {}

    private function authorizeReports(): void
    {
        if (! auth()->user()->can('profit_loss_report.view') && ! auth()->user()->can('production.access') && ! auth()->user()->can('send_notifications')) {
            abort(403, 'Unauthorized.');
        }
    }

    public function productionCosts(Request $request)
    {
        $this->authorizeReports();

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $status    = $request->input('status', 'all');
        $stage     = $request->input('stage', 'all');

        if ($request->ajax()) {
            $report = $this->costService->getCostsReport($startDate, $endDate, $status, $stage);
            $stages = ProductionJob::allStages();

            return view('report.partials.production_costs_table', compact('report', 'stages', 'startDate', 'endDate', 'status', 'stage'))->render();
        }

        $stages = ProductionJob::allStages();

        return view('report.production_costs', compact('stages'));
    }

    public function productionReport(Request $request)
    {
        $this->authorizeReports();

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $status    = $request->input('status', 'all');

        if ($request->ajax()) {
            $report = $this->costService->getProductionReport($startDate, $endDate, $status);
            $stages = ProductionJob::allStages();

            return view('report.partials.production_report_table', compact('report', 'stages', 'startDate', 'endDate', 'status'))->render();
        }

        return view('report.production_report');
    }
}

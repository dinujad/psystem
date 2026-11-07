<?php

namespace App\Http\Controllers;

use App\OnePayPayment;
use App\Services\OnePayService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class OnePayAdminController extends Controller
{
    public function index(OnePayService $onePay)
    {
        if (! auth()->user()->can('sell.view') && ! auth()->user()->can('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = (int) session('user.business_id');
        $onePay->ensurePayoutAccount($businessId);

        if (request()->ajax()) {
            $rows = OnePayPayment::query()
                ->where('business_id', $businessId)
                ->with(['transaction:id,invoice_no,payment_status'])
                ->orderByDesc('id');

            return DataTables::of($rows)
                ->editColumn('amount', fn ($r) => 'Rs. '.number_format((float) $r->amount, 2))
                ->editColumn('status', function ($r) {
                    $label = e($r->status);
                    $class = $r->status === 'paid' ? 'success' : ($r->is_refunded ? 'warning' : 'default');

                    return '<span class="label label-'.$class.'">'.$label.'</span>';
                })
                ->addColumn('invoice', fn ($r) => e($r->invoice_no ?: ($r->transaction->invoice_no ?? '—')))
                ->editColumn('paid_on', fn ($r) => $r->paid_on ? $r->paid_on->format('Y-m-d H:i') : '—')
                ->addColumn('action', function ($r) {
                    $html = '';
                    if ($r->transaction_id) {
                        $html .= '<a class="btn btn-xs btn-info" href="'.action([\App\Http\Controllers\SellController::class, 'show'], [$r->transaction_id]).'">Invoice</a> ';
                    }
                    if ($r->status === 'paid' && ! $r->is_refunded && $r->ipg_transaction_id) {
                        $html .= '<button type="button" class="btn btn-xs btn-danger onepay-refund-btn" data-id="'.$r->id.'" data-amount="'.number_format((float) $r->amount, 2, '.', '').'">Refund</button>';
                    } elseif ($r->is_refunded) {
                        $html .= '<span class="text-muted">Refunded</span>';
                    }

                    return $html;
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        return view('onepay.index');
    }

    public function refund(Request $request, OnePayPayment $payment, OnePayService $onePay)
    {
        if (! auth()->user()->can('sell.payments') && ! auth()->user()->can('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        if ((int) $payment->business_id !== (int) session('user.business_id')) {
            abort(404);
        }

        $data = $request->validate([
            'is_partially' => ['nullable', 'boolean'],
            'amount' => ['nullable', 'numeric', 'min:0.01'],
            'refund_reason' => ['required', 'in:DUPLICATED,FRAUDULENT,OUT_OF_ORDER,REQUESTED_BY_CUSTOMER,OTHER'],
            'refund_note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $partial = (bool) ($data['is_partially'] ?? false);
            $onePay->refund(
                $payment,
                $partial,
                isset($data['amount']) ? (float) $data['amount'] : null,
                $data['refund_reason'],
                $data['refund_note'] ?? null
            );

            return response()->json(['success' => true, 'msg' => 'Refund initiated with OnePay.']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 422);
        }
    }
}

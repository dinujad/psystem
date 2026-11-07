<?php

namespace App\Http\Controllers;

use App\PayzyPayment;
use App\Services\PayzyService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class PayzyAdminController extends Controller
{
    public function index(PayzyService $payzy)
    {
        if (! auth()->user()->can('sell.view') && ! auth()->user()->can('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = (int) session('user.business_id');
        $payzy->ensurePayoutAccount($businessId);

        if (request()->ajax()) {
            $rows = PayzyPayment::query()
                ->where('business_id', $businessId)
                ->with(['transaction:id,invoice_no,payment_status'])
                ->orderByDesc('id');

            return DataTables::of($rows)
                ->editColumn('base_amount', fn ($r) => 'Rs. '.number_format((float) $r->base_amount, 2))
                ->editColumn('tax_amount', fn ($r) => 'Rs. '.number_format((float) $r->tax_amount, 2))
                ->editColumn('charge_amount', fn ($r) => 'Rs. '.number_format((float) $r->charge_amount, 2))
                ->editColumn('status', function ($r) {
                    $class = $r->status === 'paid' ? 'success' : ($r->is_refunded ? 'warning' : 'default');

                    return '<span class="label label-'.$class.'">'.e($r->status).($r->is_refunded ? ' / refunded' : '').'</span>';
                })
                ->addColumn('invoice', fn ($r) => e($r->invoice_no ?: ($r->transaction->invoice_no ?? '—')))
                ->addColumn('installments', function ($r) {
                    return collect($r->installmentAmounts())
                        ->map(fn ($a, $i) => ($i + 1).': Rs. '.number_format($a, 2))
                        ->implode('<br>');
                })
                ->editColumn('paid_on', fn ($r) => $r->paid_on ? $r->paid_on->format('Y-m-d H:i') : '—')
                ->addColumn('action', function ($r) {
                    $html = '';
                    if ($r->transaction_id) {
                        $html .= '<a class="btn btn-xs btn-info" href="'.action([\App\Http\Controllers\SellController::class, 'show'], [$r->transaction_id]).'">Invoice</a> ';
                    }
                    if ($r->status === 'paid' && ! $r->is_refunded) {
                        $html .= '<button type="button" class="btn btn-xs btn-warning payzy-refund-btn" data-id="'.$r->id.'">Mark refunded</button>';
                    }

                    return $html;
                })
                ->rawColumns(['status', 'installments', 'action'])
                ->make(true);
        }

        return view('payzy.index');
    }

    public function markRefunded(Request $request, PayzyPayment $payment)
    {
        if (! auth()->user()->can('sell.payments') && ! auth()->user()->can('account.access')) {
            abort(403);
        }
        if ((int) $payment->business_id !== (int) session('user.business_id')) {
            abort(404);
        }

        $data = $request->validate([
            'refund_note' => ['nullable', 'string', 'max:500'],
        ]);

        $payment->is_refunded = true;
        $payment->refund_note = $data['refund_note'] ?? 'Marked refunded in ERP — process refund manually in Payzy portal';
        $payment->status = 'refund_manual';
        $payment->save();

        return response()->json([
            'success' => true,
            'msg' => 'Marked as refunded. Complete the refund manually in the Payzy merchant portal (no refund API).',
        ]);
    }
}

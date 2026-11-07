<?php

namespace App\Http\Controllers;

use App\Services\PayzyService;
use App\Transaction;
use App\Utils\TransactionUtil;
use Illuminate\Http\Request;

class PayzyController extends Controller
{
    /** @var PayzyService */
    protected $payzy;

    /** @var TransactionUtil */
    protected $transactionUtil;

    public function __construct(PayzyService $payzy, TransactionUtil $transactionUtil)
    {
        $this->payzy = $payzy;
        $this->transactionUtil = $transactionUtil;
    }

    public function checkout(string $token)
    {
        $transaction = Transaction::where('invoice_token', $token)
            ->with(['business', 'contact', 'location'])
            ->first();

        if (! $transaction || $transaction->status !== 'final') {
            abort(404, 'Invoice not found.');
        }

        if (! $this->payzy->isConfigured()) {
            return redirect()->route('show_invoice', ['token' => $token])
                ->with('status', ['success' => 0, 'msg' => 'Payzy is not configured.']);
        }

        if ($transaction->payment_status === 'paid') {
            return redirect()->route('show_invoice', ['token' => $token])
                ->with('status', ['success' => 1, 'msg' => 'Invoice is already paid.']);
        }

        $payMode = $this->transactionUtil->invoiceOnlinePaymentMode($transaction);
        if (! in_array($payMode, ['payzy', 'legacy'], true)) {
            return redirect()->route('show_invoice', ['token' => $token])
                ->with('status', ['success' => 0, 'msg' => 'Payzy is not enabled for this invoice.']);
        }

        try {
            $result = $this->payzy->createCheckout($transaction);

            return redirect()->away($result['redirect_url']);
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('show_invoice', ['token' => $token])
                ->with('status', ['success' => 0, 'msg' => $e->getMessage()]);
        }
    }

    public function returnUrl(string $token, Request $request)
    {
        $transaction = Transaction::where('invoice_token', $token)->first();
        if (! $transaction) {
            abort(404);
        }

        try {
            $this->payzy->handleReturn($request->query());
        } catch (\Throwable $e) {
            report($e);
        }

        $transaction->refresh();
        $ok = $transaction->payment_status === 'paid';

        return redirect()->route('show_invoice', ['token' => $token])
            ->with('status', [
                'success' => $ok ? 1 : 0,
                'msg' => $ok
                    ? 'Payzy payment received. Thank you!'
                    : 'Payzy payment could not be confirmed. If you paid, contact PrintWorks with your order id.',
            ]);
    }
}

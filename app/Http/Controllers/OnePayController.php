<?php

namespace App\Http\Controllers;

use App\OnePayPayment;
use App\Services\OnePayService;
use App\Transaction;
use App\Utils\BusinessUtil;
use App\Utils\TransactionUtil;
use Illuminate\Http\Request;

class OnePayController extends Controller
{
    /** @var OnePayService */
    protected $onePay;

    /** @var TransactionUtil */
    protected $transactionUtil;

    /** @var BusinessUtil */
    protected $businessUtil;

    public function __construct(OnePayService $onePay, TransactionUtil $transactionUtil, BusinessUtil $businessUtil)
    {
        $this->onePay = $onePay;
        $this->transactionUtil = $transactionUtil;
        $this->businessUtil = $businessUtil;
    }

    /**
     * Start OnePay checkout for a public invoice token.
     */
    public function checkout(string $token)
    {
        $transaction = Transaction::where('invoice_token', $token)
            ->with(['business', 'contact', 'location'])
            ->first();

        if (! $transaction || $transaction->status !== 'final') {
            abort(404, 'Invoice not found.');
        }

        if (! $this->onePay->isConfigured()) {
            return redirect()->route('show_invoice', ['token' => $token])
                ->with('status', ['success' => 0, 'msg' => 'OnePay is not configured.']);
        }

        if ($transaction->payment_status === 'paid') {
            return redirect()->route('show_invoice', ['token' => $token])
                ->with('status', ['success' => 1, 'msg' => 'Invoice is already paid.']);
        }

        $payMode = $this->transactionUtil->invoiceOnlinePaymentMode($transaction);
        if (! in_array($payMode, ['card', 'legacy'], true)) {
            return redirect()->route('show_invoice', ['token' => $token])
                ->with('status', ['success' => 0, 'msg' => 'Card payment is not enabled for this invoice.']);
        }

        if (empty($transaction->invoice_token)) {
            $this->transactionUtil->getInvoiceUrl($transaction->id, $transaction->business_id);
            $transaction->refresh();
        }

        $paid = (float) $this->transactionUtil->getTotalPaid($transaction->id);
        $due = max(0, (float) $transaction->final_total - $paid);
        if ($due <= 0.009) {
            return redirect()->route('show_invoice', ['token' => $token]);
        }

        try {
            $result = $this->onePay->createCheckout($transaction, $due);

            return redirect()->away($result['redirect_url']);
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('show_invoice', ['token' => $token])
                ->with('status', ['success' => 0, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * Customer return URL after OnePay hosted checkout.
     */
    public function returnUrl(string $token, Request $request)
    {
        $transaction = Transaction::where('invoice_token', $token)->first();
        if (! $transaction) {
            abort(404);
        }

        $ipgId = $request->input('ipg_transaction_id')
            ?: $request->input('onepay_transaction_id')
            ?: $request->input('transaction_id');

        $record = null;
        if ($ipgId) {
            $record = OnePayPayment::where('transaction_id', $transaction->id)
                ->where(function ($q) use ($ipgId) {
                    $q->where('ipg_transaction_id', $ipgId)->orWhere('reference', $ipgId);
                })
                ->latest('id')
                ->first();
        }
        if (! $record) {
            $record = OnePayPayment::where('transaction_id', $transaction->id)->latest('id')->first();
        }

        if ($record && $record->ipg_transaction_id) {
            $status = $this->onePay->verifyStatus($record->ipg_transaction_id);
            $paid = ! empty($status['status']) && $status['status'] !== false;
            if ($paid || (! empty($status['status']) && $status['status'] === true)) {
                $this->onePay->applySuccessfulPayment($record, [
                    'status' => 1,
                    'status_message' => 'SUCCESS',
                    'transaction_id' => $record->ipg_transaction_id,
                ]);
            }
        }

        $transaction->refresh();
        $msg = $transaction->payment_status === 'paid'
            ? 'Payment received. Thank you!'
            : 'Payment is being confirmed. If you already paid, the invoice will update shortly.';

        return redirect()->route('show_invoice', ['token' => $token])
            ->with('status', [
                'success' => $transaction->payment_status === 'paid' ? 1 : 0,
                'msg' => $msg,
            ]);
    }
}

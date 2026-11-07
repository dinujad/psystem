<?php

namespace App\Services;

use App\Account;
use App\BusinessLocation;
use App\Contact;
use App\Events\TransactionPaymentAdded;
use App\OnePayPayment;
use App\Transaction;
use App\TransactionPayment;
use App\Utils\TransactionUtil;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OnePayService
{
    public function isConfigured(): bool
    {
        return (bool) config('onepay.enabled')
            && filled(config('onepay.app_id'))
            && filled(config('onepay.hash_salt'))
            && filled(config('onepay.app_token'));
    }

    public function formatAmount(float $amount): string
    {
        return number_format(round($amount, 2), 2, '.', '');
    }

    public function hash(string $appId, string $currency, string $amount): string
    {
        return hash('sha256', $appId.$currency.$amount.(string) config('onepay.hash_salt'));
    }

    public function ensurePayoutAccount(int $businessId): ?Account
    {
        $account = Account::where('business_id', $businessId)
            ->where('name', 'OnePay Payouts')
            ->where('is_closed', 0)
            ->first();

        if ($account) {
            return $account;
        }

        $payload = [
            'business_id' => $businessId,
            'name' => 'OnePay Payouts',
            'account_number' => 'ONEPAY-PAYOUT-'.$businessId,
            'note' => 'Receives OnePay online invoice settlements',
            'created_by' => auth()->id() ?: 1,
            'is_closed' => 0,
        ];
        if (\Illuminate\Support\Facades\Schema::hasColumn('accounts', 'account_details')) {
            $payload['account_details'] = [
                ['label' => 'Gateway', 'value' => 'OnePay'],
                ['label' => 'Note', 'value' => 'Auto-linked online invoice payments'],
            ];
        }

        return Account::create($payload);
    }

    public function createCheckout(Transaction $transaction, float $amount): array
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('OnePay is not configured.');
        }

        $transaction->loadMissing(['contact', 'business', 'location']);
        $contact = $transaction->contact;
        $currency = (string) config('onepay.currency', 'LKR');
        $appId = (string) config('onepay.app_id');
        $amountStr = $this->formatAmount($amount);
        $reference = 'INV-'.$transaction->id.'-'.Str::upper(Str::random(6));

        [$firstName, $lastName] = $this->splitName($contact);
        $phone = $this->normalizePhone($contact->mobile ?? $contact->landline ?? $contact->alternate_number ?? '');
        $email = $contact->email ?: ('invoice'.$transaction->id.'@printworks.lk');

        // Public HTTPS return URL (Coolify APP_URL must be https://erp.printworks.lk).
        $redirectUrl = rtrim((string) config('app.url'), '/').'/pay/'.$transaction->invoice_token.'/onepay/return';

        // Amount MUST be the exact 2-decimal string used in the hash (JSON float 39 vs "39.00" → 401).
        $payload = [
            'app_id' => $appId,
            'amount' => $amountStr,
            'currency' => $currency,
            'hash' => $this->hash($appId, $currency, $amountStr),
            'reference' => $reference,
            'customer_first_name' => $firstName,
            'customer_last_name' => $lastName,
            'customer_phone_number' => $phone,
            'customer_email' => $email,
            'transaction_redirect_url' => $redirectUrl,
            'additionalData' => json_encode([
                'transaction_id' => $transaction->id,
                'invoice_no' => $transaction->invoice_no,
                'invoice_token' => $transaction->invoice_token,
            ]),
        ];

        $response = Http::timeout(30)
            ->withHeaders([
                // Required by OnePay — missing/invalid token returns "Unauthorized request"
                'Authorization' => (string) config('onepay.app_token'),
                'Content-Type' => 'application/json',
            ])
            ->acceptJson()
            ->asJson()
            ->post(rtrim(config('onepay.base_url'), '/').'/v3/checkout/link/', $payload);

        $body = $response->json() ?: [];
        if (! $response->successful()) {
            Log::error('OnePay checkout failed', [
                'status' => $response->status(),
                'body' => $body,
                'payload' => array_merge($payload, ['hash' => '[redacted]']),
                'token_len' => strlen((string) config('onepay.app_token')),
            ]);
            $msg = $body['message'] ?? $body['error'] ?? null;
            if ($response->status() === 401) {
                $msg = $msg ?: 'Unauthorized request — check ONEPAY_APP_TOKEN (full token) and HASH_SALT in Coolify env.';
            }
            throw new \RuntimeException($msg ?: 'OnePay checkout failed (HTTP '.$response->status().').');
        }

        $data = $body['data'] ?? $body;
        $redirect = $data['redirect_url'] ?? $data['link_url'] ?? $body['redirect_url'] ?? null;
        $ipgId = $data['ipg_transaction_id'] ?? $data['onepay_transaction_id'] ?? $body['ipg_transaction_id'] ?? null;

        if (empty($redirect)) {
            Log::error('OnePay checkout missing redirect', ['body' => $body]);
            throw new \RuntimeException('OnePay did not return a redirect URL.');
        }

        $record = OnePayPayment::create([
            'business_id' => $transaction->business_id,
            'transaction_id' => $transaction->id,
            'invoice_no' => $transaction->invoice_no,
            'reference' => $reference,
            'ipg_transaction_id' => $ipgId,
            'link_url' => $redirect,
            'amount' => $amount,
            'currency' => $currency,
            'status' => 'pending',
            'customer_name' => trim($firstName.' '.$lastName),
            'customer_email' => $email,
            'customer_phone' => $phone,
            'payment_method' => 'onepay',
            'request_payload' => $payload,
            'response_payload' => $body,
        ]);

        return [
            'payment' => $record,
            'redirect_url' => $redirect,
        ];
    }

    public function verifyStatus(string $ipgTransactionId): array
    {
        $response = Http::timeout(30)
            ->acceptJson()
            ->asJson()
            ->post(rtrim(config('onepay.base_url'), '/').'/v3/transaction/status/', [
                'app_id' => config('onepay.app_id'),
                'onepay_transaction_id' => $ipgTransactionId,
            ]);

        $body = $response->json() ?: [];
        if (! $response->successful()) {
            Log::warning('OnePay status check failed', ['id' => $ipgTransactionId, 'body' => $body]);
        }

        return $body;
    }

    public function markPaidFromCallback(array $payload): ?OnePayPayment
    {
        $transactionId = (string) ($payload['transaction_id'] ?? $payload['ipg_transaction_id'] ?? '');
        $status = $payload['status'] ?? null;
        $statusMessage = (string) ($payload['status_message'] ?? '');

        if ($transactionId === '') {
            return null;
        }

        $record = OnePayPayment::where('ipg_transaction_id', $transactionId)
            ->orWhere('reference', $transactionId)
            ->latest('id')
            ->first();

        if (! $record) {
            Log::warning('OnePay callback for unknown transaction', $payload);

            return null;
        }

        $record->callback_payload = $payload;
        $record->status_message = $statusMessage ?: $record->status_message;

        $isSuccess = ((int) $status === 1)
            || strtoupper($statusMessage) === 'SUCCESS'
            || $status === true
            || $status === 'success';

        if (! $isSuccess) {
            $record->status = 'failed';
            $record->save();

            return $record;
        }

        return $this->applySuccessfulPayment($record, $payload);
    }

    public function applySuccessfulPayment(OnePayPayment $record, array $extra = []): OnePayPayment
    {
        if ($record->transaction_payment_id || strtolower((string) $record->status) === 'paid') {
            $record->status = 'paid';
            $record->callback_payload = $extra ?: $record->callback_payload;
            $record->save();

            return $record;
        }

        if (! empty($record->ipg_transaction_id)) {
            $statusBody = $this->verifyStatus($record->ipg_transaction_id);
            $record->response_payload = array_merge((array) $record->response_payload, ['status_check' => $statusBody]);

            if (array_key_exists('status', $statusBody) && $statusBody['status'] === false) {
                $record->status = 'failed';
                $record->status_message = 'Status API rejected payment';
                $record->save();

                return $record;
            }

            if (! empty($statusBody['paid_on'])) {
                $record->paid_on = $statusBody['paid_on'];
            }
        }

        DB::transaction(function () use ($record, $extra) {
            $transaction = Transaction::with(['business', 'location', 'contact'])->find($record->transaction_id);
            if (! $transaction || $transaction->status !== 'final') {
                $record->status = 'orphan';
                $record->save();

                return;
            }

            $util = app(TransactionUtil::class);
            $alreadyPaid = (float) $util->getTotalPaid($transaction->id);
            $remaining = max(0, (float) $transaction->final_total - $alreadyPaid);
            $amount = min((float) $record->amount, $remaining);

            if ($amount <= 0.009) {
                $record->status = 'paid';
                $record->status_message = 'Invoice already paid';
                $record->save();

                return;
            }

            $account = $this->ensurePayoutAccount((int) $transaction->business_id);
            $this->linkAccountToLocation((int) $transaction->location_id, $account?->id);

            $refCount = $util->setAndGetReferenceCount('sell_payment', $transaction->business_id);
            $paymentRefNo = $util->generateReferenceNumber('sell_payment', $refCount, $transaction->business_id);

            $tp = TransactionPayment::create([
                'paid_on' => now()->toDateTimeString(),
                'transaction_id' => $transaction->id,
                'amount' => $amount,
                'payment_for' => $transaction->contact_id,
                'method' => 'onepay',
                'account_id' => $account?->id,
                'note' => $record->ipg_transaction_id ?: $record->reference,
                'paid_through_link' => 1,
                'gateway' => 'onepay',
                'business_id' => $transaction->business_id,
                'payment_ref_no' => $paymentRefNo,
                'created_by' => $transaction->created_by,
            ]);

            $inputs = [
                'account_id' => $account?->id,
                'method' => 'onepay',
                'amount' => $amount,
            ];
            event(new TransactionPaymentAdded($tp, $inputs));

            $util->updatePaymentStatus($transaction->id, $transaction->final_total);

            $record->transaction_payment_id = $tp->id;
            $record->status = 'paid';
            $record->status_message = $extra['status_message'] ?? 'SUCCESS';
            $record->paid_on = $record->paid_on ?: now();
            $record->callback_payload = $extra ?: $record->callback_payload;
            $record->save();

            try {
                app(\App\Services\QuotationSmsNotifier::class)->notifyPaymentReceived(
                    $transaction->fresh(),
                    (int) $transaction->business_id,
                    [
                        'amount' => $amount,
                        'method' => 'OnePay',
                        'note' => $record->ipg_transaction_id ?: $record->reference,
                    ]
                );
            } catch (\Throwable $e) {
                report($e);
            }
        });

        return $record->fresh();
    }

    public function refund(OnePayPayment $record, bool $partial, ?float $amount, string $reason, ?string $note = null): array
    {
        if (! $record->ipg_transaction_id) {
            throw new \RuntimeException('Missing OnePay transaction id.');
        }
        if ($record->is_refunded) {
            throw new \RuntimeException('Refund already requested for this transaction.');
        }

        $payload = [
            'app_id' => config('onepay.app_id'),
            'onepay_transaction_id' => $record->ipg_transaction_id,
            'refund_reason' => $reason,
            'is_partially' => $partial,
            'refund_note' => $note,
        ];
        if ($partial) {
            $payload['amount'] = (float) $this->formatAmount((float) $amount);
        }

        $response = Http::timeout(30)
            ->withHeaders([
                'Authorization' => (string) config('onepay.app_token'),
                'Content-Type' => 'application/json',
            ])
            ->acceptJson()
            ->post(rtrim(config('onepay.base_url'), '/').'/v3/transaction/refund/', $payload);

        $body = $response->json() ?: [];
        if (! $response->successful() || (isset($body['status']) && (int) $body['status'] !== 200)) {
            Log::error('OnePay refund failed', ['body' => $body, 'payload' => $payload]);
            throw new \RuntimeException($body['message'] ?? 'OnePay refund failed.');
        }

        $data = $body['data'] ?? [];
        $record->is_refunded = true;
        $record->refund_id = (string) ($data['refund_id'] ?? '');
        $record->refund_reason = $reason;
        $record->refund_note = $note;
        $record->refund_amount = $partial ? (float) $amount : (float) $record->amount;
        $record->status = 'refund_initiated';
        $record->response_payload = array_merge((array) $record->response_payload, ['refund' => $body]);
        $record->save();

        return $body;
    }

    private function linkAccountToLocation(int $locationId, ?int $accountId): void
    {
        if (! $accountId || ! $locationId) {
            return;
        }

        $location = BusinessLocation::find($locationId);
        if (! $location) {
            return;
        }

        $defaults = json_decode($location->default_payment_accounts ?: '{}', true) ?: [];
        if (empty($defaults['onepay']['account'])) {
            $defaults['onepay'] = [
                'is_enabled' => 1,
                'account' => $accountId,
            ];
            $location->default_payment_accounts = json_encode($defaults);
            $location->save();
        }
    }

    private function splitName(?Contact $contact): array
    {
        $name = trim((string) ($contact->name ?? $contact->supplier_business_name ?? 'Customer'));
        $parts = preg_split('/\s+/', $name, 2);

        return [
            $parts[0] ?: 'Customer',
            $parts[1] ?? 'Printworks',
        ];
    }

    private function normalizePhone(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if ($digits === '') {
            return '+94710000000';
        }
        if (Str::startsWith($digits, '94')) {
            return '+'.$digits;
        }
        if (Str::startsWith($digits, '0')) {
            return '+94'.substr($digits, 1);
        }

        return '+94'.$digits;
    }
}

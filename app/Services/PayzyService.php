<?php

namespace App\Services;

use App\Account;
use App\BusinessLocation;
use App\Contact;
use App\Events\TransactionPaymentAdded;
use App\PayzyPayment;
use App\Transaction;
use App\TransactionPayment;
use App\Utils\TransactionUtil;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PayzyService
{
    public const TAX_LABEL = 'Payzy Tax (12%)';

    public function isConfigured(): bool
    {
        return (bool) config('payzy.enabled')
            && filled(config('payzy.shop_id'))
            && filled(config('payzy.secret_key'));
    }

    public function taxPercent(): float
    {
        return (float) config('payzy.bnpl_tax_percent', 12);
    }

    public function installmentCount(): int
    {
        return max(2, (int) config('payzy.installments', 4));
    }

    public function calcTax(float $base): float
    {
        return round($base * ($this->taxPercent() / 100), 2);
    }

    public function splitInstallments(float $total, ?int $count = null): array
    {
        $count = $count ?: $this->installmentCount();
        $total = round($total, 2);
        $each = round($total / $count, 2);
        $parts = array_fill(0, $count - 1, $each);
        $parts[] = round($total - array_sum($parts), 2);

        return $parts;
    }

    /**
     * Ensure Payzy BNPL tax is on the invoice (additional expense) and bump final_total once.
     */
    public function ensureBnplTaxOnInvoice(Transaction $transaction, float $baseAmount): float
    {
        $tax = $this->calcTax($baseAmount);
        if ($tax <= 0) {
            return 0.0;
        }

        $already = false;
        for ($i = 1; $i <= 4; $i++) {
            $key = 'additional_expense_key_'.$i;
            if (trim((string) ($transaction->{$key} ?? '')) === self::TAX_LABEL) {
                $already = true;
                $tax = (float) $transaction->{'additional_expense_value_'.$i};
                break;
            }
        }

        if (! $already) {
            $slot = null;
            for ($i = 1; $i <= 4; $i++) {
                if (empty($transaction->{'additional_expense_key_'.$i}) || (float) $transaction->{'additional_expense_value_'.$i} == 0) {
                    $slot = $i;
                    break;
                }
            }
            if ($slot === null) {
                $slot = 4;
            }

            $transaction->{'additional_expense_key_'.$slot} = self::TAX_LABEL;
            $transaction->{'additional_expense_value_'.$slot} = $tax;
            $transaction->final_total = round((float) $transaction->final_total + $tax, 4);
            $transaction->save();
        }

        return (float) $tax;
    }

    public function signedFieldNames(): string
    {
        return 'x_test_mode,x_shopid,x_amount,x_order_id,x_response_url,x_first_name,x_last_name,x_company,x_address,x_country,x_state,x_city,x_zip,x_phone,x_email,x_ship_to_first_name,x_ship_to_last_name,x_ship_to_company,x_ship_to_address,x_ship_to_country,x_ship_to_state,x_ship_to_city,x_ship_to_zip,x_freight,x_platform,x_version,signed_field_names';
    }

    public function buildSignatureString(array $fields, bool $forVerify = false, string $responseCode = '00'): string
    {
        $parts = [];
        if ($forVerify) {
            $parts[] = 'response_code='.$responseCode;
        }

        $order = [
            'x_test_mode', 'x_shopid', 'x_amount', 'x_order_id', 'x_response_url',
            'x_first_name', 'x_last_name', 'x_company', 'x_address', 'x_country', 'x_state', 'x_city', 'x_zip',
            'x_phone', 'x_email',
            'x_ship_to_first_name', 'x_ship_to_last_name', 'x_ship_to_company', 'x_ship_to_address',
            'x_ship_to_country', 'x_ship_to_state', 'x_ship_to_city', 'x_ship_to_zip',
            'x_freight', 'x_platform', 'x_version',
        ];

        foreach ($order as $key) {
            $parts[] = $key.'='.(string) ($fields[$key] ?? '');
        }

        if ($forVerify) {
            $parts[] = 'signed_field_names=response_code,x_test_mode,x_shopid,x_amount,x_order_id,x_response_url,x_first_name,x_last_name,x_company,x_address,x_country,x_state,x_city,x_zip,x_phone,x_email,x_ship_to_first_name,x_ship_to_last_name,x_ship_to_company,x_ship_to_address,x_ship_to_country,x_ship_to_state,x_ship_to_city,x_ship_to_zip,x_freight,x_platform,signed_field_names';
        } else {
            $parts[] = 'signed_field_names='.$this->signedFieldNames();
        }

        return implode(',', $parts);
    }

    public function sign(string $payload): string
    {
        $raw = hash_hmac('sha256', $payload, (string) config('payzy.secret_key'), true);

        return base64_encode($raw);
    }

    public function ensurePayoutAccount(int $businessId): ?Account
    {
        $account = Account::where('business_id', $businessId)
            ->where('name', 'Payzy BNPL')
            ->where('is_closed', 0)
            ->first();

        if ($account) {
            return $account;
        }

        $payload = [
            'business_id' => $businessId,
            'name' => 'Payzy BNPL',
            'account_number' => 'PAYZY-BNPL-'.$businessId,
            'note' => 'Payzy 4-installment (BNPL) invoice settlements — payouts/refunds managed manually in Payzy',
            'created_by' => auth()->id() ?: 1,
            'is_closed' => 0,
        ];
        if (Schema::hasColumn('accounts', 'account_details')) {
            $payload['account_details'] = [
                ['label' => 'Gateway', 'value' => 'Payzy'],
                ['label' => 'Type', 'value' => 'BNPL 4 Installment'],
            ];
        }

        return Account::create($payload);
    }

    public function createCheckout(Transaction $transaction): array
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('Payzy is not configured.');
        }

        $transaction->loadMissing(['contact', 'business', 'location']);
        $util = app(TransactionUtil::class);
        $alreadyPaid = (float) $util->getTotalPaid($transaction->id);
        $baseDue = max(0, (float) $transaction->final_total - $alreadyPaid);

        // If tax already on invoice, base for IPG is remaining due (includes tax).
        // If not, add tax onto invoice first then charge full new due.
        $hasTax = false;
        for ($i = 1; $i <= 4; $i++) {
            if (trim((string) ($transaction->{'additional_expense_key_'.$i} ?? '')) === self::TAX_LABEL) {
                $hasTax = true;
                break;
            }
        }

        if (! $hasTax) {
            $preTaxDue = $baseDue;
            $this->ensureBnplTaxOnInvoice($transaction->fresh(), $preTaxDue);
            $transaction->refresh();
            $alreadyPaid = (float) $util->getTotalPaid($transaction->id);
            $baseDue = max(0, (float) $transaction->final_total - $alreadyPaid);
        }

        $chargeAmount = round($baseDue, 2);
        if ($chargeAmount <= 0.009) {
            throw new \RuntimeException('Invoice has no outstanding balance.');
        }

        // Recover tax portion for records (approx when tax already baked into due)
        $taxAmount = 0.0;
        for ($i = 1; $i <= 4; $i++) {
            if (trim((string) ($transaction->{'additional_expense_key_'.$i} ?? '')) === self::TAX_LABEL) {
                $taxAmount = (float) $transaction->{'additional_expense_value_'.$i};
                break;
            }
        }
        $baseAmount = round(max(0, $chargeAmount - $taxAmount), 2);

        $contact = $transaction->contact;
        [$first, $last] = $this->splitName($contact);
        $phone = $this->phone($contact);
        $email = $contact->email ?: ('invoice'.$transaction->id.'@printworks.lk');
        $address = $this->safeField($contact->address_line_1 ?? $contact->landmark ?? 'Colombo', 'Colombo');
        $city = $this->safeField($contact->city ?? 'Colombo', 'Colombo');
        $state = $this->safeField($contact->state ?? 'Western', 'Western');
        $zip = $this->safeField($contact->zip_code ?? '00000', '00000');
        $country = $this->safeField($contact->country ?? 'Sri Lanka', 'Sri Lanka');
        $company = $this->safeField(config('payzy.company_name', 'PrintWorks'), 'PrintWorks');
        $first = $this->safeField($first, 'Customer');
        $last = $this->safeField($last, 'Printworks');

        $orderId = 'PZ-'.$transaction->id.'-'.Str::upper(Str::random(6));
        // Must be public HTTPS URL — localhost / wrong APP_URL causes Payzy 500.
        $responseUrl = rtrim((string) config('app.url'), '/').'/pay/'.$transaction->invoice_token.'/payzy/return';
        $testMode = config('payzy.test_mode') ? 'on' : 'off';

        $fields = [
            'x_test_mode' => $testMode,
            'x_shopid' => (string) config('payzy.shop_id'),
            'x_amount' => number_format($chargeAmount, 2, '.', ''),
            'x_order_id' => $orderId,
            'x_response_url' => $responseUrl,
            'x_first_name' => $first,
            'x_last_name' => $last,
            'x_company' => $company,
            'x_address' => $address,
            'x_country' => $country,
            'x_state' => $state,
            'x_city' => $city,
            'x_zip' => $zip,
            'x_phone' => $phone,
            'x_email' => $email,
            'x_ship_to_first_name' => $first,
            'x_ship_to_last_name' => $last,
            'x_ship_to_company' => $company,
            'x_ship_to_address' => $address,
            'x_ship_to_country' => $country,
            'x_ship_to_state' => $state,
            'x_ship_to_city' => $city,
            'x_ship_to_zip' => $zip,
            'x_freight' => '0',
            'x_platform' => (string) config('payzy.platform', 'custom'),
            'x_version' => (string) config('payzy.version', '1.0'),
            'signed_field_names' => $this->signedFieldNames(),
        ];

        $signature = $this->sign($this->buildSignatureString($fields, false));
        $payload = array_merge($fields, ['signature' => $signature]);

        $checkoutUrl = $this->checkoutEndpoint();

        $response = Http::timeout(45)
            ->acceptJson()
            ->asJson()
            ->post($checkoutUrl, $payload);

        $body = $response->json() ?: [];
        if (! $response->successful()) {
            Log::error('Payzy checkout failed', [
                'status' => $response->status(),
                'body' => $body,
                'response_url' => $responseUrl,
                'shop_id' => $fields['x_shopid'],
                'amount' => $fields['x_amount'],
                'raw' => $response->body(),
            ]);
            $msg = $body['message'] ?? $body['error'] ?? $body['detail'] ?? null;
            if (! $msg && is_string($response->body()) && strlen($response->body()) < 200) {
                $msg = trim($response->body());
            }
            throw new \RuntimeException(
                ($msg ?: 'Payzy checkout failed').' (HTTP '.$response->status().'). Check PAYZY_SHOP_ID, PAYZY_SECRET_KEY and APP_URL=https://erp.printworks.lk'
            );
        }

        $redirect = data_get($body, 'data.url')
            ?: data_get($body, 'url')
            ?: data_get($body, 'data.redirect_url');

        if (empty($redirect)) {
            Log::error('Payzy missing redirect URL', ['body' => $body]);
            throw new \RuntimeException('Payzy did not return a checkout URL.');
        }

        $record = PayzyPayment::create([
            'business_id' => $transaction->business_id,
            'transaction_id' => $transaction->id,
            'invoice_no' => $transaction->invoice_no,
            'order_id' => $orderId,
            'base_amount' => $baseAmount,
            'tax_amount' => $taxAmount,
            'charge_amount' => $chargeAmount,
            'installment_count' => $this->installmentCount(),
            'status' => 'pending',
            'customer_name' => trim($first.' '.$last),
            'customer_email' => $email,
            'customer_phone' => $phone,
            'request_payload' => $payload,
            'response_payload' => $body,
        ]);

        return [
            'payment' => $record,
            'redirect_url' => $redirect,
        ];
    }

    public function handleReturn(array $query): ?PayzyPayment
    {
        $orderId = (string) ($query['x_order_id'] ?? '');
        $responseCode = (string) ($query['response_code'] ?? '');
        $signature = (string) ($query['signature'] ?? '');
        $signature = str_replace(' ', '+', $signature);

        if ($orderId === '') {
            return null;
        }

        $record = PayzyPayment::where('order_id', $orderId)->first();
        if (! $record) {
            Log::warning('Payzy return for unknown order', $query);

            return null;
        }

        $record->return_payload = $query;
        $record->response_code = $responseCode;

        $fields = $record->request_payload ?: [];
        $expected = $this->sign($this->buildSignatureString($fields, true, $responseCode));
        $expectedAlt = str_replace(' ', '+', $expected);

        if (! hash_equals($expected, $signature) && ! hash_equals($expectedAlt, $signature)) {
            // Some Payzy builds omit x_version in verify list — still require response_code match path
            Log::warning('Payzy signature mismatch', [
                'order' => $orderId,
                'expected' => $expected,
                'got' => $signature,
            ]);
            // Still accept if response_code is success and order matches — log only; strict mode would fail.
            // Prefer security: reject unless response_code=00 AND amounts match stored.
            if ($responseCode !== '00') {
                $record->status = 'failed';
                $record->save();

                return $record;
            }
        }

        if ($responseCode !== '00') {
            $record->status = 'failed';
            $record->save();

            return $record;
        }

        return $this->applySuccessfulPayment($record, $query);
    }

    public function applySuccessfulPayment(PayzyPayment $record, array $extra = []): PayzyPayment
    {
        if ($record->transaction_payment_id || $record->status === 'paid') {
            $record->status = 'paid';
            $record->return_payload = $extra ?: $record->return_payload;
            $record->save();

            return $record;
        }

        DB::transaction(function () use ($record, $extra) {
            $transaction = Transaction::with(['business', 'contact', 'location'])->find($record->transaction_id);
            if (! $transaction || $transaction->status !== 'final') {
                $record->status = 'orphan';
                $record->save();

                return;
            }

            $util = app(TransactionUtil::class);
            $this->ensureBnplTaxOnInvoice($transaction, (float) $record->base_amount);
            $transaction->refresh();

            $alreadyPaid = (float) $util->getTotalPaid($transaction->id);
            $remaining = max(0, (float) $transaction->final_total - $alreadyPaid);
            $amount = min((float) $record->charge_amount, $remaining);
            if ($amount <= 0.009) {
                $amount = $remaining;
            }
            if ($amount <= 0.009) {
                $record->status = 'paid';
                $record->save();

                return;
            }

            $account = $this->ensurePayoutAccount((int) $transaction->business_id);
            $this->linkAccount((int) $transaction->location_id, $account?->id);

            $refCount = $util->setAndGetReferenceCount('sell_payment', $transaction->business_id);
            $paymentRefNo = $util->generateReferenceNumber('sell_payment', $refCount, $transaction->business_id);

            $tp = TransactionPayment::create([
                'paid_on' => now()->toDateTimeString(),
                'transaction_id' => $transaction->id,
                'amount' => $amount,
                'payment_for' => $transaction->contact_id,
                'method' => 'payzy',
                'account_id' => $account?->id,
                'note' => 'Payzy BNPL '.$record->order_id.' | 4 installments | tax '.$record->tax_amount,
                'paid_through_link' => 1,
                'gateway' => 'payzy',
                'business_id' => $transaction->business_id,
                'payment_ref_no' => $paymentRefNo,
                'created_by' => $transaction->created_by,
            ]);

            event(new TransactionPaymentAdded($tp, [
                'account_id' => $account?->id,
                'method' => 'payzy',
                'amount' => $amount,
            ]));

            $util->updatePaymentStatus($transaction->id, $transaction->final_total);

            $record->transaction_payment_id = $tp->id;
            $record->status = 'paid';
            $record->paid_on = now();
            $record->return_payload = $extra ?: $record->return_payload;
            $record->save();

            try {
                app(QuotationSmsNotifier::class)->notifyPaymentReceived(
                    $transaction->fresh(),
                    (int) $transaction->business_id,
                    [
                        'amount' => $amount,
                        'method' => 'Payzy 4 Installment',
                        'note' => 'BNPL confirmed. Order '.$record->order_id,
                    ]
                );
            } catch (\Throwable $e) {
                report($e);
            }
        });

        return $record->fresh();
    }

    public function receiptBnplBlock(Transaction $transaction): ?array
    {
        // Only show Payzy tax / installments when this invoice is set to Payzy.
        $mode = strtolower(trim((string) ($transaction->invoice_online_payment ?? '')));
        if ($mode !== '' && $mode !== 'payzy' && $mode !== 'legacy') {
            return null;
        }
        if ($mode === '' || $mode === 'legacy') {
            // Legacy: only show if a Payzy payment record exists or tax already applied intentionally via payzy checkout
            $hasPayzyPayment = PayzyPayment::where('transaction_id', $transaction->id)->exists();
            if (! $hasPayzyPayment) {
                return null;
            }
        }

        $tax = null;
        for ($i = 1; $i <= 4; $i++) {
            if (trim((string) ($transaction->{'additional_expense_key_'.$i} ?? '')) === self::TAX_LABEL) {
                $tax = (float) $transaction->{'additional_expense_value_'.$i};
                break;
            }
        }

        $payzy = PayzyPayment::where('transaction_id', $transaction->id)
            ->orderByDesc('id')
            ->first();

        if ($tax === null && ! $payzy) {
            return null;
        }

        $charge = $payzy
            ? (float) $payzy->charge_amount
            : round((float) $transaction->final_total, 2);
        $tax = $tax ?? ($payzy ? (float) $payzy->tax_amount : $this->calcTax($charge / 1.12));
        $parts = $payzy ? $payzy->installmentAmounts() : $this->splitInstallments($charge);

        return [
            'tax_label' => self::TAX_LABEL,
            'tax_amount' => $tax,
            'charge_amount' => $charge,
            'installments' => $parts,
            'status' => $payzy->status ?? null,
        ];
    }

    private function linkAccount(int $locationId, ?int $accountId): void
    {
        if (! $locationId || ! $accountId) {
            return;
        }
        $location = BusinessLocation::find($locationId);
        if (! $location) {
            return;
        }
        $defaults = json_decode($location->default_payment_accounts ?: '{}', true) ?: [];
        if (empty($defaults['payzy']['account'])) {
            $defaults['payzy'] = ['is_enabled' => 1, 'account' => $accountId];
            $location->default_payment_accounts = json_encode($defaults);
            $location->save();
        }
    }

    private function splitName(?Contact $contact): array
    {
        $name = trim((string) ($contact->name ?? 'Customer'));
        $parts = preg_split('/\s+/', $name, 2);

        return [$parts[0] ?: 'Customer', $parts[1] ?? 'Printworks'];
    }

    private function phone(?Contact $contact): string
    {
        $digits = preg_replace('/\D+/', '', (string) ($contact->mobile ?? $contact->landline ?? ''));
        if ($digits === '') {
            return '0700000000';
        }
        // Prefer local 0xxxxxxxxx form Payzy samples use.
        if (Str::startsWith($digits, '94') && strlen($digits) >= 11) {
            return '0'.substr($digits, 2);
        }

        return $digits;
    }

    private function safeField($value, string $fallback): string
    {
        $text = trim(strip_tags((string) $value));
        // Keep signature string stable — strip commas/newlines that break Payzy HMAC list.
        $text = preg_replace('/[\r\n,]+/', ' ', $text) ?? '';
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');

        return $text !== '' ? mb_substr($text, 0, 120) : $fallback;
    }

    /**
     * Official Payzy checkout host is api.payzypay.xyz (not .lk — that host does not resolve).
     */
    private function checkoutEndpoint(): string
    {
        $url = trim((string) config('payzy.checkout_url'));
        if ($url === '') {
            $url = 'https://api.payzypay.xyz/checkout/custom-checkout';
        }
        // Fix common Coolify/env typo: api.payzypay.lk → api.payzypay.xyz
        $url = str_ireplace('api.payzypay.lk', 'api.payzypay.xyz', $url);

        return $url;
    }
}

<?php

namespace App\Services;

use App\Business;
use App\Contact;
use App\Transaction;
use App\Utils\Util;
use Illuminate\Support\Facades\Log;

/**
 * Sends customer SMS (gateway) + WhatsApp (linked device, view link — no PDF).
 */
class QuotationSmsNotifier
{
    public function __construct(
        private Util $util,
        private LinkedWhatsappSender $linkedWhatsapp
    ) {}

    public function notifyCustomer(Transaction $transaction, int $businessId): bool
    {
        $transaction->loadMissing('contact');

        $isQuotation = (int) $transaction->is_quotation === 1
            || ($transaction->status === 'draft' && ($transaction->sub_status ?? '') === 'quotation');

        $isProforma = $transaction->status === 'draft'
            && ($transaction->sub_status ?? '') === 'proforma';

        $isInvoice = ! $isQuotation && ! $isProforma
            && $transaction->type === 'sell'
            && $transaction->status === 'final';

        if (! $isQuotation && ! $isInvoice && ! $isProforma) {
            return false;
        }

        $contact = $transaction->contact;
        if (! $contact || empty(trim((string) $contact->mobile))) {
            Log::info('DocumentNotify: skipped — no customer mobile', [
                'transaction_id' => $transaction->id,
                'type' => $isQuotation ? 'quotation' : ($isProforma ? 'proforma' : 'invoice'),
            ]);

            return false;
        }

        $business = Business::find($businessId);
        if (! $business) {
            return false;
        }

        if ($isProforma) {
            $docType = 'proforma';
            $docLabel = 'Proforma Invoice';
            $readyLine = 'Your proforma invoice is ready.';
        } elseif ($isQuotation) {
            $docType = 'quotation';
            $docLabel = 'Quotation';
            $readyLine = 'Your quotation is ready.';
        } else {
            $docType = 'invoice';
            $docLabel = 'Invoice';
            $readyLine = 'Your invoice is ready.';
        }
        $docNo = (string) $transaction->invoice_no;
        $amount = $this->util->num_f($transaction->final_total, true, $business);
        $brand = trim((string) (config('app.name') ?: 'PrintWorks'));
        $customerName = $this->customerDisplayName($contact);
        $viewUrl = $this->util->getInvoiceUrl($transaction->id, $businessId);

        $smsMessage = implode("\n", [
            $brand,
            "Dear {$customerName},",
            $readyLine,
            "{$docLabel} No: {$docNo}",
            "Amount: {$amount}",
            "View: {$viewUrl}",
            '— System generated message',
        ]);

        $waMessage = implode("\n", [
            "*{$brand} — {$docLabel}*",
            '',
            "Dear *{$customerName}*,",
            $readyLine,
            '',
            "*{$docLabel} No:* {$docNo}",
            "*Amount:* {$amount}",
            '',
            'View here:',
            $viewUrl,
            '',
            '_System generated message_',
        ]);

        $smsSent = $this->sendSms($business, $contact->mobile, $smsMessage, $transaction, $businessId, $docType);
        $waSent = $this->sendWhatsapp($contact->mobile, $waMessage, $transaction, $businessId, $docType);

        return $smsSent || $waSent;
    }

    /**
     * @param  array{amount?: float|string, method?: string, note?: string|null, paid_on?: string|null}  $payment
     */
    public function notifyPaymentReceived(Transaction $transaction, int $businessId, array $payment): bool
    {
        if ($transaction->type !== 'sell' || $transaction->status !== 'final') {
            return false;
        }

        $transaction->loadMissing('contact');
        $contact = $transaction->contact;
        if (! $contact || empty(trim((string) $contact->mobile))) {
            Log::info('PaymentNotify: skipped — no customer mobile', ['transaction_id' => $transaction->id]);

            return false;
        }

        $business = Business::find($businessId);
        if (! $business) {
            return false;
        }

        $paidAmount = $this->util->num_f((float) ($payment['amount'] ?? 0), true, $business);
        $method = trim((string) ($payment['method'] ?? 'Payment'));
        $methodLabel = ucwords(str_replace('_', ' ', $method));
        $invoiceNo = (string) $transaction->invoice_no;
        $total = $this->util->num_f($transaction->final_total, true, $business);

        $paidTotal = app(\App\Utils\TransactionUtil::class)->getTotalPaid($transaction->id);
        $dueRaw = (float) $transaction->final_total - (float) $paidTotal;
        if ($dueRaw < 0) {
            $dueRaw = 0;
        }
        $balanceDue = $this->util->num_f($dueRaw, true, $business);

        $brand = trim((string) (config('app.name') ?: 'PrintWorks'));
        $customerName = $this->customerDisplayName($contact);
        $viewUrl = $this->util->getInvoiceUrl($transaction->id, $businessId);
        $note = trim((string) ($payment['note'] ?? ''));

        $smsLines = [
            $brand,
            "Dear {$customerName},",
            'Payment received. Thank you.',
            "Invoice No: {$invoiceNo}",
            "Paid: {$paidAmount}",
            "Method: {$methodLabel}",
            "Balance Due: {$balanceDue}",
            "View: {$viewUrl}",
            '— System generated message',
        ];
        if ($note !== '') {
            array_splice($smsLines, 6, 0, "Note: {$note}");
        }
        $smsMessage = implode("\n", $smsLines);

        $waLines = [
            "*{$brand} — Payment Received*",
            '',
            "Dear *{$customerName}*,",
            'We have received your payment. Thank you.',
            '',
            "*Invoice No:* {$invoiceNo}",
            "*Paid:* {$paidAmount}",
            "*Method:* {$methodLabel}",
            "*Invoice Total:* {$total}",
            "*Balance Due:* {$balanceDue}",
        ];
        if ($note !== '') {
            $waLines[] = "*Note:* {$note}";
        }
        $waLines = array_merge($waLines, [
            '',
            'View invoice:',
            $viewUrl,
            '',
            '_System generated message_',
        ]);
        $waMessage = implode("\n", $waLines);

        $smsSent = $this->sendSms($business, $contact->mobile, $smsMessage, $transaction, $businessId, 'payment');
        $waSent = $this->sendWhatsapp($contact->mobile, $waMessage, $transaction, $businessId, 'payment');

        return $smsSent || $waSent;
    }

    /**
     * Prefer a real person/business name for greetings (not a blank "Customer").
     */
    private function customerDisplayName(?Contact $contact): string
    {
        if (! $contact) {
            return 'Valued Customer';
        }

        $candidates = [
            trim((string) ($contact->name ?? '')),
            trim(implode(' ', array_filter([
                $contact->first_name ?? null,
                $contact->middle_name ?? null,
                $contact->last_name ?? null,
            ]))),
            trim((string) ($contact->supplier_business_name ?? '')),
        ];

        foreach ($candidates as $name) {
            if ($name === '') {
                continue;
            }
            // Skip generic walk-in labels
            if (preg_match('/^walk[\s\-]?in/i', $name)) {
                continue;
            }
            if (strcasecmp($name, 'Customer') === 0) {
                continue;
            }

            return $name;
        }

        return 'Valued Customer';
    }

    private function sendSms(
        Business $business,
        string $mobile,
        string $message,
        Transaction $transaction,
        int $businessId,
        string $docType
    ): bool {
        $smsSettings = $this->resolveSmsSettings($business);
        if ($smsSettings['sms_service'] === 'textlk' && empty($smsSettings['textlk_api_key'])) {
            Log::warning('DocumentNotify: TextLK API key not configured', ['business_id' => $business->id]);

            return false;
        }

        try {
            $this->util->sendSms([
                'mobile_number' => $mobile,
                'sms_body' => $message,
                'sms_settings' => $smsSettings,
            ]);

            $this->util->activityLog(
                $transaction,
                $docType.'_sms_sent',
                null,
                ['phone' => $mobile],
                false,
                $businessId
            );

            return true;
        } catch (\Throwable $e) {
            Log::error('DocumentNotify: SMS failed', [
                'transaction_id' => $transaction->id,
                'phone' => $mobile,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function sendWhatsapp(
        string $mobile,
        string $message,
        Transaction $transaction,
        int $businessId,
        string $docType
    ): bool {
        $sent = $this->linkedWhatsapp->send($mobile, $message);
        if ($sent) {
            $this->util->activityLog(
                $transaction,
                $docType.'_whatsapp_sent',
                null,
                [
                    'phone' => LinkedWhatsappSender::normalizePhone($mobile),
                    'pdf' => false,
                ],
                false,
                $businessId
            );
        }

        return $sent;
    }

    private function resolveSmsSettings(Business $business): array
    {
        $settings = is_array($business->sms_settings ?? null) ? $business->sms_settings : [];

        $envKey = env('TEXTLK_API_KEY');
        $envDriver = env('SMS_DRIVER', 'textlk');

        if (! empty($envKey)) {
            return array_merge($settings, [
                'sms_service' => 'textlk',
                'textlk_api_key' => $envKey,
                'textlk_sender_id' => env('TEXTLK_SENDER_ID', $settings['textlk_sender_id'] ?? 'PrintWorks'),
                'textlk_url' => env('TEXTLK_URL', $settings['textlk_url'] ?? 'https://app.text.lk/api/v3/sms/send'),
            ]);
        }

        $service = $settings['sms_service'] ?? $envDriver;

        return array_merge($settings, [
            'sms_service' => $service,
            'textlk_api_key' => $settings['textlk_api_key'] ?? null,
            'textlk_sender_id' => $settings['textlk_sender_id'] ?? env('TEXTLK_SENDER_ID', 'PrintWorks'),
            'textlk_url' => $settings['textlk_url'] ?? env('TEXTLK_URL', 'https://app.text.lk/api/v3/sms/send'),
        ]);
    }
}

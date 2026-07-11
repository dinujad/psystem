<?php

namespace App\Services;

use App\Business;
use App\Transaction;
use App\Utils\Util;
use Illuminate\Support\Facades\Log;

/**
 * Sends customer SMS (gateway) + WhatsApp (linked device + PDF) for invoices and quotations.
 */
class QuotationSmsNotifier
{
    public function __construct(
        private Util $util,
        private LinkedWhatsappSender $linkedWhatsapp,
        private AttractDocumentPdf $attractPdf
    ) {}

    public function notifyCustomer(Transaction $transaction, int $businessId): bool
    {
        $transaction->loadMissing('contact');

        $isQuotation = (int) $transaction->is_quotation === 1
            || ($transaction->status === 'draft' && ($transaction->sub_status ?? '') === 'quotation');

        $isInvoice = ! $isQuotation
            && $transaction->type === 'sell'
            && $transaction->status === 'final';

        if (! $isQuotation && ! $isInvoice) {
            return false;
        }

        $contact = $transaction->contact;
        if (! $contact || empty(trim((string) $contact->mobile))) {
            Log::info('DocumentNotify: skipped — no customer mobile', [
                'transaction_id' => $transaction->id,
                'type' => $isQuotation ? 'quotation' : 'invoice',
            ]);

            return false;
        }

        $business = Business::find($businessId);
        if (! $business) {
            return false;
        }

        $docType = $isQuotation ? 'quotation' : 'invoice';
        $docLabel = $isQuotation ? 'Quotation' : 'Invoice';
        $docNo = (string) $transaction->invoice_no;
        $amount = $this->util->num_f($transaction->final_total, true, $business);
        $brand = trim((string) (config('app.name') ?: 'PrintWorks'));
        $customerName = trim((string) ($contact->name ?: 'Customer'));
        $viewUrl = $this->util->getInvoiceUrl($transaction->id, $businessId);
        $readyLine = $isQuotation ? 'Your quotation is ready.' : 'Your invoice is ready.';

        // Normal SMS to customer phone
        $smsMessage = implode("\n", [
            $brand,
            "Dear {$customerName},",
            $readyLine,
            "{$docLabel} No: {$docNo}",
            "Amount: {$amount}",
            "View: {$viewUrl}",
            '— System generated message',
        ]);

        // WhatsApp caption + PDF
        $waMessage = implode("\n", [
            "*{$brand} — {$docLabel}*",
            '',
            "Dear {$customerName},",
            $readyLine,
            '',
            "*{$docLabel} No:* {$docNo}",
            "*Amount:* {$amount}",
            '',
            'View:',
            $viewUrl,
            '',
            '_System generated message_',
        ]);

        $smsSent = $this->sendSms($business, $contact->mobile, $smsMessage, $transaction, $businessId, $docType);
        $waSent = $this->sendWhatsappWithPdf($contact->mobile, $waMessage, $transaction, $businessId, $docType, $docLabel);

        return $smsSent || $waSent;
    }

    /**
     * Confirm a payment via SMS + WhatsApp (updated invoice PDF).
     *
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
        $customerName = trim((string) ($contact->name ?: 'Customer'));
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
            "Dear {$customerName},",
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
        $waSent = $this->sendWhatsappWithPdf($contact->mobile, $waMessage, $transaction, $businessId, 'payment', 'Invoice');

        return $smsSent || $waSent;
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

    private function sendWhatsappWithPdf(
        string $mobile,
        string $message,
        Transaction $transaction,
        int $businessId,
        string $docType,
        string $docLabel
    ): bool {
        $media = [];
        $pdf = $this->attractPdf->render($businessId, (int) $transaction->id);
        if ($pdf && ! empty($pdf['binary'])) {
            $media = [
                'media_type' => 'document',
                'media_base64' => base64_encode($pdf['binary']),
                'media_mimetype' => 'application/pdf',
                'media_filename' => $pdf['filename'] ?? ($docLabel.'-'.$transaction->invoice_no.'.pdf'),
            ];
        } else {
            Log::warning('DocumentNotify: PDF render failed — sending WhatsApp text only', [
                'transaction_id' => $transaction->id,
            ]);
        }

        $sent = $this->linkedWhatsapp->send($mobile, $message, $media);
        if ($sent) {
            $this->util->activityLog(
                $transaction,
                $docType.'_whatsapp_sent',
                null,
                [
                    'phone' => LinkedWhatsappSender::normalizePhone($mobile),
                    'pdf' => ! empty($media),
                ],
                false,
                $businessId
            );
        }

        return $sent;
    }

    private function resolveSmsSettings(Business $business): array
    {
        $settings = $business->sms_settings ?? [];
        $service = $settings['sms_service'] ?? env('SMS_DRIVER', 'textlk');

        return array_merge($settings, [
            'sms_service' => $service,
            'textlk_api_key' => ! empty($settings['textlk_api_key']) ? $settings['textlk_api_key'] : env('TEXTLK_API_KEY'),
            'textlk_sender_id' => ! empty($settings['textlk_sender_id']) ? $settings['textlk_sender_id'] : env('TEXTLK_SENDER_ID', 'PrintWorks'),
            'textlk_url' => ! empty($settings['textlk_url']) ? $settings['textlk_url'] : env('TEXTLK_URL', 'https://app.text.lk/api/v3/sms/send'),
        ]);
    }
}

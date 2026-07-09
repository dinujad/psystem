<?php

namespace App\Services;

use App\Business;
use App\Transaction;
use App\Utils\Util;
use Illuminate\Support\Facades\Log;

class QuotationSmsNotifier
{
    public function __construct(
        private Util $util,
        private LinkedWhatsappSender $linkedWhatsapp
    ) {}

    public function notifyCustomer(Transaction $transaction, int $businessId): bool
    {
        if ((int) $transaction->is_quotation !== 1) {
            return false;
        }

        $transaction->loadMissing('contact');
        $contact = $transaction->contact;

        if (! $contact || empty(trim((string) $contact->mobile))) {
            Log::info('QuotationNotify: skipped — no customer mobile', ['transaction_id' => $transaction->id]);

            return false;
        }

        $business = Business::find($businessId);
        if (! $business) {
            return false;
        }

        $quoteUrl = $this->util->getInvoiceUrl($transaction->id, $businessId);
        $amount = $this->util->num_f($transaction->final_total, true, $business);
        $quotationNo = $transaction->invoice_no;
        $customerName = trim((string) ($contact->name ?: 'Customer'));
        $brand = trim((string) (config('app.name', 'PrintWorks')));

        $smsMessage = implode("\n", [
            $brand,
            "Dear {$customerName},",
            'Your quotation is ready.',
            "Quotation No: {$quotationNo}",
            "Amount: {$amount}",
            "View: {$quoteUrl}",
            '— System generated message',
        ]);

        $waMessage = implode("\n", [
            "🖨️ *{$brand} — Quotation*",
            '',
            "Dear {$customerName},",
            'Your quotation is ready.',
            '',
            "*Quotation No:* {$quotationNo}",
            "*Amount:* {$amount}",
            '',
            'View quotation:',
            $quoteUrl,
            '',
            '_System generated message_',
        ]);

        $smsSent = $this->sendSms($business, $contact->mobile, $smsMessage, $transaction, $businessId);
        $waSent = $this->sendWhatsapp($contact->mobile, $waMessage, $transaction, $businessId);

        return $smsSent || $waSent;
    }

    private function sendSms(Business $business, string $mobile, string $message, Transaction $transaction, int $businessId): bool
    {
        $smsSettings = $this->resolveTextlkSettings($business);
        if (empty($smsSettings['textlk_api_key'])) {
            Log::warning('QuotationNotify: TextLK API key not configured', ['business_id' => $business->id]);

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
                'quotation_sms_sent',
                null,
                ['phone' => $mobile],
                false,
                $businessId
            );

            return true;
        } catch (\Throwable $e) {
            Log::error('QuotationNotify: SMS failed', [
                'transaction_id' => $transaction->id,
                'phone' => $mobile,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function sendWhatsapp(string $mobile, string $message, Transaction $transaction, int $businessId): bool
    {
        $sent = $this->linkedWhatsapp->send($mobile, $message);
        if ($sent) {
            $this->util->activityLog(
                $transaction,
                'quotation_whatsapp_sent',
                null,
                ['phone' => LinkedWhatsappSender::normalizePhone($mobile)],
                false,
                $businessId
            );
        }

        return $sent;
    }

    private function resolveTextlkSettings(Business $business): array
    {
        $settings = $business->sms_settings ?? [];

        return [
            'sms_service' => 'textlk',
            'textlk_api_key' => ! empty($settings['textlk_api_key']) ? $settings['textlk_api_key'] : env('TEXTLK_API_KEY'),
            'textlk_sender_id' => ! empty($settings['textlk_sender_id']) ? $settings['textlk_sender_id'] : env('TEXTLK_SENDER_ID', 'PrintWorks'),
            'textlk_url' => ! empty($settings['textlk_url']) ? $settings['textlk_url'] : env('TEXTLK_URL', 'https://app.text.lk/api/v3/sms/send'),
        ];
    }
}

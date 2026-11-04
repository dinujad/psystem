<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class LinkedWhatsappSender
{
    public function __construct(private WhatsappService $whatsapp) {}

    public static function normalizePhone(string $number): ?string
    {
        $number = preg_replace('/\D+/', '', $number);
        if (empty($number)) {
            return null;
        }

        if (str_starts_with($number, '0') && strlen($number) === 10) {
            return '94'.substr($number, 1);
        }

        if (str_starts_with($number, '94')) {
            return $number;
        }

        return $number;
    }

    public static function accountFromBrand(?string $brand): string
    {
        $brand = strtolower(trim((string) $brand));

        return $brand === 'safetysign' ? 'safetysign' : 'printworks';
    }

    public function isConnected(?string $account = 'printworks'): bool
    {
        $status = $this->whatsapp->getStatus($account);

        return ($status['status'] ?? '') === 'connected';
    }

    public function send(string $mobile, string $message, array $media = [], ?string $brandOrAccount = 'printworks'): bool
    {
        $phone = self::normalizePhone($mobile);
        if (empty($phone)) {
            Log::warning('LinkedWhatsapp: invalid phone number', ['mobile' => $mobile]);

            return false;
        }

        $account = self::accountFromBrand($brandOrAccount);
        if (! $this->isConnected($account)) {
            Log::warning('LinkedWhatsapp: WhatsApp not connected — link device at /whatsapp/link', [
                'account' => $account,
            ]);

            return false;
        }

        $result = $this->whatsapp->sendMessage($phone, $message, $media, $account);
        if (empty($result['success'])) {
            Log::warning('LinkedWhatsapp: send failed', [
                'phone' => $phone,
                'account' => $account,
                'message' => $result['message'] ?? 'unknown error',
            ]);

            return false;
        }

        return true;
    }
}

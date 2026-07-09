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

    public function isConnected(): bool
    {
        $status = $this->whatsapp->getStatus();

        return ($status['status'] ?? '') === 'connected';
    }

    public function send(string $mobile, string $message, array $media = []): bool
    {
        $phone = self::normalizePhone($mobile);
        if (empty($phone)) {
            Log::warning('LinkedWhatsapp: invalid phone number', ['mobile' => $mobile]);

            return false;
        }

        if (! $this->isConnected()) {
            Log::warning('LinkedWhatsapp: WhatsApp not connected — link device at /whatsapp/link');

            return false;
        }

        $result = $this->whatsapp->sendMessage($phone, $message, $media);
        if (empty($result['success'])) {
            Log::warning('LinkedWhatsapp: send failed', [
                'phone' => $phone,
                'message' => $result['message'] ?? 'unknown error',
            ]);

            return false;
        }

        return true;
    }
}

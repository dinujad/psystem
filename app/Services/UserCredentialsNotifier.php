<?php

namespace App\Services;

use App\Business;
use App\User;
use App\Utils\Util;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UserCredentialsNotifier
{
    public function __construct(
        private LinkedWhatsappSender $whatsapp,
        private Util $util
    ) {}

    /**
     * Reset password (hashed in DB), enable login, and send credentials via SMS + WhatsApp.
     */
    public function resetAndSend(User $user, int $businessId): array
    {
        $credentials = $this->issueLoginCredentials($user, $businessId);
        $messageWa = $this->buildWhatsappMessage($user, $credentials);
        $messageSms = $this->buildSmsMessage($user, $credentials);

        $whatsappPhone = $this->resolveWhatsappPhone($user);
        $smsPhone = $this->resolveSmsPhone($user);

        if (empty($whatsappPhone) && empty($smsPhone)) {
            return [
                'success' => false,
                'message' => __('user.credentials_no_phone'),
                'credentials' => $credentials,
                'whatsapp' => false,
                'sms' => false,
            ];
        }

        $waSent = false;
        $smsSent = false;
        $errors = [];

        if (! empty($whatsappPhone)) {
            $waSent = $this->whatsapp->send($whatsappPhone, $messageWa);
            if (! $waSent) {
                $errors[] = __('user.credentials_whatsapp_failed');
            }
        }

        if (! empty($smsPhone)) {
            $smsSent = $this->sendSms($businessId, $smsPhone, $messageSms);
            if (! $smsSent) {
                $errors[] = __('user.credentials_sms_failed');
            }
        }

        $anySent = $waSent || $smsSent;

        if (! $anySent) {
            return [
                'success' => false,
                'message' => implode(' ', $errors) ?: __('user.credentials_send_failed'),
                'credentials' => $credentials,
                'whatsapp' => $waSent,
                'sms' => $smsSent,
            ];
        }

        $parts = [];
        if ($waSent) {
            $parts[] = 'WhatsApp';
        }
        if ($smsSent) {
            $parts[] = 'SMS';
        }

        $message = __('user.credentials_sent_success', ['channels' => implode(' + ', $parts)]);
        if (! empty($errors)) {
            $message .= ' '.implode(' ', $errors);
        }

        return [
            'success' => true,
            'message' => $message,
            'credentials' => $credentials,
            'whatsapp' => $waSent,
            'sms' => $smsSent,
        ];
    }

    /**
     * Generate a fresh password, hash it, save to DB, ensure username + allow_login.
     */
    public function issueLoginCredentials(User $user, int $businessId): array
    {
        $plainPassword = Str::upper(Str::random(4)).Str::lower(Str::random(2)).random_int(10, 99);

        $updates = [
            'allow_login' => 1,
            'password' => Hash::make($plainPassword),
            'status' => 'active',
        ];

        if (empty($user->username)) {
            $refCount = $this->util->setAndGetReferenceCount('username', $businessId);
            $username = $this->util->generateReferenceNumber('username', $refCount, $businessId);
            $ext = $this->util->getUsernameExtension();
            if (! empty($ext)) {
                $username .= $ext;
            }
            $updates['username'] = $username;
        }

        $user->update($updates);
        $user->refresh();

        return [
            'username' => $user->username,
            'password' => $plainPassword,
        ];
    }

    public function buildWhatsappMessage(User $user, array $credentials): string
    {
        $name = trim(($user->surname ?? '').' '.($user->first_name ?? '').' '.($user->last_name ?? ''));
        $name = $name ?: ($user->username ?? 'User');
        $loginUrl = url('/login');

        return implode("\n", [
            '*PrintWorks Login Credentials*',
            '',
            'Hello '.$name.',',
            '',
            'Your account password has been reset. Use these details to sign in:',
            '',
            'Username: '.($credentials['username'] ?? ''),
            'Password: '.($credentials['password'] ?? ''),
            'Login URL: '.$loginUrl,
            '',
            'Please change your password after logging in.',
        ]);
    }

    public function buildSmsMessage(User $user, array $credentials): string
    {
        $loginUrl = url('/login');

        return 'PrintWorks login — Username: '.($credentials['username'] ?? '')
            .' Password: '.($credentials['password'] ?? '')
            .' URL: '.$loginUrl;
    }

    public function resolveWhatsappPhone(User $user): ?string
    {
        foreach (['whatsapp_number', 'contact_number', 'contact_no', 'alt_number'] as $field) {
            $value = trim((string) ($user->{$field} ?? ''));
            if ($value !== '') {
                return LinkedWhatsappSender::normalizePhone($value);
            }
        }

        return null;
    }

    public function resolveSmsPhone(User $user): ?string
    {
        foreach (['contact_number', 'contact_no', 'whatsapp_number', 'alt_number'] as $field) {
            $value = trim((string) ($user->{$field} ?? ''));
            if ($value !== '') {
                return LinkedWhatsappSender::normalizePhone($value);
            }
        }

        return null;
    }

    private function sendSms(int $businessId, string $mobile, string $message): bool
    {
        $business = Business::find($businessId);
        if (! $business) {
            return false;
        }

        $smsSettings = $this->resolveSmsSettings($business);
        if (($smsSettings['sms_service'] ?? '') === 'textlk' && empty($smsSettings['textlk_api_key'])) {
            Log::warning('UserCredentialsNotifier: TextLK API key not configured', ['business_id' => $businessId]);

            return false;
        }

        try {
            $this->util->sendSms([
                'mobile_number' => $mobile,
                'sms_body' => $message,
                'sms_settings' => $smsSettings,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::warning('UserCredentialsNotifier: SMS failed', [
                'business_id' => $businessId,
                'mobile' => $mobile,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
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

<?php

namespace App\Services;

use App\WhatsappContact;
use App\WhatsappMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappService
{
    protected function baseUrl(): string
    {
        return rtrim((string) config('services.whatsapp.url'), '/');
    }

    protected function apiKey(): string
    {
        return (string) config('services.whatsapp.api_key');
    }

    protected function client()
    {
        return Http::timeout(15)
            ->acceptJson()
            ->withHeaders([
                'x-api-key' => $this->apiKey(),
            ]);
    }

    protected function serviceUnavailableResponse(): array
    {
        return [
            'success' => false,
            'message' => 'WhatsApp service is unavailable. Please ensure the Node service is running.',
        ];
    }

    public function getQrCode(): array
    {
        try {
            $response = $this->client()->get($this->baseUrl().'/qr');

            if ($response->failed()) {
                return array_merge($this->serviceUnavailableResponse(), [
                    'status' => 'disconnected',
                ]);
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::warning('WhatsApp getQrCode failed: '.$e->getMessage());

            return array_merge($this->serviceUnavailableResponse(), [
                'status' => 'disconnected',
            ]);
        }
    }

    public function getStatus(): array
    {
        try {
            $response = $this->client()->get($this->baseUrl().'/status');

            if ($response->failed()) {
                return [
                    'status' => 'disconnected',
                    'message' => 'WhatsApp service is unavailable.',
                ];
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::warning('WhatsApp getStatus failed: '.$e->getMessage());

            return [
                'status' => 'disconnected',
                'message' => 'WhatsApp service is unavailable.',
            ];
        }
    }

    public function sendMessage(string $number, string $message, array $media = []): array
    {
        $normalizedNumber = preg_replace('/\D/', '', $number);

        try {
            $body = array_merge(['number' => $normalizedNumber, 'message' => $message], $media);
            $response = $this->client()->post($this->baseUrl().'/send', $body);

            $payload = $response->json() ?? [];
            $success = $response->successful() && ! empty($payload['success']);

            $record = WhatsappMessage::create([
                'direction'      => 'out',
                'phone_number'   => $normalizedNumber,
                'message'        => $message ?: ($media['media_filename'] ?? ''),
                'status'         => $success ? 'sent' : 'failed',
                'message_id'     => $payload['message_id'] ?? null,
                'media_type'     => $media['media_type'] ?? null,
                'media_path'     => $media['_local_path'] ?? null,
                'media_filename' => $media['media_filename'] ?? null,
                'media_mimetype' => $media['media_mimetype'] ?? null,
            ]);

            if (! $success) {
                return ['success' => false, 'message' => $payload['error'] ?? 'Failed to send WhatsApp message.'];
            }

            return [
                'success'    => true,
                'message_id' => $payload['message_id'] ?? null,
                'id'         => $record->id,
            ];
        } catch (\Throwable $e) {
            Log::error('WhatsApp sendMessage failed: '.$e->getMessage());
            WhatsappMessage::create([
                'direction' => 'out', 'phone_number' => $normalizedNumber,
                'message' => $message, 'status' => 'failed',
            ]);
            return $this->serviceUnavailableResponse();
        }
    }

    public function sendImageFromUrl(string $number, string $imageUrl, string $caption = ''): array
    {
        return $this->sendMediaFromUrl($number, $imageUrl, 'image', $caption);
    }

    public function sendAudioFromUrl(string $number, string $audioUrl, string $caption = ''): array
    {
        return $this->sendMediaFromUrl($number, $audioUrl, 'document', $caption, 'voice.ogg', 'audio/ogg');
    }

    public function sendLinkPreview(string $number, string $text, string $url, string $title = '', string $description = ''): array
    {
        $lines = array_filter([
            trim($text),
            $title !== '' ? "*{$title}*" : null,
            $description !== '' ? $description : null,
            $url,
        ]);

        return $this->sendMessage($number, implode("\n", $lines));
    }

    public function sendPoll(string $number, string $question, array $options, int $selectableCount = 1): array
    {
        $normalizedNumber = preg_replace('/\D/', '', $number);

        try {
            $response = $this->client()->post($this->baseUrl().'/send-poll', [
                'number' => $normalizedNumber,
                'question' => $question,
                'options' => $options,
                'selectable_count' => $selectableCount,
            ]);

            return $this->parseNodeResponse($response, $normalizedNumber, "Poll: {$question}");
        } catch (\Throwable $e) {
            Log::error('WhatsApp sendPoll failed: '.$e->getMessage());

            return $this->serviceUnavailableResponse();
        }
    }

    public function sendStatusText(string $text, ?string $backgroundColor = null, ?string $font = null): array
    {
        try {
            $payload = ['text' => $text];
            if ($backgroundColor) {
                $payload['background_color'] = $backgroundColor;
            }
            if ($font) {
                $payload['font'] = $font;
            }

            $response = $this->client()->post($this->baseUrl().'/status/send-text', $payload);

            $data = $response->json() ?? [];
            $success = $response->successful() && ! empty($data['success']);

            if (! $success) {
                return ['success' => false, 'message' => $data['error'] ?? 'Failed to send WhatsApp status.'];
            }

            return [
                'success' => true,
                'message_id' => $data['message_id'] ?? null,
                'status_id' => $data['status_id'] ?? null,
                'recipients' => $data['recipients'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::error('WhatsApp sendStatusText failed: '.$e->getMessage());

            return $this->serviceUnavailableResponse();
        }
    }

    public function deleteStatus(string $statusId): array
    {
        try {
            $response = $this->client()->post($this->baseUrl().'/status/delete', [
                'status_id' => $statusId,
            ]);

            $data = $response->json() ?? [];
            $success = $response->successful() && ! empty($data['success']);

            if (! $success) {
                return ['success' => false, 'message' => $data['error'] ?? 'Failed to delete WhatsApp status.'];
            }

            return ['success' => true, 'message' => $data['message'] ?? 'Status deleted successfully.'];
        } catch (\Throwable $e) {
            Log::error('WhatsApp deleteStatus failed: '.$e->getMessage());

            return $this->serviceUnavailableResponse();
        }
    }

    private function parseNodeResponse($response, string $phone, string $message): array
    {
        $payload = $response->json() ?? [];
        $success = $response->successful() && ! empty($payload['success']);

        WhatsappMessage::create([
            'direction' => 'out',
            'phone_number' => $phone,
            'message' => $message,
            'status' => $success ? 'sent' : 'failed',
            'message_id' => $payload['message_id'] ?? null,
        ]);

        if (! $success) {
            return ['success' => false, 'message' => $payload['error'] ?? 'Failed to send WhatsApp message.'];
        }

        return [
            'success' => true,
            'message_id' => $payload['message_id'] ?? null,
        ];
    }

    private function sendMediaFromUrl(
        string $number,
        string $url,
        string $mediaType,
        string $caption = '',
        ?string $filename = null,
        ?string $mimetype = null
    ): array {
        try {
            $response = Http::timeout(30)->get($url);
            if ($response->failed()) {
                return ['success' => false, 'message' => 'Failed to download media from URL.'];
            }

            $bytes = $response->body();
            if ($bytes === '') {
                return ['success' => false, 'message' => 'Media URL returned empty content.'];
            }

            $mimetype = $mimetype ?: ($response->header('Content-Type') ?: 'application/octet-stream');
            $filename = $filename ?: basename(parse_url($url, PHP_URL_PATH) ?: 'file');

            return $this->sendMessage($number, $caption, [
                'media_type' => $mediaType,
                'media_base64' => base64_encode($bytes),
                'media_mimetype' => $mimetype,
                'media_filename' => $filename,
            ]);
        } catch (\Throwable $e) {
            Log::error('WhatsApp sendMediaFromUrl failed: '.$e->getMessage());

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getHealth(): array
    {
        try {
            $response = $this->client()->get($this->baseUrl().'/health');
            if ($response->failed()) {
                return ['ok' => false, 'status' => 'disconnected'];
            }

            return $response->json() ?? ['ok' => false];
        } catch (\Throwable $e) {
            return ['ok' => false, 'status' => 'disconnected'];
        }
    }

    public function triggerContactsSync(): array
    {
        try {
            $response = $this->client()->timeout(120)->post($this->baseUrl().'/sync-contacts');

            return $response->json() ?? ['success' => false];
        } catch (\Throwable $e) {
            Log::warning('WhatsApp triggerContactsSync failed: '.$e->getMessage());

            return ['success' => false, 'message' => 'Could not sync contacts from WhatsApp.'];
        }
    }

    /**
     * @param  array<int, array{phone: string, wa_name?: string|null, profile_picture?: string|null}>  $contacts
     */
    public function syncDeviceContacts(array $contacts): int
    {
        $count = 0;

        foreach ($contacts as $row) {
            if ($this->syncDeviceContact($row)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param  array{phone: string, wa_name?: string|null, profile_picture?: string|null}  $data
     */
    public function syncDeviceContact(array $data): ?WhatsappContact
    {
        $phone = preg_replace('/\D/', '', (string) ($data['phone'] ?? ''));
        if (strlen($phone) < 7) {
            return null;
        }

        if (strlen($phone) === 10 && str_starts_with($phone, '0')) {
            $phone = '94'.substr($phone, 1);
        }

        $contact = WhatsappContact::firstOrNew(['phone_number' => $phone]);

        if (! empty($data['wa_name'])) {
            $contact->wa_name = mb_substr(trim((string) $data['wa_name']), 0, 120);
        }

        if (! empty($data['profile_picture'])) {
            $saved = $this->saveProfilePicture($phone, (string) $data['profile_picture']);
            if ($saved) {
                $contact->profile_picture = $saved;
            }
        }

        if (! $contact->exists && empty($contact->wa_name) && empty($contact->profile_picture)) {
            return null;
        }

        $contact->save();

        return $contact;
    }

    private function saveProfilePicture(string $phone, string $base64): ?string
    {
        try {
            $binary = base64_decode($base64, true);
            if ($binary === false || $binary === '') {
                return null;
            }

            $dir = storage_path('app/public/whatsapp/avatars');
            if (! is_dir($dir)) {
                mkdir($dir, 0775, true);
            }

            $path = 'whatsapp/avatars/'.$phone.'.jpg';
            file_put_contents(storage_path('app/public/'.$path), $binary);

            return $path;
        } catch (\Throwable $e) {
            Log::warning('WhatsApp profile picture save failed: '.$e->getMessage());

            return null;
        }
    }

    public function logout(): array
    {
        try {
            $response = $this->client()->post($this->baseUrl().'/logout');

            if ($response->failed()) {
                return [
                    'success' => false,
                    'message' => $response->json('error') ?? 'Failed to logout WhatsApp session.',
                ];
            }

            return [
                'success' => true,
                'message' => $response->json('message') ?? 'Logged out successfully.',
            ];
        } catch (\Throwable $e) {
            Log::warning('WhatsApp logout failed: '.$e->getMessage());

            return $this->serviceUnavailableResponse();
        }
    }

    public function storeIncomingMessage(array $data): WhatsappMessage
    {
        $mediaPath = null;
        $direction = ($data['direction'] ?? 'in') === 'out' ? 'out' : 'in';
        $status    = $direction === 'out' ? 'sent' : 'received';

        // Save base64 media to disk
        if (! empty($data['media_base64']) && ! empty($data['media_type'])) {
            try {
                $ext      = $this->guessExtension($data['media_mimetype'] ?? '', $data['media_filename'] ?? '');
                $filename = 'wa_' . uniqid() . '.' . $ext;
                $dir      = storage_path('app/public/whatsapp');
                if (! is_dir($dir)) mkdir($dir, 0775, true);
                file_put_contents($dir . '/' . $filename, base64_decode($data['media_base64']));
                $mediaPath = 'whatsapp/' . $filename;
            } catch (\Throwable $e) {
                Log::warning('WhatsApp media save failed: ' . $e->getMessage());
            }
        }

        $rawFrom = preg_replace('/\D/', '', (string) ($data['from'] ?? ''));
        // Auto-add Sri Lanka code if local format
        if (strlen($rawFrom) === 10 && str_starts_with($rawFrom, '0')) {
            $rawFrom = '94' . substr($rawFrom, 1);
        }

        // Resolve WhatsApp LID → real phone when mapping exists
        $resolvedFrom = WhatsappLidResolver::resolve($rawFrom);
        if ($resolvedFrom !== $rawFrom && WhatsappLidResolver::isLikelyLid($rawFrom)) {
            WhatsappLidResolver::mergeInDatabase($rawFrom, $resolvedFrom);
            $rawFrom = $resolvedFrom;
        }

        if (! empty($data['message_id'])) {
            $existing = WhatsappMessage::where('message_id', $data['message_id'])->first();
            if ($existing) {
                return $existing;
            }
        }

        $attrs = [
            'direction'      => $direction,
            'phone_number'   => $rawFrom,
            'message'        => (string) ($data['message'] ?? ''),
            'status'         => $status,
            'message_id'     => $data['message_id'] ?? null,
            'media_type'     => $data['media_type'] ?? null,
            'media_path'     => $mediaPath,
            'media_filename' => $data['media_filename'] ?? null,
            'media_mimetype' => $data['media_mimetype'] ?? null,
        ];

        if (! empty($data['timestamp'])) {
            try {
                $ts = \Carbon\Carbon::parse($data['timestamp']);
                $attrs['created_at'] = $ts;
                $attrs['updated_at'] = $ts;
            } catch (\Throwable $e) {
                // keep default now()
            }
        }

        return WhatsappMessage::create($attrs);
    }

    private function guessExtension(string $mime, string $filename): string
    {
        if ($filename && str_contains($filename, '.')) {
            return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        }
        $map = [
            'image/jpeg'      => 'jpg',
            'image/png'       => 'png',
            'image/gif'       => 'gif',
            'image/webp'      => 'webp',
            'application/pdf' => 'pdf',
            'video/mp4'       => 'mp4',
            'audio/ogg'       => 'ogg',
            'audio/mpeg'      => 'mp3',
        ];
        return $map[$mime] ?? 'bin';
    }
}

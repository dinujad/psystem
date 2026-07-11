<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FardarDeliveryService
{
    public function clientId(): string
    {
        return (string) config('services.fardar.client_id');
    }

    public function apiKey(): string
    {
        return (string) config('services.fardar.api_key');
    }

    public function newWaybillUrl(): string
    {
        return (string) config('services.fardar.new_waybill_url');
    }

    public function existingWaybillUrl(): string
    {
        return (string) config('services.fardar.existing_waybill_url');
    }

    public function isConfigured(): bool
    {
        return $this->clientId() !== '' && $this->apiKey() !== '';
    }

    /**
     * Create a new Fardar waybill (form-data POST).
     *
     * @param  array<string, mixed>  $payload
     * @return array{success: bool, status: int|null, waybill_no: string|null, message: string, raw: mixed}
     */
    public function createNewWaybill(array $payload): array
    {
        return $this->post($this->newWaybillUrl(), $payload, false);
    }

    /**
     * Assign parcel to an existing Fardar waybill (form-data POST).
     *
     * @param  array<string, mixed>  $payload
     * @return array{success: bool, status: int|null, waybill_no: string|null, message: string, raw: mixed}
     */
    public function createExistingWaybill(array $payload): array
    {
        return $this->post($this->existingWaybillUrl(), $payload, true);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{success: bool, status: int|null, waybill_no: string|null, message: string, raw: mixed}
     */
    protected function post(string $url, array $payload, bool $existing): array
    {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'status' => null,
                'waybill_no' => null,
                'message' => 'Fardar API credentials are not configured.',
                'raw' => null,
            ];
        }

        $body = array_merge([
            'client_id' => $this->clientId(),
            'api_key' => $this->apiKey(),
        ], $payload);

        try {
            $response = Http::asForm()
                ->timeout(30)
                ->post($url, $body);

            $raw = $response->json();
            if (! is_array($raw)) {
                $raw = ['body' => $response->body()];
            }

            $status = isset($raw['status']) ? (int) $raw['status'] : null;
            $waybill = isset($raw['waybill_no']) ? (string) $raw['waybill_no'] : null;
            $success = $response->successful() && $status === 200;

            return [
                'success' => $success,
                'status' => $status,
                'waybill_no' => $waybill,
                'message' => $success
                    ? 'Waybill created successfully.'
                    : $this->statusMessage($status, $existing),
                'raw' => $raw,
            ];
        } catch (\Throwable $e) {
            Log::error('Fardar API request failed: '.$e->getMessage(), [
                'url' => $url,
            ]);

            return [
                'success' => false,
                'status' => null,
                'waybill_no' => null,
                'message' => 'Fardar API request failed: '.$e->getMessage(),
                'raw' => null,
            ];
        }
    }

    public function statusMessage(?int $code, bool $existing = false): string
    {
        $new = [
            200 => 'Successful insert',
            201 => 'Inactive Client',
            202 => 'Invalid order id',
            203 => 'Invalid weight',
            204 => 'Empty or invalid parcel description',
            205 => 'Empty or invalid name',
            206 => 'Contact number 1 is not valid',
            207 => 'Contact number 2 is not valid',
            208 => 'Empty or invalid address',
            209 => 'Invalid City',
            210 => 'Unsuccessful insert, try again',
            211 => 'Invalid API key',
            212 => 'Invalid or inactive client',
            213 => 'Invalid exchange value',
            214 => 'System maintain mode is activated',
        ];

        $exist = [
            200 => 'Successfully insert the parcel',
            201 => 'Incorrect waybill type. Only allow CRE or CCP',
            202 => 'The waybill is used',
            203 => 'The waybill is not yet assigned',
            204 => 'Inactive Client',
            205 => 'Invalid order id',
            206 => 'Invalid weight',
            207 => 'Empty or invalid parcel description',
            208 => 'Empty or invalid name',
            209 => 'Invalid contact number 1',
            210 => 'Invalid contact number 2',
            211 => 'Empty or invalid address',
            212 => 'Empty or invalid amount',
            213 => 'Invalid city',
            214 => 'Parcel insert unsuccessfully',
            215 => 'Invalid or inactive client',
            216 => 'Invalid API key',
            217 => 'Invalid exchange value',
            218 => 'System maintain mode is activated',
        ];

        $map = $existing ? $exist : $new;

        if ($code === null) {
            return 'No response from Fardar API';
        }

        return $map[$code] ?? ('Unknown status code: '.$code);
    }
}

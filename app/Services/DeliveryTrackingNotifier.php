<?php

namespace App\Services;

use App\DeliveryParcel;
use Illuminate\Support\Facades\Log;

/**
 * Customer WhatsApp updates for delivery create + live Fardar status changes.
 */
class DeliveryTrackingNotifier
{
    public function __construct(private LinkedWhatsappSender $whatsapp) {}

    public function notifyCreated(DeliveryParcel $parcel): bool
    {
        return $this->send($parcel, 'created');
    }

    public function notifyStatusUpdate(DeliveryParcel $parcel, ?string $previousStatus = null): bool
    {
        if ($previousStatus !== null && strcasecmp((string) $previousStatus, (string) $parcel->current_status) === 0) {
            return false;
        }

        return $this->send($parcel, 'status');
    }

    private function send(DeliveryParcel $parcel, string $kind): bool
    {
        $phone = trim((string) ($parcel->recipient_contact_1 ?: ''));
        if ($phone === '') {
            Log::info('DeliveryTrackNotify: skipped — no recipient phone', ['parcel_id' => $parcel->id]);

            return false;
        }

        if (! $parcel->relationLoaded('transaction')) {
            $parcel->load('transaction');
        }

        $brand = trim((string) (config('app.name') ?: 'PrintWorks'));
        $courier = DeliveryParcel::COURIER_NAME;
        $name = trim((string) ($parcel->recipient_name ?: 'Customer'));
        $status = trim((string) ($parcel->current_status ?: 'Pending'));
        $trackingId = trim((string) ($parcel->waybill_no ?: '—'));
        $orderNo = trim((string) (optional($parcel->transaction)->invoice_no ?: ($parcel->order_id ?: '—')));
        $city = trim((string) ($parcel->recipient_city ?: '—'));
        $address = trim((string) ($parcel->recipient_address ?: ''));
        $description = trim((string) ($parcel->parcel_description ?: ''));
        $weight = trim((string) ($parcel->parcel_weight ?: '—'));
        $codAmount = number_format((float) $parcel->amount, 2);
        $deliveryFee = number_format((float) (optional($parcel->transaction)->shipping_charges ?? 0), 2);
        $trackUrl = $parcel->trackingUrl();

        if ($kind === 'created') {
            $headline = 'Your order has been handed to delivery.';
        } else {
            $headline = 'Delivery status updated.';
        }

        $lines = [
            "*{$brand} — Live Tracking*",
            '',
            "Dear *{$name}*,",
            $headline,
            '',
            "*Courier:* {$courier}",
            "*Tracking ID:* {$trackingId}",
            "*Order / Invoice:* {$orderNo}",
            "*Status:* {$status}",
            "*Delivery Fee:* LKR {$deliveryFee}",
            "*COD / Parcel Value:* LKR {$codAmount}",
            "*Weight:* {$weight}",
            "*City:* {$city}",
        ];

        if ($address !== '') {
            $lines[] = "*Address:* {$address}";
        }
        if ($description !== '') {
            $lines[] = "*Item:* {$description}";
        }

        $lines = array_merge($lines, [
            '',
            'Track your order live:',
            $trackUrl,
            '',
            '_System generated message_',
        ]);

        $message = implode("\n", $lines);

        $sent = $this->whatsapp->send($phone, $message);
        if (! $sent) {
            Log::warning('DeliveryTrackNotify: WhatsApp send failed', [
                'parcel_id' => $parcel->id,
                'phone' => LinkedWhatsappSender::normalizePhone($phone),
            ]);
        }

        return $sent;
    }
}

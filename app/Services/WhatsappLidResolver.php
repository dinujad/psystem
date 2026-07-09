<?php

namespace App\Services;

use App\WhatsappChatAssignment;
use App\WhatsappContact;
use App\WhatsappMessage;
use Illuminate\Support\Facades\Log;

class WhatsappLidResolver
{
    private static ?array $map = null;

    public static function mapPath(): string
    {
        $paths = [
            base_path('whatsapp-service/lid_map.json'),
            base_path('../whatsapp-service/lid_map.json'),
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return $paths[0];
    }

    public static function loadMap(bool $reload = false): array
    {
        if (! $reload && self::$map !== null) {
            return self::$map;
        }

        $path = self::mapPath();
        if (! file_exists($path)) {
            self::$map = [];

            return self::$map;
        }

        $raw = file_get_contents($path);
        self::$map = json_decode(str_replace("\uFEFF", '', $raw), true) ?: [];

        return self::$map;
    }

    public static function normalizeDigits(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);
        if (strlen($digits) === 10 && str_starts_with($digits, '0')) {
            $digits = '94'.substr($digits, 1);
        }

        return $digits;
    }

    /** WhatsApp LID numbers are typically 13+ digits and stored in lid_map.json */
    public static function isLikelyLid(string $phone): bool
    {
        $digits = self::normalizeDigits($phone);
        if ($digits === '') {
            return false;
        }

        $map = self::loadMap();
        if (isset($map[$digits.'@lid'])) {
            return true;
        }

        // E.164 max is 15; LIDs often 13–18 digits
        if (strlen($digits) > 15) {
            return true;
        }

        if (strlen($digits) >= 13) {
            return true;
        }

        return false;
    }

    /** Resolve LID digits to real phone using lid_map.json; returns input if not mapped. */
    public static function resolve(string $phone): string
    {
        $digits = self::normalizeDigits($phone);
        if ($digits === '') {
            return $phone;
        }

        if (! self::isLikelyLid($digits)) {
            return $digits;
        }

        $map = self::loadMap();
        $mapped = $map[$digits.'@lid'] ?? null;
        if ($mapped) {
            $real = self::normalizeDigits(explode('@', (string) $mapped)[0]);
            if ($real !== '' && ! self::isLikelyLid($real)) {
                return $real;
            }
        }

        return $digits;
    }

    /**
     * Merge all records stored under a LID to the real phone number.
     */
    public static function mergeInDatabase(string $lidPhone, string $realPhone): int
    {
        $lid  = self::normalizeDigits($lidPhone);
        $real = self::normalizeDigits($realPhone);

        if ($lid === '' || $real === '' || $lid === $real) {
            return 0;
        }

        if (self::isLikelyLid($real)) {
            return 0;
        }

        $count = 0;

        $count += WhatsappMessage::where('phone_number', $lid)->update(['phone_number' => $real]);

        WhatsappContact::where('phone_number', $lid)->each(function (WhatsappContact $c) use ($real) {
            $existing = WhatsappContact::where('phone_number', $real)->first();
            if ($existing) {
                if (! $existing->name && $c->name) {
                    $existing->name = $c->name;
                }
                if (! $existing->wa_name && $c->wa_name) {
                    $existing->wa_name = $c->wa_name;
                }
                if (! $existing->profile_picture && $c->profile_picture) {
                    $existing->profile_picture = $c->profile_picture;
                }
                $existing->save();
                $c->delete();
            } else {
                $c->update(['phone_number' => $real]);
            }
        });

        WhatsappChatAssignment::where('phone_number', $lid)->update(['phone_number' => $real]);

        self::persistMapping($lid, $real);

        Log::info("WhatsApp LID merged: {$lid} → {$real} ({$count} messages)");

        return $count;
    }

    public static function persistMapping(string $lid, string $real): void
    {
        $path = self::mapPath();
        $map  = file_exists($path) ? (json_decode(file_get_contents($path), true) ?: []) : [];
        $map[$lid.'@lid'] = $real.'@s.whatsapp.net';
        @file_put_contents($path, json_encode($map, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        self::$map = $map;
    }

    /** Merge every LID in lid_map.json into the database. */
    public static function mergeAllFromMap(): int
    {
        $total = 0;
        foreach (self::loadMap(true) as $lidJid => $realJid) {
            $lid  = self::normalizeDigits(explode('@', (string) $lidJid)[0]);
            $real = self::normalizeDigits(explode('@', (string) $realJid)[0]);
            if ($lid && $real && $lid !== $real) {
                $total += self::mergeInDatabase($lid, $real);
            }
        }

        return $total;
    }
}

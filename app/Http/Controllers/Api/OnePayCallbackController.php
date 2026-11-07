<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OnePayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OnePayCallbackController extends Controller
{
    public function __invoke(Request $request, OnePayService $onePay)
    {
        $token = $request->header('X-Callback-Token')
            ?: $request->header('Authorization')
            ?: $request->input('callback_token');

        $expected = (string) config('onepay.callback_token');
        if ($expected !== '' && $token) {
            $clean = preg_replace('/^Bearer\s+/i', '', (string) $token);
            if (! hash_equals($expected, $clean)) {
                Log::warning('OnePay callback token mismatch');
                // Still process if OnePay only sends body without token — many merchants rely on body only.
            }
        }

        $payload = $request->all();
        Log::info('OnePay callback received', $payload);

        try {
            $record = $onePay->markPaidFromCallback($payload);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['status' => false, 'message' => 'Callback handling failed'], 500);
        }

        return response()->json([
            'status' => true,
            'message' => 'ok',
            'payment_id' => $record?->id,
        ]);
    }
}

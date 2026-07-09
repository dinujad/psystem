<?php

namespace App\Http\Controllers;

use App\EmwaApiClient;
use App\Services\LinkedWhatsappSender;
use App\Services\WhatsappService;
use Illuminate\Http\Request;

class EmwaApiController extends Controller
{
    public function __construct(
        private WhatsappService $whatsapp,
        private LinkedWhatsappSender $linkedWhatsapp
    ) {}

    public function sendMessage(Request $request)
    {
        return $this->handle($request, function (string $phone) use ($request) {
            $message = trim((string) $request->input('message', ''));
            if ($message === '') {
                return $this->error('message is required.');
            }

            $result = $this->whatsapp->sendMessage($phone, $message);

            return $this->fromWhatsappResult($result, 'Message sent successfully');
        });
    }

    public function sendImage(Request $request)
    {
        return $this->handle($request, function (string $phone) use ($request) {
            $imageUrl = trim((string) $request->input('image_url', ''));
            if ($imageUrl === '') {
                return $this->error('image_url is required.');
            }

            $caption = trim((string) $request->input('caption', ''));
            $result = $this->whatsapp->sendImageFromUrl($phone, $imageUrl, $caption);

            return $this->fromWhatsappResult($result, 'Image sent successfully');
        });
    }

    public function sendLinkPreview(Request $request)
    {
        return $this->handle($request, function (string $phone) use ($request) {
            $url = trim((string) $request->input('url', ''));
            if ($url === '') {
                return $this->error('url is required.');
            }

            $text = trim((string) $request->input('text', ''));
            $title = trim((string) $request->input('title', ''));
            $description = trim((string) $request->input('description', ''));

            $result = $this->whatsapp->sendLinkPreview($phone, $text, $url, $title, $description);

            return $this->fromWhatsappResult($result, 'Link message sent successfully');
        });
    }

    public function sendVoice(Request $request)
    {
        return $this->handle($request, function (string $phone) use ($request) {
            $audioUrl = trim((string) $request->input('audio_url', ''));
            if ($audioUrl === '') {
                return $this->error('audio_url is required.');
            }

            $result = $this->whatsapp->sendAudioFromUrl($phone, $audioUrl);

            return $this->fromWhatsappResult($result, 'Voice message sent successfully');
        });
    }

    public function sendPoll(Request $request)
    {
        return $this->handle($request, function (string $phone) use ($request) {
            $question = trim((string) $request->input('question', ''));
            $options = trim((string) $request->input('options', ''));
            if ($question === '' || $options === '') {
                return $this->error('question and options are required.');
            }

            $optionList = array_values(array_filter(array_map('trim', explode(',', $options))));
            if (count($optionList) < 2) {
                return $this->error('At least two poll options are required.');
            }

            $selectableCount = max(1, (int) $request->input('selectable_count', 1));
            $result = $this->whatsapp->sendPoll($phone, $question, $optionList, $selectableCount);

            return $this->fromWhatsappResult($result, 'Poll sent successfully');
        });
    }

    public function sendStatusText(Request $request)
    {
        return $this->handleStatus($request, function () use ($request) {
            $text = trim((string) $request->input('text', ''));
            if ($text === '') {
                return $this->error('text is required.');
            }

            $result = $this->whatsapp->sendStatusText(
                $text,
                $request->input('background_color'),
                $request->input('font')
            );

            if (empty($result['success'])) {
                return $this->error($result['message'] ?? 'Failed to send status.');
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Status sent successfully',
                'data' => [
                    'status_id' => $result['status_id'] ?? null,
                    'message_id' => $result['message_id'] ?? null,
                    'recipients' => $result['recipients'] ?? null,
                ],
            ]);
        });
    }

    public function deleteStatus(Request $request)
    {
        return $this->handleStatus($request, function () use ($request) {
            $statusId = trim((string) $request->input('status_id', ''));
            if ($statusId === '') {
                return $this->error('status_id is required.');
            }

            $result = $this->whatsapp->deleteStatus($statusId);

            if (empty($result['success'])) {
                return $this->error($result['message'] ?? 'Failed to delete status.');
            }

            return response()->json([
                'status' => 'success',
                'message' => $result['message'] ?? 'Status deleted successfully',
            ]);
        });
    }

    private function handle(Request $request, callable $callback)
    {
        $client = $this->authenticate($request);
        if (! $client) {
            return $this->error('Invalid email or API key.', 401);
        }

        if (! $this->linkedWhatsapp->isConnected()) {
            return $this->error('WhatsApp is not connected. Please link your device in the admin panel.', 503);
        }

        $phone = LinkedWhatsappSender::normalizePhone((string) $request->input('phone', ''));
        if (empty($phone)) {
            return $this->error('Valid phone number is required (e.g. 947XXXXXXXX).', 422);
        }

        $result = $callback($phone);
        if ($result instanceof \Illuminate\Http\JsonResponse) {
            return $result;
        }

        return $result;
    }

    private function handleStatus(Request $request, callable $callback)
    {
        $client = $this->authenticate($request);
        if (! $client) {
            return $this->error('Invalid email or API key.', 401);
        }

        if (! $this->linkedWhatsapp->isConnected()) {
            return $this->error('WhatsApp is not connected. Please link your device in the admin panel.', 503);
        }

        return $callback();
    }

    private function authenticate(Request $request): ?EmwaApiClient
    {
        $email = trim((string) $request->input('email', ''));
        $apiKey = trim((string) $request->input('api_key', ''));

        if ($email === '' || $apiKey === '') {
            return null;
        }

        return EmwaApiClient::where('email', $email)
            ->where('api_key', $apiKey)
            ->where('is_active', true)
            ->first();
    }

    private function fromWhatsappResult(array $result, string $successMessage)
    {
        if (empty($result['success'])) {
            return $this->error($result['message'] ?? 'Failed to send WhatsApp message.');
        }

        return response()->json([
            'status' => 'success',
            'message' => $successMessage,
            'data' => [
                'message_id' => $result['message_id'] ?? null,
            ],
        ]);
    }

    private function error(string $message, int $code = 422)
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
        ], $code);
    }
}

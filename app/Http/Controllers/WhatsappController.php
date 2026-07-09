<?php

namespace App\Http\Controllers;

use App\Http\Controllers\WhatsappAgentController;
use App\Services\WhatsappService;
use App\Services\WhatsappLidResolver;
use App\WhatsappChatAssignment;
use App\WhatsappContact;
use App\WhatsappMessage;
use Illuminate\Http\Request;

class WhatsappController extends Controller
{
    protected WhatsappService $whatsappService;

    public function __construct(WhatsappService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    public function showQr()
    {
        if (! auth()->user()->can('send_notifications')) {
            abort(403, 'Unauthorized action.');
        }

        return view('whatsapp.link');
    }

    public function inbox(Request $request)
    {
        $user    = auth()->user();
        $isAdmin = $user->can('send_notifications');
        $isAgent = $user->can('whatsapp.agent');

        if (! $isAdmin && ! $isAgent) {
            abort(403, 'Unauthorized action.');
        }

        // Merge any LID → real phone mappings discovered by WhatsApp service
        WhatsappLidResolver::mergeAllFromMap();

        $threads = $this->threadQuery($isAdmin, $isAgent)->get();

        // Build a phone → contact map for the initial server-side render
        $phones   = $threads->pluck('phone_number')->all();
        $contacts = WhatsappContact::whereIn('phone_number', $phones)
            ->with('labels')
            ->get()
            ->keyBy('phone_number');

        // Assignment map for initial render
        $assignments = WhatsappChatAssignment::whereIn('phone_number', $phones)
            ->where('status', 'open')
            ->with('agent')
            ->get()
            ->keyBy('phone_number');

        $status    = $this->whatsappService->getStatus();
        $userId    = $user->id;
        $openPhone = preg_replace('/\D/', '', (string) $request->query('phone', ''));

        return response()
            ->view('whatsapp.inbox', compact('threads', 'status', 'contacts', 'assignments', 'userId', 'isAdmin', 'openPhone'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    public function conversation(Request $request, $phone)
    {
        if (! auth()->user()->can('send_notifications')) {
            abort(403, 'Unauthorized action.');
        }

        $messages = WhatsappMessage::where('phone_number', $phone)
            ->orderBy('created_at')
            ->get();

        if ($request->ajax()) {
            return response()->json(['messages' => $messages]);
        }

        $threads = WhatsappMessage::selectRaw('phone_number, MAX(created_at) as last_at, COUNT(*) as total')
            ->groupBy('phone_number')
            ->orderByDesc('last_at')
            ->paginate(30);

        $status = $this->whatsappService->getStatus();

        return view('whatsapp.inbox', compact('threads', 'messages', 'phone', 'status'));
    }

    public function sendFromInbox(Request $request)
    {
        if (! auth()->user()->can('send_notifications')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'phone_number' => ['required', 'string'],
            'message'      => ['nullable', 'string', 'max:4096'],
            'file'         => ['nullable', 'file', 'max:16384', 'mimes:jpg,jpeg,png,gif,webp,pdf,mp4,ogg,mp3'],
        ]);

        // Normalize: strip non-digits, auto-add Sri Lanka 94 if starts with 0
        $rawPhone = preg_replace('/\D/', '', $request->input('phone_number'));
        if (strlen($rawPhone) === 10 && str_starts_with($rawPhone, '0')) {
            $rawPhone = '94' . substr($rawPhone, 1);
        }
        $phone = $rawPhone;
        $message = $request->input('message', '');
        $media   = [];

        if ($request->hasFile('file') && $request->file('file')->isValid()) {
            $file     = $request->file('file');
            $mime     = $file->getMimeType();
            $origName = $file->getClientOriginalName();
            $ext      = strtolower($file->getClientOriginalExtension());

            // Save to public storage
            $dir  = storage_path('app/public/whatsapp');
            if (! is_dir($dir)) mkdir($dir, 0775, true);
            $fname = 'wa_' . uniqid() . '.' . $ext;
            $file->move($dir, $fname);
            $localPath = 'whatsapp/' . $fname;

            $mediaType = str_starts_with($mime, 'image/') ? 'image'
                       : ($mime === 'application/pdf' ? 'document'
                       : (str_starts_with($mime, 'video/') ? 'video' : 'document'));

            $media = [
                'media_type'     => $mediaType,
                'media_base64'   => base64_encode(file_get_contents($dir . '/' . $fname)),
                'media_mimetype' => $mime,
                'media_filename' => $origName,
                '_local_path'    => $localPath,
            ];

            if (! $message) $message = $origName;
        }

        if (! $message && empty($media)) {
            return response()->json(['success' => false, 'message' => 'Message or file required.'], 422);
        }

        $result = $this->whatsappService->sendMessage($phone, $message, $media);

        return response()->json($result);
    }

    public function fixLid(\Illuminate\Http\Request $request)
    {
        if (! auth()->user()->can('send_notifications')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'lid_phone'  => ['required', 'string'],
            'real_phone' => ['required', 'string'],
        ]);

        $lid  = preg_replace('/\D/', '', $validated['lid_phone']);
        $real = preg_replace('/\D/', '', $validated['real_phone']);

        if (strlen($real) < 8) {
            return response()->json(['success' => false, 'message' => 'Invalid phone number.']);
        }

        // Auto-add Sri Lanka country code
        if (strlen($real) === 10 && str_starts_with($real, '0')) {
            $real = '94' . substr($real, 1);
        }

        $count = WhatsappLidResolver::mergeInDatabase($lid, $real);

        if ($count === 0 && ! WhatsappLidResolver::isLikelyLid($lid)) {
            return response()->json(['success' => false, 'message' => 'No messages found for this number.']);
        }

        return response()->json([
            'success' => true,
            'message' => $count > 0 ? "Updated {$count} message(s): {$lid} → {$real}" : "Mapped {$lid} → {$real} for future messages.",
            'real_phone' => $real,
            'merged' => $count,
        ]);
    }

    public function lidMergeWebhook(Request $request)
    {
        $apiKey = $request->header('x-api-key');
        $expectedKey = config('services.whatsapp.api_key');

        if (empty($expectedKey) || $apiKey !== $expectedKey) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'lid'  => ['required', 'string'],
            'real' => ['required', 'string'],
        ]);

        $count = WhatsappLidResolver::mergeInDatabase($validated['lid'], $validated['real']);

        return response()->json(['success' => true, 'merged' => $count]);
    }

    public function serveMedia(string $path)
    {
        $fullPath = storage_path('app/public/' . $path);
        if (! file_exists($fullPath)) abort(404);

        return response()->file($fullPath, [
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }

    public function pollThreads(Request $request)
    {
        $user = auth()->user();
        if (! $user->can('send_notifications') && ! $user->can('whatsapp.agent')) {
            abort(403, 'Unauthorized action.');
        }

        $isAdmin = $user->can('send_notifications');
        $isAgent = $user->can('whatsapp.agent');

        if (! $isAdmin && ! $isAgent) {
            abort(403);
        }

        WhatsappLidResolver::mergeAllFromMap();

        $threads = $this->threadQuery($isAdmin, $isAgent)->get();

        $phones      = $threads->pluck('phone_number')->all();
        $contacts    = WhatsappContact::whereIn('phone_number', $phones)
            ->with('labels')
            ->get()
            ->keyBy('phone_number');
        $assignments = WhatsappChatAssignment::whereIn('phone_number', $phones)
            ->where('status', 'open')
            ->with('agent')
            ->get()
            ->keyBy('phone_number');

        return response()->json([
            'threads' => $threads->map(function ($t) use ($contacts, $assignments, $user) {
                $c = $contacts->get($t->phone_number);
                $a = $assignments->get($t->phone_number);
                return [
                    'phone_number'    => $t->phone_number,
                    'last_at'         => $t->last_at,
                    'unread_count'    => (int) $t->unread_count,
                    'last_message'    => $t->last_message,
                    'last_direction'  => $t->last_direction,
                    'last_media_type' => $t->last_media_type,
                    'contact_name'    => $c?->name,
                    'wa_name'         => $c?->wa_name,
                    'display_name'    => $c ? $c->displayName() : ('+'.$t->phone_number),
                    'has_avatar'      => $c ? $c->hasProfilePicture() : false,
                    'labels'          => $c ? $c->labels->map(fn ($l) => ['id' => $l->id, 'name' => $l->name, 'color' => $l->color])->all() : [],
                    'assignment'      => $a ? [
                        'agent_id'   => $a->assigned_to,
                        'agent_name' => $a->agent ? WhatsappAgentController::agentDisplayName($a->agent) : null,
                        'is_me'      => $a->assigned_to === $user->id,
                    ] : null,
                ];
            }),
        ]);
    }

    public function markRead(Request $request, $phone)
    {
        $user = auth()->user();
        if (! $user->can('send_notifications') && ! $user->can('whatsapp.agent')) {
            abort(403, 'Unauthorized action.');
        }

        $normalized = preg_replace('/\D/', '', $phone);

        WhatsappMessage::where('phone_number', $normalized)
            ->where('direction', 'in')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    public function pollMessages(Request $request, $phone)
    {
        $user = auth()->user();
        if (! $user->can('send_notifications') && ! $user->can('whatsapp.agent')) {
            abort(403, 'Unauthorized action.');
        }

        $after = $request->query('after', 0);

        $messages = WhatsappMessage::where('phone_number', $phone)
            ->when($after, fn ($q) => $q->where('id', '>', $after))
            ->orderBy('created_at')
            ->get()
            ->map(function ($m) {
                return [
                    'id'             => $m->id,
                    'direction'      => $m->direction,
                    'message'        => $m->message,
                    'status'         => $m->status,
                    'created_at'     => $m->created_at->format('Y-m-d H:i:s'),
                    'media_type'     => $m->media_type,
                    'media_path'     => $m->media_path,
                    'media_filename' => $m->media_filename,
                    'media_mimetype' => $m->media_mimetype,
                ];
            });

        return response()->json(['messages' => $messages]);
    }

    public function qr()
    {
        if (! auth()->user()->can('send_notifications')) {
            abort(403, 'Unauthorized action.');
        }

        return response()->json($this->whatsappService->getQrCode());
    }

    public function status()
    {
        if (! auth()->user()->can('send_notifications')) {
            abort(403, 'Unauthorized action.');
        }

        return response()->json($this->whatsappService->getStatus());
    }

    public function syncStatus()
    {
        $user = auth()->user();
        if (! $user->can('send_notifications') && ! $user->can('whatsapp.agent')) {
            abort(403, 'Unauthorized action.');
        }

        return response()->json($this->whatsappService->getHealth());
    }

    public function send(Request $request)
    {
        if (! auth()->user()->can('send_notifications')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'number' => ['required', 'string', 'regex:/^\d{8,15}$/'],
            'message' => ['required', 'string', 'max:4096'],
        ], [
            'number.regex' => 'Phone number must be in international format without + or spaces (8–15 digits).',
        ]);

        $result = $this->whatsappService->sendMessage(
            $validated['number'],
            $validated['message']
        );

        $status = ! empty($result['success']) ? 200 : 422;

        return response()->json($result, $status);
    }

    public function logout()
    {
        if (! auth()->user()->can('send_notifications')) {
            abort(403, 'Unauthorized action.');
        }

        return response()->json($this->whatsappService->logout());
    }

    public function incomingWebhook(Request $request)
    {
        $apiKey = $request->header('x-api-key');
        $expectedKey = config('services.whatsapp.api_key');

        if (empty($expectedKey) || $apiKey !== $expectedKey) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'from'           => ['required', 'string'],
            'message'        => ['nullable', 'string'],
            'timestamp'      => ['nullable', 'string'],
            'message_id'     => ['nullable', 'string'],
            'direction'      => ['nullable', 'in:in,out'],
            'is_history'     => ['nullable', 'boolean'],
            'media_type'     => ['nullable', 'string'],
            'media_mimetype' => ['nullable', 'string'],
            'media_filename' => ['nullable', 'string'],
            'media_base64'   => ['nullable', 'string'],
        ]);

        $message = $this->whatsappService->storeIncomingMessage($validated);

        // Only run bot automation for live incoming messages (not history / outgoing sync)
        $isHistory = filter_var($validated['is_history'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $direction = $validated['direction'] ?? 'in';
        if (! $isHistory && $direction === 'in') {
            \App\Jobs\ProcessBotMessageJob::dispatch(
                $validated['from'],
                $validated['message'] ?? null
            );
        }

        return response()->json(['success' => true, 'id' => $message->id]);
    }

    public function contactWebhook(Request $request)
    {
        $apiKey = $request->header('x-api-key');
        $expectedKey = config('services.whatsapp.api_key');

        if (empty($expectedKey) || $apiKey !== $expectedKey) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'contacts'                   => ['required', 'array'],
            'contacts.*.phone'           => ['required', 'string'],
            'contacts.*.wa_name'         => ['nullable', 'string', 'max:120'],
            'contacts.*.profile_picture' => ['nullable', 'string'],
        ]);

        $count = $this->whatsappService->syncDeviceContacts($validated['contacts']);

        return response()->json(['success' => true, 'synced' => $count]);
    }

    public function syncContacts()
    {
        $user = auth()->user();
        if (! $user->can('send_notifications') && ! $user->can('whatsapp.agent')) {
            abort(403, 'Unauthorized action.');
        }

        return response()->json($this->whatsappService->triggerContactsSync());
    }

    public function serveAvatar(string $phone)
    {
        $user = auth()->user();
        if (! $user->can('send_notifications') && ! $user->can('whatsapp.agent')) {
            abort(403, 'Unauthorized action.');
        }

        $normalized = preg_replace('/\D/', '', $phone);
        $contact = WhatsappContact::where('phone_number', $normalized)->first();

        if (! $contact || ! $contact->hasProfilePicture()) {
            abort(404);
        }

        $fullPath = storage_path('app/public/'.$contact->profile_picture);

        return response()->file($fullPath, [
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }

    /** Save or update the display name for a contact. */
    public function saveContact(Request $request, string $phone)
    {
        if (! auth()->user()->can('send_notifications')) {
            abort(403, 'Unauthorized action.');
        }

        $data = $request->validate([
            'name'  => ['required', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $contact = WhatsappContact::updateOrCreate(
            ['phone_number' => $phone],
            $data
        );

        return response()->json(['success' => true, 'contact' => $contact]);
    }

    /** Return the saved contact for a phone number (or null). */
    public function getContact(string $phone)
    {
        $user = auth()->user();
        if (! $user->can('send_notifications') && ! $user->can('whatsapp.agent')) {
            abort(403, 'Unauthorized action.');
        }

        $contact = WhatsappContact::where('phone_number', $phone)
            ->with('labels')
            ->first();

        return response()->json([
            'contact' => $contact ? [
                'phone_number'    => $contact->phone_number,
                'name'            => $contact->name,
                'wa_name'         => $contact->wa_name,
                'display_name'    => $contact->displayName(),
                'has_avatar'      => $contact->hasProfilePicture(),
                'notes'           => $contact->notes,
                'labels'          => $contact->labels,
            ] : null,
        ]);
    }

    /** Delete all messages for a phone number (delete the whole chat). */
    public function deleteChat(string $phone)
    {
        if (! auth()->user()->can('send_notifications')) {
            abort(403, 'Unauthorized action.');
        }

        WhatsappMessage::where('phone_number', $phone)->delete();

        return response()->json(['success' => true]);
    }

    private function threadQuery(bool $isAdmin = true, bool $isAgent = false)
    {
        $q = WhatsappMessage::selectRaw(
            'phone_number, MAX(created_at) as last_at, ' .
            'SUM(CASE WHEN direction = \'in\' AND read_at IS NULL THEN 1 ELSE 0 END) as unread_count, ' .
            'SUBSTRING_INDEX(GROUP_CONCAT(message ORDER BY created_at DESC SEPARATOR "|||"), "|||", 1) as last_message, ' .
            'SUBSTRING_INDEX(GROUP_CONCAT(direction ORDER BY created_at DESC SEPARATOR "|||"), "|||", 1) as last_direction, ' .
            'SUBSTRING_INDEX(GROUP_CONCAT(IFNULL(media_type,"") ORDER BY created_at DESC SEPARATOR "|||"), "|||", 1) as last_media_type'
        )
            ->groupBy('phone_number')
            ->orderByDesc('last_at');

        // Agents see only: (1) chats assigned to them, (2) unassigned/open chats.
        // Admins see everything.
        if (! $isAdmin && $isAgent) {
            $userId = auth()->id();
            $q->whereIn('phone_number', function ($sub) use ($userId) {
                $sub->select('phone_number')
                    ->from('whatsapp_chat_assignments')
                    ->where('status', 'open')
                    ->where(function ($inner) use ($userId) {
                        $inner->where('assigned_to', $userId)
                              ->orWhereNull('assigned_to');
                    });
            });
        }

        return $q;
    }
}

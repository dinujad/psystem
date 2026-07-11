<?php

namespace App\Http\Controllers;

use App\User;
use App\WhatsappChatAssignment;
use App\WhatsappContact;
use App\WhatsappInquiryStatusLog;
use App\WhatsappMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

/**
 * Manages WhatsApp chat assignments between admin and agent users.
 *
 * Admin (send_notifications): full access — can assign, transfer, close any chat.
 * Agent (whatsapp.agent):     can claim unassigned chats, view own assigned chats.
 */
class WhatsappAgentController extends Controller
{
    private function isAdmin(): bool
    {
        return auth()->user()->can('send_notifications');
    }

    private function isAgent(): bool
    {
        return auth()->user()->can('whatsapp.agent');
    }

    private function checkAccess()
    {
        if (! $this->isAdmin() && ! $this->isAgent()) {
            abort(403, 'Unauthorized action.');
        }
    }

    // ── Inquiry workflow statuses ────────────────────────────────────────────
    public static function inquiryStatuses(): array
    {
        return [
            'quotation_waiting'      => 'Quotation Waiting',
            'quotation_sent'         => 'Quotation Sent',
            'invoice_waiting'        => 'Invoice Waiting',
            'invoice_sent'           => 'Invoice Sent',
            'proforma_invoice_sent'  => 'Proforma Invoice Sent',
            'project_confirm'        => 'Project Confirm',
            'payment_received'       => 'Payment Received',
            'sent_to_production'     => 'Sent to Production',
        ];
    }

    public static function paymentMethods(): array
    {
        return [
            'cash'           => 'Cash',
            'bank_transfer'  => 'Bank Transfer',
            'card'           => 'Card',
            'cheque'         => 'Cheque',
            'online'         => 'Online Payment',
            'other'          => 'Other',
        ];
    }

    public static function statusBadgeColor(string $status): string
    {
        return match ($status) {
            'quotation_waiting'     => '#f59e0b',
            'quotation_sent'        => '#3b82f6',
            'invoice_waiting'       => '#f97316',
            'invoice_sent'          => '#6366f1',
            'proforma_invoice_sent' => '#8b5cf6',
            'project_confirm'       => '#0ea5e9',
            'payment_received'      => '#10b981',
            'sent_to_production'    => '#7c5cfc',
            default                 => '#6b7280',
        };
    }

    // ── Inquiry categories ───────────────────────────────────────────────────
    public static function categories(): array
    {
        return [
            'Product Inquiry'      => 'Product Inquiry',
            'Pricing'              => 'Pricing',
            'Order Status'         => 'Order Status',
            'Complaint'            => 'Complaint',
            'Delivery / Shipping'  => 'Delivery / Shipping',
            'Payment'              => 'Payment',
            'Technical Support'    => 'Technical Support',
            'Quotation Request'    => 'Quotation Request',
            'General Inquiry'      => 'General Inquiry',
            'Other'                => 'Other',
        ];
    }

    // ── Agent management page (admin only) ──────────────────────────────────

    public function agents()
    {
        if (! $this->isAdmin()) abort(403);

        $permission = Permission::where('name', 'whatsapp.agent')->first();
        $agents = $permission
            ? User::permission('whatsapp.agent')->get()
            : collect();

        // All threads with their assignments
        $assignments = WhatsappChatAssignment::with('agent')
            ->where('status', 'open')
            ->get()
            ->keyBy('phone_number');

        // Thread stats per agent
        $stats = WhatsappChatAssignment::where('status', 'open')
            ->whereNotNull('assigned_to')
            ->selectRaw('assigned_to, count(*) as chat_count')
            ->groupBy('assigned_to')
            ->pluck('chat_count', 'assigned_to');

        return view('whatsapp.agents.index', compact('agents', 'assignments', 'stats'));
    }

    // ── API endpoints ────────────────────────────────────────────────────────

    /** Admin assigns a chat to a specific agent. */
    public function assign(Request $request, string $phone)
    {
        if (! $this->isAdmin()) abort(403);

        $data = $request->validate([
            'agent_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $open = WhatsappChatAssignment::where('phone_number', $phone)->where('status', 'open')->first();
        if ($open) {
            $open->update(['assigned_to' => $data['agent_id'], 'assigned_by' => auth()->id()]);
        } else {
            WhatsappChatAssignment::create([
                'phone_number' => $phone,
                'assigned_to'  => $data['agent_id'],
                'assigned_by'  => auth()->id(),
                'status'       => 'open',
            ]);
        }

        $agent = User::find($data['agent_id']);
        return response()->json([
            'success'    => true,
            'agent_name' => $this->agentDisplayName($agent),
            'agent_id'   => $agent->id,
        ]);
    }

    /** Agent (or admin) claims an unassigned chat for themselves. */
    public function claim(Request $request, string $phone)
    {
        $this->checkAccess();

        $existing = WhatsappChatAssignment::where('phone_number', $phone)->where('status', 'open')->first();

        if ($existing && $existing->assigned_to && $existing->assigned_to !== auth()->id()) {
            if (! $this->isAdmin()) {
                return response()->json(['success' => false, 'message' => 'Chat is already assigned to another agent.'], 422);
            }
            $existing->update(['assigned_to' => auth()->id(), 'assigned_by' => auth()->id()]);
        } elseif ($existing) {
            $existing->update(['assigned_to' => auth()->id(), 'assigned_by' => auth()->id()]);
        } else {
            WhatsappChatAssignment::create([
                'phone_number' => $phone,
                'assigned_to'  => auth()->id(),
                'assigned_by'  => auth()->id(),
                'status'       => 'open',
            ]);
        }

        return response()->json([
            'success'    => true,
            'agent_name' => $this->agentDisplayName(auth()->user()),
            'agent_id'   => auth()->id(),
            'is_me'      => true,
        ]);
    }

    /** Transfer a chat from current agent to another agent. */
    public function transfer(Request $request, string $phone)
    {
        if (! $this->isAdmin() && ! $this->isAgent()) abort(403);

        $data = $request->validate([
            'agent_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $assignment = WhatsappChatAssignment::where('phone_number', $phone)->where('status', 'open')->first();

        if (! $this->isAdmin()) {
            if (! $assignment || $assignment->assigned_to !== auth()->id()) {
                return response()->json(['success' => false, 'message' => 'You can only transfer your own chats.'], 422);
            }
        }

        if ($assignment) {
            $assignment->update(['assigned_to' => $data['agent_id'], 'assigned_by' => auth()->id()]);
        } else {
            WhatsappChatAssignment::create([
                'phone_number' => $phone,
                'assigned_to'  => $data['agent_id'],
                'assigned_by'  => auth()->id(),
                'status'       => 'open',
            ]);
        }

        $agent = User::find($data['agent_id']);
        return response()->json([
            'success'    => true,
            'agent_name' => $this->agentDisplayName($agent),
            'agent_id'   => $agent->id,
        ]);
    }

    /** Close a chat — requires inquiry details. */
    public function close(Request $request, string $phone)
    {
        $this->checkAccess();

        $open = WhatsappChatAssignment::where('phone_number', $phone)->where('status', 'open')->first();

        if (! $this->isAdmin() && $open && $open->assigned_to !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'You can only close your own chats.'], 422);
        }

        $data = $request->validate([
            'customer_name'     => ['required', 'string', 'max:100'],
            'inquiry_category'  => ['required', 'string', 'max:80'],
            'inquiry_notes'     => ['nullable', 'string', 'max:1000'],
        ]);

        if ($open) {
            $open->update([
                'status'           => 'closed',
                'customer_name'    => $data['customer_name'],
                'inquiry_category' => $data['inquiry_category'],
                'inquiry_notes'    => $data['inquiry_notes'] ?? null,
                'inquiry_status'   => 'quotation_waiting',
                'closed_by'        => auth()->id(),
                'closed_at'        => now(),
                'status_updated_by' => auth()->id(),
                'status_updated_at' => now(),
            ]);

            WhatsappInquiryStatusLog::create([
                'assignment_id' => $open->id,
                'from_status'   => null,
                'to_status'     => 'quotation_waiting',
                'notes'         => 'Inquiry created on chat close',
                'updated_by'    => auth()->id(),
            ]);
        }

        return response()->json(['success' => true]);
    }

    /** Unassign without closing (admin only). */
    public function unassign(string $phone)
    {
        if (! $this->isAdmin()) abort(403);

        WhatsappChatAssignment::where('phone_number', $phone)->where('status', 'open')
            ->update(['assigned_to' => null, 'assigned_by' => null]);

        return response()->json(['success' => true]);
    }

    /** Update inquiry workflow status (admin or handling agent). */
    public function updateStatus(Request $request, int $id)
    {
        $this->checkAccess();

        $assignment = WhatsappChatAssignment::where('status', 'closed')->findOrFail($id);

        if (! $this->isAdmin() && $assignment->assigned_to !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'You can only update inquiries you handled.'], 422);
        }

        $statuses = array_keys(self::inquiryStatuses());
        $rules = [
            'inquiry_status' => ['required', 'string', 'in:' . implode(',', $statuses)],
            'status_notes'   => ['nullable', 'string', 'max:500'],
        ];

        if ($request->input('inquiry_status') === 'payment_received') {
            $rules['payment_amount']    = ['required', 'numeric', 'min:0.01'];
            $rules['payment_method']    = ['required', 'string', 'max:50'];
            $rules['payment_reference'] = ['nullable', 'string', 'max:100'];
        }

        if ($request->input('inquiry_status') === 'sent_to_production') {
            $rules['google_drive_url'] = ['nullable', 'url', 'max:500'];
        }

        $data = $request->validate($rules);
        $fromStatus = $assignment->inquiry_status;

        $update = [
            'inquiry_status'    => $data['inquiry_status'],
            'status_updated_by' => auth()->id(),
            'status_updated_at' => now(),
        ];

        if ($data['inquiry_status'] === 'payment_received') {
            $update['payment_amount']    = $data['payment_amount'];
            $update['payment_method']    = $data['payment_method'];
            $update['payment_reference'] = $data['payment_reference'] ?? null;
        } else {
            $update['payment_amount']    = null;
            $update['payment_method']    = null;
            $update['payment_reference'] = null;
        }

        $assignment->update($update);

        WhatsappInquiryStatusLog::create([
            'assignment_id'     => $assignment->id,
            'from_status'       => $fromStatus,
            'to_status'         => $data['inquiry_status'],
            'payment_amount'    => $update['payment_amount'] ?? null,
            'payment_method'    => $update['payment_method'] ?? null,
            'payment_reference' => $update['payment_reference'] ?? null,
            'notes'             => $data['status_notes'] ?? null,
            'updated_by'        => auth()->id(),
        ]);

        // Auto-create production job when first set to sent_to_production
        $prodJobNumber = null;
        if ($data['inquiry_status'] === 'sent_to_production' && $fromStatus !== 'sent_to_production') {
            $existingJob = \App\ProductionJob::where('inquiry_id', $assignment->id)->first();
            if (! $existingJob) {
                $newJob = \App\Http\Controllers\ProductionController::createFromInquiry(
                    $assignment,
                    $data['google_drive_url'] ?? null
                );
                $prodJobNumber = $newJob->job_number;
            }
        }

        return response()->json([
            'success'          => true,
            'inquiry_status'   => $data['inquiry_status'],
            'status_label'     => self::inquiryStatuses()[$data['inquiry_status']],
            'status_color'     => self::statusBadgeColor($data['inquiry_status']),
            'production_job'   => $prodJobNumber,
        ]);
    }

    /** Status change history for one inquiry. */
    public function statusHistory(int $id)
    {
        $this->checkAccess();

        $assignment = WhatsappChatAssignment::findOrFail($id);
        if (! $this->isAdmin() && $assignment->assigned_to !== auth()->id()) {
            abort(403);
        }

        $logs = $assignment->statusLogs()->with('updatedBy')->get()->map(fn ($log) => [
            'from'       => $log->from_status ? (self::inquiryStatuses()[$log->from_status] ?? $log->from_status) : '—',
            'to'         => self::inquiryStatuses()[$log->to_status] ?? $log->to_status,
            'payment'    => $log->payment_amount ? number_format($log->payment_amount, 2) : null,
            'method'     => $log->payment_method ? (self::paymentMethods()[$log->payment_method] ?? $log->payment_method) : null,
            'reference'  => $log->payment_reference,
            'notes'      => $log->notes,
            'updated_by' => $log->updatedBy ? self::agentDisplayName($log->updatedBy) : '—',
            'at'         => $log->created_at->format('d M Y, H:i'),
        ]);

        return response()->json(['logs' => $logs]);
    }

    /** Full inquiry detail page — admin or handling agent. */
    public function inquiryShow(int $id)
    {
        $this->checkAccess();

        $inquiry = WhatsappChatAssignment::with([
            'agent', 'closedBy', 'statusUpdatedBy', 'assignedBy',
            'statusLogs.updatedBy',
        ])->where('status', 'closed')->findOrFail($id);

        if (! $this->isAdmin() && $inquiry->assigned_to !== auth()->id()) {
            abort(403, 'You can only view inquiries you handled.');
        }

        $messages = WhatsappMessage::where('phone_number', $inquiry->phone_number)
            ->orderBy('created_at')
            ->get();

        $statuses   = self::inquiryStatuses();
        $payMethods = self::paymentMethods();
        $isAdmin    = $this->isAdmin();

        return view('whatsapp.inquiry_show', compact(
            'inquiry', 'messages', 'statuses', 'payMethods', 'isAdmin'
        ));
    }

    /** Admin: view all closed inquiry records with filters. */
    public function reports(Request $request)
    {
        $this->checkAccess();

        // Ensure agent permission exists (avoids Spatie PermissionDoesNotExist → 500)
        try {
            Permission::findOrCreate('whatsapp.agent', 'web');
        } catch (\Throwable $e) {
            \Log::warning('WhatsApp reports: could not ensure permission: '.$e->getMessage());
        }

        if (! \Illuminate\Support\Facades\Schema::hasTable('whatsapp_chat_assignments')) {
            return response()->view('whatsapp.inbox_error', [
                'message' => 'WhatsApp tables are missing. Run: php artisan migrate --force',
            ], 500);
        }

        try {
            $query = WhatsappChatAssignment::with(['agent', 'closedBy', 'statusUpdatedBy'])
                ->where('status', 'closed')
                ->orderByDesc('closed_at');

            if (! $this->isAdmin()) {
                $query->where('assigned_to', auth()->id());
            }

            if ($cat = $request->get('category')) {
                $query->where('inquiry_category', $cat);
            }
            if ($status = $request->get('inquiry_status')) {
                $query->where('inquiry_status', $status);
            }
            if ($agentId = $request->get('agent_id')) {
                $query->where('assigned_to', $agentId);
            }
            if ($from = $request->get('from')) {
                $query->whereDate('closed_at', '>=', $from);
            }
            if ($to = $request->get('to')) {
                $query->whereDate('closed_at', '<=', $to);
            }

            $records = $query->paginate(25)->withQueryString();

            $categoryStats = WhatsappChatAssignment::where('status', 'closed')
                ->when(! $this->isAdmin(), fn ($q) => $q->where('assigned_to', auth()->id()))
                ->selectRaw('inquiry_category, count(*) as total')
                ->groupBy('inquiry_category')
                ->orderByDesc('total')
                ->get();

            $statusStats = WhatsappChatAssignment::where('status', 'closed')
                ->when(! $this->isAdmin(), fn ($q) => $q->where('assigned_to', auth()->id()))
                ->selectRaw('inquiry_status, count(*) as total')
                ->groupBy('inquiry_status')
                ->orderByDesc('total')
                ->get();

            $agentStats = WhatsappChatAssignment::with('agent')
                ->where('status', 'closed')
                ->whereNotNull('assigned_to')
                ->when(! $this->isAdmin(), fn ($q) => $q->where('assigned_to', auth()->id()))
                ->selectRaw('assigned_to, count(*) as total')
                ->groupBy('assigned_to')
                ->orderByDesc('total')
                ->get();

            $agents = User::permission('whatsapp.agent')->get();
            $categories = self::categories();
            $statuses = self::inquiryStatuses();
            $payMethods = self::paymentMethods();

            return view('whatsapp.reports', compact(
                'records', 'categoryStats', 'statusStats', 'agentStats',
                'agents', 'categories', 'statuses', 'payMethods'
            ));
        } catch (\Throwable $e) {
            \Log::error('WhatsApp reports failed: '.$e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->view('whatsapp.inbox_error', [
                'message' => 'Inquiry Reports could not load. If columns are missing, run: php artisan migrate --force && php artisan db:seed --class=WhatsappAgentPermissionSeeder --force',
                'detail' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /** Return list of active agents for the assignment picker. */
    public function agentList()
    {
        $this->checkAccess();

        $agents = User::permission('whatsapp.agent')
            ->orWhereHas('permissions', fn ($q) => $q->where('name', 'send_notifications'))
            ->get()
            ->map(fn ($u) => [
                'id'   => $u->id,
                'name' => $this->agentDisplayName($u),
            ]);

        return response()->json(['agents' => $agents]);
    }

    /** Return assignment info for a specific phone. */
    public function assignmentFor(string $phone)
    {
        $this->checkAccess();

        $a = WhatsappChatAssignment::with('agent')
            ->where('phone_number', $phone)
            ->where('status', 'open')
            ->first();

        if (! $a) {
            return response()->json(['assignment' => null]);
        }

        return response()->json([
            'assignment' => [
                'agent_id'   => $a->assigned_to,
                'agent_name' => $a->agent ? $this->agentDisplayName($a->agent) : null,
                'is_me'      => $a->assigned_to === auth()->id(),
            ],
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public static function agentDisplayName(User $u): string
    {
        $name = trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''));
        return $name ?: $u->username;
    }
}

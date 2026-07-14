<?php

namespace App\Http\Controllers;

use App\BusinessLocation;
use App\InventoryMaterial;
use App\Product;
use App\ProductionEmployeeRating;
use App\ProductionJob;
use App\ProductionJobFile;
use App\ProductionJobMaterial;
use App\ProductionJobAssignment;
use App\ProductionJobSectionPlan;
use App\ProductionJobStage;
use App\ProductionJobTask;
use App\ProductionMaterialRequest;
use App\ProductionStageApproval;
use App\ProductionStageEmployee;
use App\PurchaseLine;
use App\Services\ProductionCostService;
use App\Services\ProductionNotifier;
use App\Services\WhatsappLidResolver;
use App\Transaction;
use App\Unit;
use App\User;
use App\Utils\ProductUtil;
use App\Variation;
use App\WhatsappChatAssignment;
use App\WhatsappContact;
use App\WhatsappMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductionController extends Controller
{
    private function checkAccess()
    {
        if ($this->isAdmin() || auth()->user()->can('production.access') || auth()->user()->can('production.manager')) {
            return;
        }

        if (ProductionStageEmployee::where('user_id', auth()->id())->exists()) {
            return;
        }

        abort(403, 'Unauthorized.');
    }

    private function isProductionManager(): bool
    {
        return auth()->user()->can('production.manager');
    }

    private function checkManagerAccess()
    {
        if (! $this->isProductionManager()) {
            abort(403, 'Production Manager access required.');
        }
    }

    private function checkTeamAdmin()
    {
        $businessId = session('business.id');
        if (! auth()->user()->hasRole('Admin#' . $businessId) && ! $this->isAdmin()) {
            abort(403, 'Only admin can manage production teams.');
        }
    }

    private function isAdmin(): bool
    {
        return auth()->user()->can('send_notifications');
    }

    private function hasFullProductionAccess(): bool
    {
        return $this->isAdmin()
            || auth()->user()->can('production.access')
            || auth()->user()->can('production.manager');
    }

    private function assignedStages(): array
    {
        return ProductionStageEmployee::stagesForUser(auth()->id());
    }

    private function canAccessStage(string $stage): bool
    {
        if ($this->hasFullProductionAccess()) {
            return true;
        }

        return in_array($stage, $this->assignedStages(), true);
    }

    private function canAccessJob(ProductionJob $job): bool
    {
        if ($this->hasFullProductionAccess()) {
            return true;
        }

        if (! $this->canAccessStage($job->current_stage)) {
            return false;
        }

        $userId = auth()->id();
        $stage  = $job->current_stage;

        $isHead = ProductionStageEmployee::where('stage', $stage)
            ->where('user_id', $userId)
            ->where('is_head', true)
            ->exists();

        if ($isHead) {
            return true;
        }

        $hasStageAssignments = ProductionJobAssignment::where('job_id', $job->id)
            ->where('stage', $stage)
            ->exists();

        if (! $hasStageAssignments) {
            return true;
        }

        return ProductionJobAssignment::where('job_id', $job->id)
            ->where('stage', $stage)
            ->where('user_id', $userId)
            ->exists();
    }

    private function isProductionSupervisor(): bool
    {
        return ProductionStageEmployee::where('stage', 'production')
            ->where('user_id', auth()->id())
            ->where('is_head', true)
            ->exists();
    }

    /** Workshop team can request materials; Production Manager issues after approve. */
    private function canRequestMaterials(ProductionJob $job): bool
    {
        if ($job->current_stage !== 'production') {
            return false;
        }

        if ($this->hasFullProductionAccess() || $this->isProductionSupervisor()) {
            return true;
        }

        if (! $this->canAccessStage('production')) {
            return false;
        }

        return $this->canAccessJob($job);
    }

    /** Direct issue / approve material requests (Production Manager). */
    private function canIssueMaterials(ProductionJob $job): bool
    {
        return $this->canRequestMaterials($job) && $this->isProductionManager();
    }

    private function businessUsers()
    {
        $businessId = request()->session()->get('user.business_id');

        return User::where('business_id', $businessId)
            ->user()
            ->where('is_cmmsn_agnt', 0)
            ->select([
                'id',
                DB::raw("CONCAT(COALESCE(surname, ''), ' ', COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) as full_name"),
                'email',
            ])
            ->orderBy('first_name')
            ->get();
    }

    // ── Kanban board ─────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $this->checkAccess();

        if (! $this->hasFullProductionAccess()) {
            $allowedStages = $this->assignedStages();
            if (empty($allowedStages)) {
                abort(403, 'You are not assigned to any production section.');
            }

            return redirect()->route('production.section', $allowedStages[0]);
        }

        $stageFilter = $request->get('stage');
        $search      = $request->get('q');

        $query = ProductionJob::with(['files', 'latestStage.movedBy', 'creator', 'materials', 'stageHistory', 'inquiry'])
            ->orderByRaw("FIELD(current_stage,'design','printing','production','quality','dispatch','completed')")
            ->orderByDesc('id');

        if ($stageFilter && $stageFilter !== 'all') {
            $query->where('current_stage', $stageFilter);
        }
        if ($search) {
            $query->where(fn ($q) =>
                $q->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('job_number', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%")
            );
        }

        if (! $this->hasFullProductionAccess()) {
            $allowedStages = $this->assignedStages();
            if (empty($allowedStages)) {
                abort(403, 'You are not assigned to any production section.');
            }
            $query->whereIn('current_stage', $allowedStages);
        }

        $jobs           = $query->get();
        $stages         = ProductionJob::allStages();
        $stageEmployees = ProductionStageEmployee::groupedByStage();
        $grouped        = [];
        $visibleStages  = $this->hasFullProductionAccess()
            ? array_keys($stages)
            : $this->assignedStages();

        foreach ($visibleStages as $s) {
            if (! isset($stages[$s])) {
                continue;
            }
            $grouped[$s] = $jobs->where('current_stage', $s)->values();
        }

        $isAdmin = $this->isAdmin();
        $canViewCosts = $this->hasFullProductionAccess();
        $jobCosts = [];

        if ($canViewCosts) {
            $costService = app(ProductionCostService::class);
            foreach ($jobs as $job) {
                $jobCosts[$job->id] = $costService->buildJobCostRow($job);
            }
        }

        $costSummary = ['total_cost' => 0, 'total_revenue' => 0, 'total_profit' => 0];
        if ($canViewCosts) {
            $costSummary = [
                'total_cost'    => round(collect($jobCosts)->sum('total_cost'), 2),
                'total_revenue' => round(collect($jobCosts)->sum('revenue'), 2),
                'total_profit'  => round(collect($jobCosts)->sum('profit'), 2),
            ];
        }

        return view('production.index', compact(
            'grouped', 'stages', 'jobs', 'stageFilter', 'search', 'stageEmployees', 'visibleStages', 'isAdmin',
            'canViewCosts', 'jobCosts', 'costSummary'
        ));
    }

    // ── Section dashboard (one section at a time) ────────────────────────────

    public function sectionDashboard(Request $request, string $stage)
    {
        $this->checkAccess();

        if (! in_array($stage, ProductionStageEmployee::workableStages(), true)) {
            abort(404);
        }

        if (! $this->canAccessStage($stage)) {
            abort(403, 'You are not assigned to this production section.');
        }

        $search = $request->get('q');
        $filter = $request->get('filter', 'all');

        $query = ProductionJob::with(['files', 'creator', 'sectionPlans', 'tasks', 'assignments.user'])
            ->where('current_stage', $stage)
            ->orderByDesc('id');

        if ($search) {
            $query->where(fn ($q) =>
                $q->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('job_number', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%")
            );
        }

        if (! $this->hasFullProductionAccess()) {
            $userId = auth()->id();
            $isHead = ProductionStageEmployee::where('stage', $stage)
                ->where('user_id', $userId)
                ->where('is_head', true)
                ->exists();

            if (! $isHead) {
                $query->where(function ($q) use ($userId, $stage) {
                    $q->whereDoesntHave('assignments', fn ($a) => $a->where('stage', $stage))
                        ->orWhereHas('assignments', fn ($a) => $a->where('stage', $stage)->where('user_id', $userId));
                });
            }
        }

        $jobs = $query->get();

        $pendingApprovals = ProductionStageApproval::pending()
            ->whereIn('job_id', $jobs->pluck('id'))
            ->where('from_stage', $stage)
            ->get()
            ->keyBy('job_id');

        $openStages = ProductionJobStage::whereIn('job_id', $jobs->pluck('id'))
            ->where('stage', $stage)
            ->whereNull('completed_at')
            ->get()
            ->keyBy('job_id');

        $taskStatus = function (ProductionJob $job) use ($openStages): string {
            $record = $openStages->get($job->id);
            if (! $record || ! $record->task_started_at) {
                return 'new';
            }
            if (! $record->task_ended_at) {
                return 'progress';
            }

            return 'ready';
        };

        $stats = [
            'total'    => $jobs->count(),
            'new'      => $jobs->filter(fn ($j) => $taskStatus($j) === 'new')->count(),
            'progress' => $jobs->filter(fn ($j) => $taskStatus($j) === 'progress')->count(),
            'ready'    => $jobs->filter(fn ($j) => $taskStatus($j) === 'ready')->count(),
        ];

        if ($filter !== 'all') {
            $jobs = $jobs->filter(fn ($j) => $taskStatus($j) === $filter)->values();
        }

        $stages         = ProductionJob::allStages();
        $stageLabel     = $stages[$stage];
        $stageColor     = ProductionJob::stageColor($stage);
        $nextStage      = ProductionJob::nextStage($stage);
        $nextStageLabel = $nextStage ? ($stages[$nextStage] ?? ucfirst($nextStage)) : null;
        $prevStage      = ProductionJob::prevStage($stage);
        $prevStageLabel = $prevStage ? ($stages[$prevStage] ?? ucfirst($prevStage)) : null;
        $isAdmin        = $this->hasFullProductionAccess();
        $workableStages = ProductionStageEmployee::workableStages();
        $myStages       = $this->hasFullProductionAccess() ? $workableStages : $this->assignedStages();
        $sectionHead    = ProductionStageEmployee::headForStage($stage)?->load('user');

        // Employees of the previous section — each can be rated individually.
        $prevStageEmployees = $prevStage
            ? ProductionStageEmployee::with('user')->where('stage', $prevStage)->get()->map(function ($e) {
                $u = $e->user;
                $name = $u ? trim(($u->surname ?? '') . ' ' . ($u->first_name ?? '') . ' ' . ($u->last_name ?? '')) : '';
                return [
                    'user_id'  => $e->user_id,
                    'name'     => $name ?: ($u->username ?? 'Employee'),
                    'initials' => $u ? $this->userInitials($u) : 'U',
                    'is_head'  => (bool) $e->is_head,
                ];
            })->values()
            : collect();

        $isProductionManager = $this->isProductionManager();

        return view('production.section', compact(
            'stage', 'stageLabel', 'stageColor', 'jobs', 'openStages', 'stats',
            'search', 'filter', 'nextStage', 'nextStageLabel', 'isAdmin',
            'myStages', 'stages', 'sectionHead', 'prevStage', 'prevStageLabel',
            'prevStageEmployees', 'pendingApprovals', 'isProductionManager'
        ));
    }

    // ── Create job (manual or from inquiry) ──────────────────────────────────

    public function create(Request $request)
    {
        return redirect()->route('production.start-job', $request->only('inquiry_id'));
    }

    public function store(Request $request)
    {
        $this->checkAccess();
        if (! $this->hasFullProductionAccess()) {
            abort(403, 'Only admin can create production jobs.');
        }

        $data = $request->validate([
            'customer_name'    => ['required', 'string', 'max:120'],
            'customer_phone'   => ['nullable', 'string', 'max:30'],
            'title'            => ['required', 'string', 'max:200'],
            'description'      => ['nullable', 'string', 'max:2000'],
            'google_drive_url' => ['nullable', 'url', 'max:500'],
            'priority'         => ['required', 'in:low,normal,high,urgent'],
            'due_date'         => ['nullable', 'date'],
            'inquiry_id'       => ['nullable', 'integer', 'exists:whatsapp_chat_assignments,id'],
            'files.*'          => ['nullable', 'file', 'max:20480'],
            'file_labels.*'    => ['nullable', 'string', 'max:80'],
        ]);

        $job = ProductionJob::create([
            'job_number'       => ProductionJob::generateJobNumber(),
            'inquiry_id'       => $data['inquiry_id'] ?? null,
            'customer_name'    => $data['customer_name'],
            'customer_phone'   => $data['customer_phone'] ?? null,
            'title'            => $data['title'],
            'description'      => $data['description'] ?? null,
            'google_drive_url' => $data['google_drive_url'] ?? null,
            'priority'         => $data['priority'],
            'due_date'         => $data['due_date'] ?? null,
            'current_stage'    => 'design',
            'created_by'       => auth()->id(),
        ]);

        // First stage entry
        ProductionJobStage::create([
            'job_id'     => $job->id,
            'stage'      => 'design',
            'notes'      => 'Job created — sent to Design Team.',
            'moved_by'   => auth()->id(),
            'started_at' => now(),
        ]);

        // Handle file uploads
        if ($request->hasFile('files')) {
            $this->attachFiles($job, $request->file('files'), $request->input('file_labels', []));
        }

        app(ProductionNotifier::class)->notifyStageTeam('design', $job);

        return redirect()->route('production.show', $job)->with('success', "Job {$job->job_number} created.");
    }

    // ── Start Job wizard (admin) ─────────────────────────────────────────────

    public function startJobForm(Request $request)
    {
        $this->checkAccess();
        if (! $this->hasFullProductionAccess()) {
            abort(403, 'Only admin can start production jobs.');
        }

        $inquiryId = $request->get('inquiry_id');
        $inquiry   = $inquiryId ? WhatsappChatAssignment::find($inquiryId) : null;

        $stages         = ProductionJob::allStages();
        $workableStages = ProductionStageEmployee::workableStages();
        $stageEmployees = ProductionStageEmployee::groupedByStage();
        $stageHeads     = [];
        foreach ($workableStages as $s) {
            $stageHeads[$s] = ProductionStageEmployee::headForStage($s)?->load('user');
        }

        return view('production.start-job', compact(
            'inquiry', 'stages', 'workableStages', 'stageEmployees', 'stageHeads'
        ));
    }

    public function searchWhatsappChats(Request $request)
    {
        $this->checkAccess();
        if (! $this->hasFullProductionAccess()) {
            abort(403);
        }

        $q = trim((string) $request->get('q', ''));

        $threads = WhatsappMessage::selectRaw('phone_number, MAX(created_at) as last_at')
            ->when($q !== '', fn ($query) => $query->where('phone_number', 'like', "%{$q}%"))
            ->groupBy('phone_number')
            ->orderByDesc('last_at')
            ->limit(25)
            ->get();

        $phones   = $threads->pluck('phone_number')->all();
        $contacts = WhatsappContact::whereIn('phone_number', $phones)->get()->keyBy('phone_number');
        $inquiries = WhatsappChatAssignment::whereIn('phone_number', $phones)
            ->where('status', 'open')
            ->get()
            ->keyBy('phone_number');

        if ($q !== '') {
            $namedPhones = WhatsappContact::where('name', 'like', "%{$q}%")
                ->orWhere('wa_name', 'like', "%{$q}%")
                ->limit(15)
                ->pluck('phone_number')
                ->all();

            foreach ($namedPhones as $phone) {
                if (! in_array($phone, $phones, true)) {
                    $phones[] = $phone;
                }
            }

            $inqPhones = WhatsappChatAssignment::where('status', 'open')
                ->where(function ($query) use ($q) {
                    $query->where('customer_name', 'like', "%{$q}%")
                        ->orWhere('phone_number', 'like', "%{$q}%");
                })
                ->limit(15)
                ->pluck('phone_number')
                ->all();

            foreach ($inqPhones as $phone) {
                if (! in_array($phone, $phones, true)) {
                    $phones[] = $phone;
                }
            }
        }

        $results = collect($phones)->unique()->take(25)->map(function ($phone) use ($contacts, $inquiries) {
            $resolved = WhatsappLidResolver::resolve($phone);
            $contact = $contacts->get($phone) ?? $contacts->get($resolved);
            $inquiry = $inquiries->get($phone) ?? $inquiries->get($resolved);

            $name = $contact?->displayName()
                ?? $inquiry?->customer_name
                ?? (WhatsappLidResolver::isLikelyLid($phone) ? 'Unknown Contact' : ('+' . $resolved));

            return [
                'phone'       => $resolved,
                'name'        => $name,
                'inquiry_id'  => $inquiry?->id,
                'category'    => $inquiry?->inquiry_category,
            ];
        })->values();

        return response()->json($results);
    }

    public function startJobStore(Request $request)
    {
        $this->checkAccess();
        if (! $this->hasFullProductionAccess()) {
            abort(403, 'Only admin can start production jobs.');
        }

        $workableStages = ProductionStageEmployee::workableStages();

        $data = $request->validate([
            'customer_name'    => ['required', 'string', 'max:120'],
            'customer_phone'   => ['nullable', 'string', 'max:30'],
            'title'            => ['required', 'string', 'max:200'],
            'description'      => ['nullable', 'string', 'max:2000'],
            'google_drive_url' => ['nullable', 'url', 'max:500'],
            'priority'         => ['required', 'in:low,normal,high,urgent'],
            'due_date'         => ['nullable', 'date', 'after_or_equal:today'],
            'inquiry_id'       => ['nullable', 'integer', 'exists:whatsapp_chat_assignments,id'],
            'whatsapp_phone'   => ['nullable', 'string', 'max:30'],
            'notify_customer'  => ['nullable', 'boolean'],
            'files.*'          => ['nullable', 'file', 'max:20480'],
            'file_labels.*'    => ['nullable', 'string', 'max:80'],
            'sections'         => ['nullable', 'array'],
            'sections.*.estimated_minutes' => ['nullable', 'integer', 'min:1', 'max:10080'],
            'sections.*.notes'             => ['nullable', 'string', 'max:500'],
            'tasks'            => ['nullable', 'array'],
            'tasks.*.stage'    => ['required_with:tasks', 'string', 'in:' . implode(',', $workableStages)],
            'tasks.*.title'    => ['required_with:tasks', 'string', 'max:200'],
            'tasks.*.estimated_minutes' => ['nullable', 'integer', 'min:1', 'max:10080'],
            'materials'        => ['nullable', 'array'],
            'materials.*.material_id' => ['required_with:materials', 'integer', 'exists:inventory_materials,id'],
            'materials.*.quantity'    => ['required_with:materials', 'numeric', 'min:0.001'],
            'assignments'             => ['nullable', 'array'],
            'assignments.*'           => ['nullable', 'array'],
            'assignments.*.*'         => ['integer', 'exists:users,id'],
        ]);

        $stageTeams = ProductionStageEmployee::groupedByStage();
        foreach ($data['assignments'] ?? [] as $stage => $userIds) {
            if (! in_array($stage, $workableStages, true)) {
                continue;
            }
            $allowed = ($stageTeams[$stage] ?? collect())->pluck('user_id')->map(fn ($id) => (int) $id)->all();
            foreach (array_unique($userIds ?? []) as $userId) {
                if (! in_array((int) $userId, $allowed, true)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "assignments.{$stage}" => ['Selected member is not in this section team.'],
                    ]);
                }
            }
        }

        $customerPhone = $data['customer_phone'] ?? null;
        if (! empty($data['whatsapp_phone'])) {
            $customerPhone = preg_replace('/\D/', '', $data['whatsapp_phone']);
        } elseif ($customerPhone) {
            $customerPhone = ProductionNotifier::normalizePhone($customerPhone);
        }

        $notifier = app(ProductionNotifier::class);
        $job      = null;

        try {
            DB::transaction(function () use ($request, $data, $customerPhone, $workableStages, &$job) {
            $job = ProductionJob::create([
                'job_number'       => ProductionJob::generateJobNumber(),
                'inquiry_id'       => $data['inquiry_id'] ?? null,
                'customer_name'    => $data['customer_name'],
                'customer_phone'   => $customerPhone ?: null,
                'title'            => $data['title'],
                'description'      => $data['description'] ?? null,
                'google_drive_url' => $data['google_drive_url'] ?? null,
                'priority'         => $data['priority'],
                'due_date'         => $data['due_date'] ?? null,
                'current_stage'    => 'design',
                'created_by'       => auth()->id(),
                'started_at'       => now(),
            ]);

            ProductionJobStage::create([
                'job_id'     => $job->id,
                'stage'      => 'design',
                'notes'      => 'Job started by admin — sent to Design Team.',
                'moved_by'   => auth()->id(),
                'started_at' => now(),
            ]);

            foreach ($workableStages as $stage) {
                $section = $data['sections'][$stage] ?? [];
                if (empty($section['estimated_minutes']) && empty($section['notes'])) {
                    continue;
                }

                ProductionJobSectionPlan::create([
                    'job_id'             => $job->id,
                    'stage'              => $stage,
                    'estimated_minutes'  => $section['estimated_minutes'] ?? null,
                    'notes'              => $section['notes'] ?? null,
                ]);
            }

            foreach ($data['tasks'] ?? [] as $idx => $task) {
                if (empty(trim($task['title'] ?? ''))) {
                    continue;
                }

                ProductionJobTask::create([
                    'job_id'            => $job->id,
                    'stage'             => $task['stage'],
                    'title'             => trim($task['title']),
                    'estimated_minutes' => $task['estimated_minutes'] ?? null,
                    'sort_order'        => $idx,
                ]);
            }

            foreach ($data['materials'] ?? [] as $row) {
                if (empty($row['material_id']) || empty($row['quantity'])) {
                    continue;
                }
                $this->issueMaterialToJob($job, (int) $row['material_id'], (float) $row['quantity'], 'production');
            }

            foreach ($data['assignments'] ?? [] as $stage => $userIds) {
                if (! in_array($stage, $workableStages, true)) {
                    continue;
                }
                foreach (array_unique($userIds ?? []) as $userId) {
                    ProductionJobAssignment::create([
                        'job_id'      => $job->id,
                        'stage'       => $stage,
                        'user_id'     => (int) $userId,
                        'assigned_by' => auth()->id(),
                    ]);
                }
            }

            if ($request->hasFile('files')) {
                $this->attachFiles($job, $request->file('files'), $request->input('file_labels', []));
            }
        });
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['materials' => $e->getMessage()]);
        }

        $notifier->notifyStageTeam('design', $job);

        $customerNotified = false;
        if ($request->boolean('notify_customer', true) && $job->customer_phone) {
            $result = $notifier->notifyCustomerJobStarted($job);
            $customerNotified = ! empty($result['success']);
            if ($customerNotified) {
                $job->update(['customer_notified_at_start' => true]);
            }
        }

        $msg = "Job {$job->job_number} started successfully.";
        if ($request->boolean('notify_customer', true) && $job->customer_phone) {
            $msg .= $customerNotified
                ? ' Customer notified on WhatsApp.'
                : ' Could not send WhatsApp to customer (check phone number).';
        }

        return redirect()->route('production.show', $job)->with('success', $msg);
    }

    private function issueMaterialToJob(ProductionJob $job, int $materialId, float $quantity, string $stage = 'production'): ProductionJobMaterial
    {
        $material = InventoryMaterial::findOrFail($materialId);

        if (! $material->hasEnoughStock($quantity)) {
            throw new \RuntimeException(
                "Insufficient stock for {$material->name}. Available: {$material->current_stock} " . ($material->unit?->abbreviation ?? '')
            );
        }

        $material->decrement('current_stock', $quantity);

        return ProductionJobMaterial::create([
            'job_id'      => $job->id,
            'stage'       => $stage,
            'material_id' => $materialId,
            'quantity'    => $quantity,
            'unit_price'  => $material->price_per_unit,
            'added_by'    => auth()->id(),
        ]);
    }

    // ── Admin: all jobs list ──────────────────────────────────────────────────

    public function allJobs(Request $request)
    {
        $this->checkAccess();
        if (! $this->hasFullProductionAccess()) {
            abort(403, 'Only admin can view all production jobs.');
        }

        $search = $request->get('q');
        $stage  = $request->get('stage');
        $status = $request->get('status', 'all'); // all | ongoing | completed

        $applyFilters = function ($q) use ($search, $stage, $status) {
            if ($search) {
                $q->where(fn ($sub) =>
                    $sub->where('job_number', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%")
                        ->orWhere('customer_phone', 'like', "%{$search}%")
                );
            }

            if ($status === 'completed') {
                $q->where('current_stage', 'completed');
            } elseif ($status === 'ongoing') {
                $q->where('current_stage', '!=', 'completed');
            }

            if ($stage && $stage !== 'all') {
                $q->where('current_stage', $stage);
            }
        };

        $query = ProductionJob::with(['creator', 'latestStage.movedBy', 'product', 'materials', 'stageHistory', 'inquiry'])->orderByDesc('id');
        $applyFilters($query);

        $jobs   = $query->paginate(25)->withQueryString();
        $stages = ProductionJob::allStages();
        $canViewCosts = $this->hasFullProductionAccess();
        $jobCosts = [];

        if ($canViewCosts) {
            $costService = app(ProductionCostService::class);
            foreach ($jobs as $job) {
                $jobCosts[$job->id] = $costService->buildJobCostRow($job);
            }
        }

        // Counts respect the search term but not the status/stage toggle.
        $baseCount = ProductionJob::query();
        if ($search) {
            $baseCount->where(fn ($sub) =>
                $sub->where('job_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
            );
        }
        $statusCounts = [
            'all'       => (clone $baseCount)->count(),
            'ongoing'   => (clone $baseCount)->where('current_stage', '!=', 'completed')->count(),
            'completed' => (clone $baseCount)->where('current_stage', 'completed')->count(),
        ];

        return view('production.jobs', compact('jobs', 'stages', 'search', 'stage', 'status', 'statusCounts', 'canViewCosts', 'jobCosts'));
    }

    // ── Job detail ────────────────────────────────────────────────────────────

    public function show(ProductionJob $job)
    {
        $this->checkAccess();
        if (! $this->canAccessJob($job)) {
            abort(403, 'You are not assigned to this production section.');
        }

        $job->load([
            'inquiry.agent', 'creator', 'files.uploader',
            'stageHistory.movedBy', 'product',
            'materials.material.unit', 'materials.addedBy',
            'sectionPlans', 'tasks', 'assignments.user',
        ]);

        $stages    = ProductionJob::allStages();
        $nextStage = ProductionJob::nextStage($job->current_stage);
        $isAdmin   = $this->hasFullProductionAccess();
        $canDoTask = $this->canAccessStage($job->current_stage);
        $canAdvance = $isAdmin || ($canDoTask && $nextStage);
        $canRequestMaterials = $this->canRequestMaterials($job);
        $canIssueMaterials = $this->canIssueMaterials($job);
        $isProductionManager = $this->isProductionManager();
        $pendingApproval = ProductionStageApproval::pending()
            ->where('job_id', $job->id)
            ->where('from_stage', $job->current_stage)
            ->latest('id')
            ->first();
        $pendingMaterialRequests = ProductionMaterialRequest::with('material.unit', 'requester')
            ->where('job_id', $job->id)
            ->pending()
            ->orderByDesc('id')
            ->get();

        $currentStageRecord = ProductionJobStage::where('job_id', $job->id)
            ->where('stage', $job->current_stage)
            ->whereNull('completed_at')
            ->latest('started_at')
            ->first();

        $productionStageRecord = $job->stageHistory->where('stage', 'production')->first();
        $sectionDashboardUrl   = route('production.section', $job->current_stage);

        // Per-employee ratings grouped by the stage that was rated.
        $employeeRatings = ProductionEmployeeRating::with(['ratedUser', 'ratedBy'])
            ->where('job_id', $job->id)
            ->orderBy('id')
            ->get()
            ->groupBy('rated_stage');

        $costService  = app(ProductionCostService::class);
        $materialCost = $costService->materialCost($job);
        $stageCost    = $costService->stageCost($job);
        $totalCost    = $costService->totalCost($job);
        $jobRevenue   = $isAdmin ? $costService->jobRevenue($job) : 0;
        $jobProfit    = $isAdmin ? $costService->jobProfit($job) : 0;
        $stageCosts   = $isAdmin ? $costService->stageCostBreakdown($job) : [];

        return view('production.show', compact(
            'job', 'stages', 'nextStage', 'canAdvance', 'isAdmin',
            'currentStageRecord', 'canDoTask', 'canIssueMaterials', 'canRequestMaterials', 'productionStageRecord', 'sectionDashboardUrl',
            'employeeRatings', 'materialCost', 'stageCost', 'totalCost', 'jobRevenue', 'jobProfit', 'stageCosts',
            'isProductionManager', 'pendingApproval', 'pendingMaterialRequests'
        ));
    }

    // ── Edit job ─────────────────────────────────────────────────────────────

    public function edit(ProductionJob $job)
    {
        $this->checkAccess();
        if (! $this->hasFullProductionAccess()) {
            abort(403, 'Only admin can edit production jobs.');
        }
        return view('production.edit', compact('job'));
    }

    public function update(Request $request, ProductionJob $job)
    {
        $this->checkAccess();
        if (! $this->hasFullProductionAccess()) {
            abort(403, 'Only admin can edit production jobs.');
        }

        $data = $request->validate([
            'title'            => ['required', 'string', 'max:200'],
            'description'      => ['nullable', 'string', 'max:2000'],
            'google_drive_url' => ['nullable', 'url', 'max:500'],
            'priority'         => ['required', 'in:low,normal,high,urgent'],
            'due_date'         => ['nullable', 'date'],
        ]);

        $job->update($data);

        return redirect()->route('production.show', $job)->with('success', 'Job updated.');
    }

    // ── Move to next stage ────────────────────────────────────────────────────

    public function advance(Request $request, ProductionJob $job)
    {
        $this->checkAccess();
        if (! $this->canAccessJob($job)) {
            return response()->json(['success' => false, 'message' => 'You are not assigned to this production section.'], 403);
        }

        $data = $request->validate([
            'notes'           => ['nullable', 'string', 'max:500'],
            'stage_rate'      => ['nullable', 'numeric', 'min:0'],
            'stage_rate_notes'=> ['nullable', 'string', 'max:300'],
            'quality_rating'  => ['nullable', 'integer', 'min:1', 'max:5'],
            'quality_comment' => ['nullable', 'string', 'max:500'],
        ]);

        $fromStage = $job->current_stage;
        $nextStage = ProductionJob::nextStage($fromStage);
        if (! $nextStage) {
            return response()->json(['success' => false, 'message' => 'Already at final stage.']);
        }

        $existing = ProductionStageApproval::pending()
            ->where('job_id', $job->id)
            ->where('from_stage', $fromStage)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'pending' => true,
                'message' => 'A move request is already waiting for Production Manager approval.',
            ]);
        }

        // Production Manager / Admin can move immediately; sections must request approval.
        if ($this->isProductionManager()) {
            return response()->json($this->executeStageAdvance($job, $data, auth()->id()));
        }

        ProductionStageApproval::create([
            'job_id'       => $job->id,
            'from_stage'   => $fromStage,
            'to_stage'     => $nextStage,
            'status'       => 'pending',
            'requested_by' => auth()->id(),
            'notes'        => $data['notes'] ?? null,
            'payload'      => [
                'notes'            => $data['notes'] ?? null,
                'stage_rate'       => $data['stage_rate'] ?? null,
                'stage_rate_notes' => $data['stage_rate_notes'] ?? null,
                'quality_rating'   => $data['quality_rating'] ?? null,
                'quality_comment'  => $data['quality_comment'] ?? null,
            ],
        ]);

        $stages = ProductionJob::allStages();

        return response()->json([
            'success'     => true,
            'pending'     => true,
            'message'     => 'Move request sent to Production Manager for approval.',
            'next_stage'  => $nextStage,
            'stage_label' => $stages[$nextStage] ?? $nextStage,
            'redirect'    => route('production.show', $job),
        ]);
    }

    public function managerDashboard(Request $request)
    {
        $this->checkManagerAccess();

        $tab = $request->get('tab', 'moves');
        if (! in_array($tab, ['moves', 'materials'], true)) {
            $tab = 'moves';
        }

        $filter = $request->get('filter', 'pending');
        if (! in_array($filter, ['pending', 'approved', 'rejected', 'all'], true)) {
            $filter = 'pending';
        }

        $moveCounts = [
            'pending'  => ProductionStageApproval::pending()->count(),
            'approved' => ProductionStageApproval::where('status', 'approved')->count(),
            'rejected' => ProductionStageApproval::where('status', 'rejected')->count(),
            'all'      => ProductionStageApproval::count(),
        ];

        $materialCounts = [
            'pending'  => ProductionMaterialRequest::pending()->count(),
            'approved' => ProductionMaterialRequest::where('status', 'approved')->count(),
            'rejected' => ProductionMaterialRequest::where('status', 'rejected')->count(),
            'all'      => ProductionMaterialRequest::count(),
        ];

        $counts = $tab === 'materials' ? $materialCounts : $moveCounts;
        $stages = ProductionJob::allStages();

        if ($tab === 'materials') {
            $query = ProductionMaterialRequest::with([
                'job', 'material.unit', 'requester', 'reviewer',
            ])->orderByDesc('id');

            if ($filter !== 'all') {
                $query->where('status', $filter);
            }

            $materialRequests = $query->paginate(30)->appends(['tab' => $tab, 'filter' => $filter]);
            $requests = collect();

            return view('production.manager', compact(
                'tab', 'filter', 'counts', 'moveCounts', 'materialCounts', 'stages', 'requests', 'materialRequests'
            ));
        }

        $query = ProductionStageApproval::with([
            'job', 'requester', 'reviewer',
        ])->orderByDesc('id');

        if ($filter !== 'all') {
            $query->where('status', $filter);
        }

        $requests = $query->paginate(30)->appends(['tab' => $tab, 'filter' => $filter]);
        $materialRequests = collect();

        return view('production.manager', compact(
            'tab', 'filter', 'counts', 'moveCounts', 'materialCounts', 'stages', 'requests', 'materialRequests'
        ));
    }

    public function approveMaterialRequest(Request $request, ProductionMaterialRequest $materialRequest)
    {
        $this->checkManagerAccess();

        if (! $materialRequest->isPending()) {
            return response()->json(['success' => false, 'message' => 'This request was already reviewed.'], 422);
        }

        $data = $request->validate([
            'review_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $job = ProductionJob::findOrFail($materialRequest->job_id);

        if ($job->current_stage !== 'production') {
            $materialRequest->update([
                'status' => 'rejected',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'review_notes' => 'Auto-rejected: job is not in Workshop.',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Job is not in Workshop anymore. Request closed.',
            ], 422);
        }

        try {
            $usage = $this->issueMaterialToJob(
                $job,
                (int) $materialRequest->material_id,
                (float) $materialRequest->quantity,
                'production'
            );
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $materialRequest->update([
            'status' => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_notes' => $data['review_notes'] ?? null,
            'issued_usage_id' => $usage->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Approved — raw material issued to Workshop job.',
        ]);
    }

    public function rejectMaterialRequest(Request $request, ProductionMaterialRequest $materialRequest)
    {
        $this->checkManagerAccess();

        if (! $materialRequest->isPending()) {
            return response()->json(['success' => false, 'message' => 'This request was already reviewed.'], 422);
        }

        $data = $request->validate([
            'review_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $materialRequest->update([
            'status' => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_notes' => $data['review_notes'] ?? 'Rejected by Production Manager.',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Material request rejected.',
        ]);
    }

    public function approveStageMove(Request $request, ProductionStageApproval $approval)
    {
        $this->checkManagerAccess();

        if (! $approval->isPending()) {
            return response()->json(['success' => false, 'message' => 'This request was already reviewed.'], 422);
        }

        $data = $request->validate([
            'review_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $job = ProductionJob::findOrFail($approval->job_id);

        if ($job->current_stage !== $approval->from_stage) {
            $approval->update([
                'status'       => 'rejected',
                'reviewed_by'  => auth()->id(),
                'reviewed_at'  => now(),
                'review_notes' => 'Auto-rejected: job is no longer in '.$approval->from_stage.'.',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Job has already moved from that section. Request closed.',
            ], 422);
        }

        $payload = array_merge($approval->payload ?? [], [
            'notes' => $approval->notes ?? ($approval->payload['notes'] ?? null),
        ]);

        $result = $this->executeStageAdvance($job, $payload, auth()->id());

        $approval->update([
            'status'       => 'approved',
            'reviewed_by'  => auth()->id(),
            'reviewed_at'  => now(),
            'review_notes' => $data['review_notes'] ?? null,
        ]);

        return response()->json(array_merge($result, [
            'message' => 'Approved — job moved to '.($result['stage_label'] ?? 'next section').'.',
        ]));
    }

    public function rejectStageMove(Request $request, ProductionStageApproval $approval)
    {
        $this->checkManagerAccess();

        if (! $approval->isPending()) {
            return response()->json(['success' => false, 'message' => 'This request was already reviewed.'], 422);
        }

        $data = $request->validate([
            'review_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $approval->update([
            'status'       => 'rejected',
            'reviewed_by'  => auth()->id(),
            'reviewed_at'  => now(),
            'review_notes' => $data['review_notes'] ?? 'Rejected by Production Manager.',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Request rejected. Job stays in the current section.',
        ]);
    }

    /**
     * Apply a stage advance (used by managers approving, or managers moving directly).
     */
    private function executeStageAdvance(ProductionJob $job, array $data, int $movedBy): array
    {
        $fromStage = $job->current_stage;
        $nextStage = ProductionJob::nextStage($fromStage);
        if (! $nextStage) {
            return ['success' => false, 'message' => 'Already at final stage.'];
        }

        $openStage = ProductionJobStage::where('job_id', $job->id)
            ->where('stage', $fromStage)
            ->whereNull('completed_at')
            ->latest('started_at')
            ->first();

        if ($openStage) {
            $openStage->update([
                'completed_at'     => now(),
                'stage_rate'       => $data['stage_rate'] ?? null,
                'stage_rate_notes' => $data['stage_rate_notes'] ?? null,
            ]);
        }

        if ($fromStage === 'quality' && ! empty($data['quality_rating'])) {
            $productionStage = ProductionJobStage::where('job_id', $job->id)
                ->where('stage', 'production')
                ->latest('started_at')
                ->first();
            if ($productionStage) {
                $productionStage->update([
                    'quality_rating'  => $data['quality_rating'],
                    'quality_comment' => $data['quality_comment'] ?? null,
                ]);
            }
        }

        $job->update(['current_stage' => $nextStage]);

        ProductionJobStage::create([
            'job_id'     => $job->id,
            'stage'      => $nextStage,
            'notes'      => $data['notes'] ?? null,
            'moved_by'   => $movedBy,
            'started_at' => now(),
        ]);

        $stageLabel = ProductionJob::allStages()[$nextStage];

        if ($nextStage !== 'completed') {
            app(ProductionNotifier::class)->notifyStageTeam($nextStage, $job->fresh());
        }

        return [
            'success'     => true,
            'pending'     => false,
            'next_stage'  => $nextStage,
            'stage_label' => $stageLabel,
            'stage_color' => ProductionJob::stageColor($nextStage),
            'redirect'    => route('production.show', $job),
        ];
    }

    // ── Task Start / End ──────────────────────────────────────────────────────

    public function taskStart(Request $request, ProductionJob $job)
    {
        $this->checkAccess();
        if (! $this->canAccessJob($job)) {
            return response()->json(['success' => false, 'message' => 'Not assigned to this stage.'], 403);
        }

        $data = $request->validate([
            'ratings'                  => ['nullable', 'array'],
            'ratings.*.rated_user_id'  => ['required_with:ratings', 'integer', 'exists:users,id'],
            'ratings.*.rating'         => ['required_with:ratings', 'integer', 'min:1', 'max:5'],
            'ratings.*.comment'        => ['nullable', 'string', 'max:500'],
        ]);

        $stage = ProductionJobStage::where('job_id', $job->id)
            ->where('stage', $job->current_stage)
            ->whereNull('completed_at')
            ->latest('started_at')
            ->first();

        if (! $stage) {
            return response()->json(['success' => false, 'message' => 'Stage record not found.'], 404);
        }
        if ($stage->task_started_at) {
            return response()->json(['success' => false, 'message' => 'Task already started.']);
        }

        $stage->update(['task_started_at' => now()]);

        // Per-employee ratings for the work received from the previous section.
        $prevStage  = ProductionJob::prevStage($job->current_stage);
        $ratedCount = 0;

        if ($prevStage && ! empty($data['ratings'])) {
            // Only accept ratings for employees actually assigned to the previous stage.
            $validUserIds = ProductionStageEmployee::where('stage', $prevStage)
                ->pluck('user_id')->all();

            foreach ($data['ratings'] as $entry) {
                if (! in_array((int) $entry['rated_user_id'], $validUserIds, true)) {
                    continue;
                }

                ProductionEmployeeRating::updateOrCreate(
                    [
                        'job_id'        => $job->id,
                        'rated_stage'   => $prevStage,
                        'rated_user_id' => (int) $entry['rated_user_id'],
                    ],
                    [
                        'rater_stage' => $job->current_stage,
                        'rated_by'    => auth()->id(),
                        'rating'      => (int) $entry['rating'],
                        'comment'     => $entry['comment'] ?? null,
                    ]
                );
                $ratedCount++;
            }
        }

        return response()->json([
            'success'    => true,
            'started_at' => now()->format('d M Y, h:i A'),
            'rated'      => $ratedCount > 0,
        ]);
    }

    public function taskEnd(Request $request, ProductionJob $job)
    {
        $this->checkAccess();
        if (! $this->canAccessJob($job)) {
            return response()->json(['success' => false, 'message' => 'Not assigned to this stage.'], 403);
        }

        $stage = ProductionJobStage::where('job_id', $job->id)
            ->where('stage', $job->current_stage)
            ->whereNull('completed_at')
            ->latest('started_at')
            ->first();

        if (! $stage) {
            return response()->json(['success' => false, 'message' => 'Stage record not found.'], 404);
        }
        if (! $stage->task_started_at) {
            return response()->json(['success' => false, 'message' => 'Start the task first.']);
        }
        if ($stage->task_ended_at) {
            return response()->json(['success' => false, 'message' => 'Task already ended.']);
        }

        $stage->update(['task_ended_at' => now()]);

        $mins = (int) $stage->task_started_at->diffInMinutes(now());
        $dur  = $mins >= 60
            ? floor($mins / 60) . 'h ' . ($mins % 60) . 'm'
            : $mins . 'm';

        return response()->json([
            'success'  => true,
            'ended_at' => now()->format('d M Y, h:i A'),
            'duration' => $dur,
        ]);
    }

    // ── Materials ─────────────────────────────────────────────────────────────

    public function searchJobMaterials(Request $request, ProductionJob $job)
    {
        $this->checkAccess();
        if (! $this->canRequestMaterials($job)) {
            return response()->json([], 403);
        }

        return $this->searchInventoryMaterialsJson($request);
    }

    public function searchInventoryMaterials(Request $request)
    {
        $this->checkAccess();
        if (! $this->hasFullProductionAccess()) {
            abort(403);
        }

        return $this->searchInventoryMaterialsJson($request);
    }

    private function searchInventoryMaterialsJson(Request $request)
    {
        $q = $request->get('q', '');

        $results = InventoryMaterial::with(['unit', 'category'])
            ->where('name', 'like', "%{$q}%")
            ->orderBy('name')
            ->limit(12)
            ->get()
            ->map(fn ($m) => [
                'id'       => $m->id,
                'name'     => $m->name,
                'category' => $m->category?->name,
                'unit'     => $m->unit?->abbreviation ?? '',
                'price'    => $m->price_per_unit,
                'stock'    => $m->current_stock,
            ]);

        return response()->json($results);
    }

    public function addMaterial(Request $request, ProductionJob $job)
    {
        $this->checkAccess();
        if (! $this->canRequestMaterials($job)) {
            return response()->json(['success' => false, 'message' => 'You cannot request materials for this job.'], 403);
        }

        $data = $request->validate([
            'material_id' => ['required', 'integer', 'exists:inventory_materials,id'],
            'quantity'    => ['required', 'numeric', 'min:0.001'],
            'notes'       => ['nullable', 'string', 'max:500'],
        ]);

        $material = InventoryMaterial::with('unit')->findOrFail($data['material_id']);
        $qty = (float) $data['quantity'];

        if (! $material->hasEnoughStock($qty)) {
            return response()->json([
                'success' => false,
                'message' => "Insufficient stock for {$material->name}. Available: {$material->current_stock} ".($material->unit?->abbreviation ?? ''),
            ]);
        }

        // Production Manager can issue immediately; Workshop team must request approval.
        if ($this->isProductionManager()) {
            try {
                $usage = $this->issueMaterialToJob($job, (int) $data['material_id'], $qty, 'production');
            } catch (\RuntimeException $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage()]);
            }

            return response()->json([
                'success' => true,
                'pending' => false,
                'usage'   => [
                    'id'         => $usage->id,
                    'name'       => $material->name,
                    'quantity'   => $usage->quantity,
                    'unit'       => $material->unit?->abbreviation ?? '',
                    'unit_price' => $usage->unit_price,
                    'subtotal'   => round($usage->quantity * $usage->unit_price, 2),
                    'stock_left' => $material->fresh()->current_stock,
                ],
            ]);
        }

        $existing = ProductionMaterialRequest::pending()
            ->where('job_id', $job->id)
            ->where('material_id', $data['material_id'])
            ->where('quantity', $qty)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'pending' => true,
                'message' => 'Same material request is already waiting for Production Manager approval.',
            ]);
        }

        ProductionMaterialRequest::create([
            'job_id' => $job->id,
            'material_id' => (int) $data['material_id'],
            'quantity' => $qty,
            'status' => 'pending',
            'requested_by' => auth()->id(),
            'notes' => $data['notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'pending' => true,
            'message' => 'Material request sent to Production Manager for approval.',
            'request' => [
                'name' => $material->name,
                'quantity' => $qty,
                'unit' => $material->unit?->abbreviation ?? '',
                'stock' => $material->current_stock,
            ],
        ]);
    }

    public function removeMaterial(ProductionJob $job, ProductionJobMaterial $usage)
    {
        $this->checkAccess();

        if ($usage->job_id !== $job->id) {
            abort(404);
        }

        if (! $this->canIssueMaterials($job)) {
            return response()->json(['success' => false, 'message' => 'You cannot remove materials for this job.'], 403);
        }

        // Restore stock
        $usage->material->increment('current_stock', $usage->quantity);
        $usage->delete();

        return response()->json(['success' => true]);
    }

    // ── Admin detail view ─────────────────────────────────────────────────────

    public function adminDetail(ProductionJob $job)
    {
        $this->checkAccess();
        if (! $this->hasFullProductionAccess()) {
            abort(403, 'Admin access required.');
        }

        $job->load([
            'stageHistory.movedBy',
            'materials.material.unit',
            'materials.addedBy',
            'creator',
            'inquiry.agent',
        ]);

        $stages       = ProductionJob::allStages();
        $totalRate    = $job->stageHistory->sum('stage_rate');
        $materialCost = $job->materials->sum(fn ($m) => $m->quantity * $m->unit_price);
        $grandTotal   = $totalRate + $materialCost;

        $costService = app(ProductionCostService::class);
        $jobRevenue  = $costService->jobRevenue($job);
        $jobProfit   = $grandTotal > 0 || $jobRevenue > 0 ? $jobRevenue - $grandTotal : 0;

        return view('production.detail', compact(
            'job', 'stages', 'totalRate', 'materialCost', 'grandTotal', 'jobRevenue', 'jobProfit'
        ));
    }

    // ── Upload additional files ───────────────────────────────────────────────

    public function uploadFiles(Request $request, ProductionJob $job)
    {
        $this->checkAccess();
        if (! $this->canAccessJob($job)) {
            return response()->json(['success' => false, 'message' => 'You are not assigned to this production section.'], 403);
        }

        $request->validate([
            'files.*'       => ['required', 'file', 'max:20480'],
            'file_labels.*' => ['nullable', 'string', 'max:80'],
        ]);

        $this->attachFiles($job, $request->file('files'), $request->input('file_labels', []));

        return response()->json(['success' => true]);
    }

    public function deleteFile(ProductionJobFile $file)
    {
        $this->checkAccess();
        $file->load('job');
        if (! $this->canAccessJob($file->job)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        if (\App\Support\UploadStorage::appExists($file->file_path)) {
            \App\Support\UploadStorage::deleteApp($file->file_path);
        }
        $file->delete();

        return response()->json(['success' => true]);
    }

    public function downloadFile(ProductionJobFile $file)
    {
        $this->checkAccess();
        if (! $this->canAccessJob($file->job)) {
            abort(403, 'You are not assigned to this production section.');
        }

        return \App\Support\UploadStorage::appDownload($file->file_path, $file->original_name);
    }

    // ── Update Google Drive URL ───────────────────────────────────────────────

    public function updateDrive(Request $request, ProductionJob $job)
    {
        $this->checkAccess();
        if (! $this->canAccessJob($job)) {
            return response()->json(['success' => false, 'message' => 'You are not assigned to this production section.'], 403);
        }

        $data = $request->validate([
            'google_drive_url' => ['nullable', 'url', 'max:500'],
        ]);

        $job->update(['google_drive_url' => $data['google_drive_url']]);

        return response()->json(['success' => true, 'url' => $job->google_drive_url]);
    }

    // ── Team management (admin) ─────────────────────────────────────────────

    public function team()
    {
        $this->checkAccess();
        $this->checkTeamAdmin();

        $stages         = ProductionJob::allStages();
        $workableStages = ProductionStageEmployee::workableStages();
        $stageEmployees = ProductionStageEmployee::groupedByStage();
        $users          = $this->businessUsers();
        $stageColors    = collect($workableStages)->mapWithKeys(
            fn ($s) => [$s => ProductionJob::stageColor($s)]
        );

        return view('production.team', compact('stages', 'workableStages', 'stageEmployees', 'users', 'stageColors'));
    }

    public function assignTeamMember(Request $request)
    {
        $this->checkAccess();
        $this->checkTeamAdmin();

        $data = $request->validate([
            'stage'            => ['required', 'in:'.implode(',', ProductionStageEmployee::workableStages())],
            'user_id'          => ['required', 'integer', 'exists:users,id'],
            'whatsapp_number'  => ['required', 'string', 'min:9', 'max:20'],
        ]);

        $businessId = request()->session()->get('user.business_id');
        $user = User::where('business_id', $businessId)->user()->findOrFail($data['user_id']);
        $whatsapp = ProductionNotifier::normalizePhone($data['whatsapp_number']);

        if (strlen($whatsapp) < 9) {
            return response()->json(['success' => false, 'message' => 'Enter a valid WhatsApp number.'], 422);
        }

        $assignment = ProductionStageEmployee::updateOrCreate(
            ['stage' => $data['stage'], 'user_id' => $user->id],
            ['whatsapp_number' => $whatsapp, 'assigned_by' => auth()->id()]
        );

        $assignment->load('user');

        $notifier    = app(ProductionNotifier::class);
        $credentials = $notifier->issueLoginCredentials($user, (int) $businessId);
        $waResult    = $notifier->notifyTeamMemberCredentials($assignment, $data['stage'], $credentials, $whatsapp);

        return response()->json([
            'success' => true,
            'member'  => [
                'id'               => $assignment->id,
                'user_id'          => $assignment->user_id,
                'name'             => trim($assignment->user->surname . ' ' . $assignment->user->first_name . ' ' . $assignment->user->last_name),
                'email'            => $assignment->user->email,
                'whatsapp_number'  => $assignment->whatsapp_number,
                'is_head'          => (bool) $assignment->is_head,
                'initials'         => $this->userInitials($assignment->user),
            ],
            'whatsapp_sent'    => ! empty($waResult['success']),
            'whatsapp_message' => $waResult['message'] ?? null,
        ]);
    }

    public function removeTeamMember(ProductionStageEmployee $assignment)
    {
        $this->checkAccess();
        $this->checkTeamAdmin();

        $assignment->delete();

        return response()->json(['success' => true]);
    }

    public function setTeamHead(Request $request)
    {
        $this->checkAccess();
        $this->checkTeamAdmin();

        $data = $request->validate([
            'assignment_id' => ['required', 'integer', 'exists:production_stage_employees,id'],
        ]);

        $assignment = ProductionStageEmployee::with('user')->findOrFail($data['assignment_id']);

        ProductionStageEmployee::where('stage', $assignment->stage)->update(['is_head' => false]);
        $assignment->update(['is_head' => true]);

        $user = $assignment->user;

        return response()->json([
            'success' => true,
            'head'    => [
                'id'              => $assignment->id,
                'name'            => trim(($user->surname ?? '') . ' ' . ($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: ($user->username ?? ''),
                'whatsapp_number' => $assignment->whatsapp_number,
            ],
        ]);
    }

    private function userInitials(User $user): string
    {
        $parts = array_filter([$user->first_name, $user->last_name]);
        if (empty($parts)) {
            return strtoupper(substr($user->username ?? 'U', 0, 2));
        }

        return strtoupper(collect($parts)->map(fn ($p) => substr($p, 0, 1))->join(''));
    }

    // ── Convert completed job to product ─────────────────────────────────────

    public function convertProductForm(ProductionJob $job)
    {
        $this->checkAccess();
        if (! $this->hasFullProductionAccess()) {
            abort(403, 'Only admin can convert jobs to products.');
        }
        if ($job->current_stage !== 'completed') {
            return response()->json(['success' => false, 'message' => 'Only completed jobs can be converted to products.'], 422);
        }

        $businessId = request()->session()->get('user.business_id');
        $job->load(['materials.material', 'stageHistory']);

        if ($job->isConverted()) {
            return response()->json([
                'success'      => true,
                'converted'    => true,
                'product_name' => $job->product?->name,
                'product_url'  => $job->product_id ? $this->productPageUrl($job->product_id) : null,
                'converted_at' => $job->converted_at?->format('d M Y, h:i A'),
                'converted_qty'=> $job->converted_qty,
            ]);
        }

        $locations = BusinessLocation::forDropdown($businessId, false, false, true, false);
        $units     = Unit::forDropdown($businessId, false);

        $materialCost = round($job->total_material_cost, 2);
        $fullCost     = round($job->grand_total, 2);

        return response()->json([
            'success'          => true,
            'converted'        => false,
            'job'              => [
                'id'          => $job->id,
                'job_number'  => $job->job_number,
                'title'       => $job->title,
                'customer'    => $job->customer_name,
                'description' => $job->description,
            ],
            'default_name'              => $job->title,
            'suggested_purchase_price'  => $materialCost,
            'suggested_selling_price'   => $fullCost > 0 ? $fullCost : $materialCost,
            'material_count'            => $job->materials->count(),
            'default_qty'               => 1,
            'locations'        => collect($locations)->map(fn ($name, $id) => ['id' => $id, 'name' => $name])->values(),
            'units'            => collect($units)->map(fn ($name, $id) => ['id' => $id, 'name' => $name])->values(),
        ]);
    }

    public function searchProductsForConvert(Request $request)
    {
        $this->checkAccess();
        if (! $this->hasFullProductionAccess()) {
            abort(403);
        }

        $term       = $request->get('q', '');
        $locationId = $request->get('location_id');
        $businessId = $request->session()->get('user.business_id');

        $query = Variation::query()
            ->join('products', 'products.id', '=', 'variations.product_id')
            ->leftJoin('variation_location_details as vld', function ($join) use ($locationId) {
                $join->on('vld.variation_id', '=', 'variations.id');
                if ($locationId) {
                    $join->where('vld.location_id', '=', $locationId);
                }
            })
            ->where('products.business_id', $businessId)
            ->where('products.type', 'single')
            ->where('products.is_inactive', 0);

        if ($term) {
            $query->where(function ($q) use ($term) {
                $q->where('products.name', 'like', "%{$term}%")
                  ->orWhere('products.sku', 'like', "%{$term}%")
                  ->orWhere('variations.sub_sku', 'like', "%{$term}%");
            });
        }

        $products = $query
            ->select([
                'products.id as product_id',
                'products.name',
                'products.sku',
                'variations.id as variation_id',
                'variations.default_sell_price',
                'variations.sell_price_inc_tax',
                DB::raw('COALESCE(vld.qty_available, 0) as qty_available'),
            ])
            ->orderBy('products.name')
            ->limit(20)
            ->get();

        return response()->json(['success' => true, 'products' => $products]);
    }

    public function convertToProduct(Request $request, ProductionJob $job)
    {
        $this->checkAccess();
        if (! $this->hasFullProductionAccess()) {
            abort(403, 'Only admin can convert jobs to products.');
        }
        if ($job->current_stage !== 'completed') {
            return response()->json(['success' => false, 'message' => 'Only completed jobs can be converted.'], 422);
        }
        if ($job->isConverted()) {
            return response()->json(['success' => false, 'message' => 'This job was already converted to a product.'], 422);
        }

        $data = $request->validate([
            'mode'           => ['required', 'in:existing,new'],
            'location_id'    => ['required', 'integer'],
            'quantity'       => ['required', 'numeric', 'min:0.01'],
            'product_id'     => ['required_if:mode,existing', 'nullable', 'integer'],
            'variation_id'   => ['required_if:mode,existing', 'nullable', 'integer'],
            'name'           => ['required_if:mode,new', 'nullable', 'string', 'max:191'],
            'unit_id'        => ['required_if:mode,new', 'nullable', 'integer'],
            'purchase_price' => ['required_if:mode,new', 'nullable', 'numeric', 'min:0'],
            'selling_price'  => ['required_if:mode,new', 'nullable', 'numeric', 'min:0'],
            'profit_percent' => ['nullable', 'numeric', 'min:0'],
        ]);

        $businessId   = $request->session()->get('user.business_id');
        $productUtil  = app(ProductUtil::class);
        $qty          = (float) $data['quantity'];
        $locationId   = (int) $data['location_id'];

        try {
            DB::beginTransaction();

            if ($data['mode'] === 'new') {
                $purchasePrice = (float) $data['purchase_price'];
                $sellingPrice  = (float) $data['selling_price'];
                $profitPercent = isset($data['profit_percent'])
                    ? (float) $data['profit_percent']
                    : ($purchasePrice > 0 ? round((($sellingPrice - $purchasePrice) / $purchasePrice) * 100, 2) : 0);

                $product = Product::create([
                    'name'         => $data['name'],
                    'business_id'  => $businessId,
                    'created_by'   => auth()->id(),
                    'unit_id'      => (int) $data['unit_id'],
                    'type'         => 'single',
                    'enable_stock' => 1,
                    'sku'          => ' ',
                    'barcode_type' => 'C128',
                    'tax_type'     => 'exclusive',
                    'product_description' => $job->description,
                ]);

                $product->sku = $productUtil->generateProductSku($product->id);
                $product->save();

                $productUtil->createSingleProductVariation(
                    $product->id,
                    $product->sku,
                    $purchasePrice,
                    $purchasePrice,
                    $profitPercent,
                    $sellingPrice,
                    $sellingPrice
                );

                $variation = Variation::where('product_id', $product->id)->firstOrFail();
                $productId = $product->id;
                $variationId = $variation->id;
            } else {
                $product = Product::where('business_id', $businessId)->findOrFail((int) $data['product_id']);
                $variation = Variation::where('product_id', $product->id)
                    ->where('id', (int) $data['variation_id'])
                    ->firstOrFail();
                $productId = $product->id;
                $variationId = $variation->id;

                if ($product->enable_stock != 1) {
                    $product->update(['enable_stock' => 1]);
                }
            }

            $existingLocations = $product->product_locations()->pluck('product_locations.location_id')->toArray();
            if (! in_array($locationId, $existingLocations, true)) {
                $existingLocations[] = $locationId;
                $product->product_locations()->sync($existingLocations);
            }

            $unitCost = $data['mode'] === 'new'
                ? (float) $data['purchase_price']
                : (float) ($variation->default_purchase_price ?? 0);

            $this->recordProductionStockTransaction(
                $job,
                $businessId,
                $locationId,
                $productId,
                $variationId,
                $qty,
                $unitCost,
                $productUtil,
                true
            );

            $job->update([
                'product_id'    => $productId,
                'variation_id'  => $variationId,
                'converted_qty' => $qty,
                'converted_at'  => now(),
                'converted_by'  => auth()->id(),
            ]);

            DB::commit();

            return response()->json([
                'success'      => true,
                'message'      => $data['mode'] === 'new'
                    ? 'New product created and stock added successfully.'
                    : 'Stock added to existing product successfully.',
                'product_name' => $product->fresh()->name,
                'product_url'  => $this->productPageUrl($productId),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Production convert to product failed: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Conversion failed. Please try again.'], 500);
        }
    }

    // ── Helper ───────────────────────────────────────────────────────────────

    private function productPageUrl(int $productId): string
    {
        return route('products.index', ['view_product' => $productId]);
    }

    private function recordProductionStockTransaction(
        ProductionJob $job,
        int $businessId,
        int $locationId,
        int $productId,
        int $variationId,
        float $qty,
        float $unitCost,
        ProductUtil $productUtil,
        bool $updateStock = true
    ): Transaction {
        $variation = Variation::findOrFail($variationId);
        $purchasePrice = $unitCost > 0 ? $unitCost : (float) ($variation->default_purchase_price ?? 0);
        $purchasePriceIncTax = $purchasePrice > 0 ? $purchasePrice : (float) ($variation->dpp_inc_tax ?? 0);
        if ($purchasePriceIncTax <= 0) {
            $purchasePriceIncTax = $purchasePrice;
        }

        $total = $purchasePriceIncTax * $qty;
        $notes = $job->title;
        if ($job->customer_name) {
            $notes .= ' | Customer: ' . $job->customer_name;
        }

        $transaction = Transaction::create([
            'type'               => 'production_purchase',
            'status'             => 'received',
            'business_id'        => $businessId,
            'transaction_date'   => now(),
            'location_id'        => $locationId,
            'ref_no'             => $job->job_number,
            'additional_notes'   => $notes,
            'total_before_tax'   => $total,
            'final_total'        => $total,
            'payment_status'     => 'paid',
            'created_by'         => auth()->id(),
            'production_job_id'  => $job->id,
        ]);

        $purchaseLine = new PurchaseLine();
        $purchaseLine->product_id = $productId;
        $purchaseLine->variation_id = $variationId;
        $purchaseLine->quantity = $qty;
        $purchaseLine->pp_without_discount = $purchasePrice;
        $purchaseLine->purchase_price = $purchasePrice;
        $purchaseLine->purchase_price_inc_tax = $purchasePriceIncTax;
        $purchaseLine->item_tax = max(0, $purchasePriceIncTax - $purchasePrice);
        $transaction->purchase_lines()->save($purchaseLine);

        if ($updateStock) {
            $productUtil->updateProductQuantity($locationId, $productId, $variationId, $qty, 0, null, false);
        }

        return $transaction;
    }

    private function attachFiles(ProductionJob $job, array $files, array $labels): void
    {
        $dir = 'production/' . $job->id;

        foreach ($files as $idx => $file) {
            if (! $file || ! $file->isValid()) continue;

            $ext   = strtolower($file->getClientOriginalExtension());
            $fname = 'file_' . uniqid() . '.' . $ext;
            \App\Support\UploadStorage::putAppFileAs($dir, $file, $fname);

            ProductionJobFile::create([
                'job_id'        => $job->id,
                'original_name' => $file->getClientOriginalName(),
                'file_path'     => $dir . '/' . $fname,
                'mime_type'     => $file->getClientMimeType(),
                'file_size'     => $file->getSize(),
                'label'         => $labels[$idx] ?? null,
                'uploaded_by'   => auth()->id(),
            ]);
        }
    }

    // ── Static helper: auto-create job from inquiry ───────────────────────────

    public static function createFromInquiry(WhatsappChatAssignment $inquiry, ?string $googleDriveUrl = null): ProductionJob
    {
        $job = ProductionJob::create([
            'job_number'       => ProductionJob::generateJobNumber(),
            'inquiry_id'       => $inquiry->id,
            'customer_name'    => $inquiry->customer_name ?: $inquiry->phone_number,
            'customer_phone'   => $inquiry->phone_number,
            'title'            => ($inquiry->inquiry_category ?? 'Print Job') . ' — ' . ($inquiry->customer_name ?: $inquiry->phone_number),
            'description'      => $inquiry->inquiry_notes,
            'google_drive_url' => $googleDriveUrl,
            'current_stage'    => 'design',
            'priority'         => 'normal',
            'created_by'       => auth()->id(),
        ]);

        ProductionJobStage::create([
            'job_id'     => $job->id,
            'stage'      => 'design',
            'notes'      => 'Auto-created from WhatsApp Inquiry #' . $inquiry->id . ' — status set to Sent to Production.',
            'moved_by'   => auth()->id(),
            'started_at' => now(),
        ]);

        app(ProductionNotifier::class)->notifyStageTeam('design', $job);

        return $job;
    }
}

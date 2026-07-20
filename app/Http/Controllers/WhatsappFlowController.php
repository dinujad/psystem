<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\WhatsappAccess;
use App\WhatsappFlow;
use App\WhatsappFlowStep;
use Illuminate\Http\Request;

class WhatsappFlowController extends Controller
{
    use WhatsappAccess;

    protected function authorizeAccess(): void
    {
        $this->requireWhatsappAdmin();
    }

    // ── Flows CRUD ──────────────────────────────────────────────────────
    public function index()
    {
        $this->authorizeAccess();

        $flows = WhatsappFlow::withCount('steps')->orderByDesc('id')->get();

        return view('whatsapp.bot.flows', compact('flows'));
    }

    public function store(Request $request)
    {
        $this->authorizeAccess();

        $data = $this->validateFlow($request);
        WhatsappFlow::create($data);

        return back()->with('status', 'Flow created.');
    }

    public function update(Request $request, WhatsappFlow $flow)
    {
        $this->authorizeAccess();

        $data = $this->validateFlow($request);
        $flow->update($data);

        return back()->with('status', 'Flow updated.');
    }

    public function destroy(WhatsappFlow $flow)
    {
        $this->authorizeAccess();

        $flow->delete();

        return redirect()
            ->route('admin.whatsapp.flows.index')
            ->with('status', 'Flow deleted.');
    }

    public function toggle(WhatsappFlow $flow)
    {
        $this->authorizeAccess();

        $flow->is_active = ! $flow->is_active;
        $flow->save();

        return response()->json(['success' => true, 'is_active' => $flow->is_active]);
    }

    protected function validateFlow(Request $request): array
    {
        $validated = $request->validate([
            'name'                => ['required', 'string', 'max:191'],
            'trigger_keywords'    => ['nullable', 'string'],
            'is_default_fallback' => ['nullable', 'boolean'],
            'is_active'           => ['nullable', 'boolean'],
        ]);

        // Comma / newline separated keywords → clean lowercase array.
        $keywords = collect(preg_split('/[\n,]+/', (string) ($validated['trigger_keywords'] ?? '')))
            ->map(fn ($k) => mb_strtolower(trim($k)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return [
            'name'                => $validated['name'],
            'trigger_keywords'    => $keywords,
            'is_default_fallback' => $request->boolean('is_default_fallback'),
            'is_active'           => $request->boolean('is_active'),
        ];
    }

    // ── Flow builder (steps) ────────────────────────────────────────────
    public function builder(WhatsappFlow $flow)
    {
        $this->authorizeAccess();

        $flow->load('steps');

        return view('whatsapp.bot.builder', compact('flow'));
    }

    public function storeStep(Request $request, WhatsappFlow $flow)
    {
        $this->authorizeAccess();

        $data = $this->validateStep($request, $flow);
        $flow->steps()->create($data);

        return back()->with('status', 'Step added.');
    }

    public function updateStep(Request $request, WhatsappFlowStep $step)
    {
        $this->authorizeAccess();

        $data = $this->validateStep($request, $step->flow, $step->id);
        $step->update($data);

        return back()->with('status', 'Step updated.');
    }

    public function destroyStep(WhatsappFlowStep $step)
    {
        $this->authorizeAccess();

        $flowId = $step->flow_id;
        $step->delete();

        return redirect()
            ->route('admin.whatsapp.flows.builder', $flowId)
            ->with('status', 'Step deleted.');
    }

    protected function validateStep(Request $request, WhatsappFlow $flow, ?int $ignoreId = null): array
    {
        $validated = $request->validate([
            'step_key'                => ['required', 'string', 'max:191', 'regex:/^[a-zA-Z0-9_]+$/'],
            'message_text'            => ['required', 'string'],
            'step_type'               => ['required', 'in:menu,text_input,final'],
            'next_step_key'           => ['nullable', 'string', 'max:191'],
            'save_input_as'           => ['nullable', 'string', 'max:191', 'regex:/^[a-zA-Z0-9_]+$/'],
            'is_first_step'           => ['nullable', 'boolean'],
            'triggers_human_takeover' => ['nullable', 'boolean'],
            'sort_order'              => ['nullable', 'integer'],
            // Menu options arrive as parallel arrays from the repeater UI.
            'option_label'            => ['nullable', 'array'],
            'option_match'            => ['nullable', 'array'],
            'option_next'             => ['nullable', 'array'],
        ]);

        // step_key must be unique within the flow.
        $exists = WhatsappFlowStep::where('flow_id', $flow->id)
            ->where('step_key', $validated['step_key'])
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        if ($exists) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'step_key' => 'step_key must be unique within this flow.',
            ]);
        }

        // Build the menu options JSON from the repeater fields.
        $options = null;
        if ($validated['step_type'] === 'menu') {
            $labels  = $request->input('option_label', []);
            $matches = $request->input('option_match', []);
            $nexts   = $request->input('option_next', []);
            $options = [];

            foreach ($labels as $i => $label) {
                $label = trim((string) $label);
                $match = trim((string) ($matches[$i] ?? ''));
                if ($label === '' && $match === '') {
                    continue;
                }
                $options[] = [
                    'label'         => $label,
                    'match'         => $match,
                    'next_step_key' => trim((string) ($nexts[$i] ?? '')) ?: null,
                ];
            }
        }

        // Only one first step per flow — clear the flag on others if set here.
        $isFirst = $request->boolean('is_first_step');
        if ($isFirst) {
            WhatsappFlowStep::where('flow_id', $flow->id)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->update(['is_first_step' => false]);
        }

        return [
            'step_key'                => $validated['step_key'],
            'message_text'            => $validated['message_text'],
            'step_type'               => $validated['step_type'],
            'options'                 => $options,
            'next_step_key'           => $validated['next_step_key'] ?: null,
            'save_input_as'           => $validated['step_type'] === 'text_input' ? ($validated['save_input_as'] ?: null) : null,
            'is_first_step'           => $isFirst,
            'triggers_human_takeover' => $request->boolean('triggers_human_takeover'),
            'sort_order'              => (int) ($validated['sort_order'] ?? 0),
        ];
    }
}

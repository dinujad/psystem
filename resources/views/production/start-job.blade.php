@extends('layouts.app')
@section('title', 'Start Production Job')

@section('css')
<style>
.sj-page  { padding: 0 20px 60px; max-width: 960px; margin: 0 auto; }
.sj-back  { display: inline-flex; align-items: center; gap: 6px; color: #7c5cfc; font-size: 13px; font-weight: 600; text-decoration: none; margin: 18px 0 14px; }
.sj-back:hover { color: #5b3fd9; text-decoration: none; }
.sj-card  { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; overflow: hidden; margin-bottom: 18px; }
.sj-head  { background: linear-gradient(135deg, #1e1b4b, #4f46e5); padding: 22px 26px; color: #fff; }
.sj-head h2 { font-size: 20px; font-weight: 800; margin: 0 0 4px; }
.sj-head p  { font-size: 13px; opacity: .8; margin: 0; }
.sj-body  { padding: 22px 26px; }
.sj-section-title { font-size: 14px; font-weight: 800; color: #111827; margin: 0 0 14px; display: flex; align-items: center; gap: 8px; }
.sj-section-title span { background: #ede9fe; color: #5b21b6; font-size: 11px; padding: 2px 8px; border-radius: 20px; }
.sj-group { margin-bottom: 16px; }
.sj-label { font-size: 11px; font-weight: 700; color: #374151; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 5px; display: block; }
.sj-input { width: 100%; border: 1px solid #d1d5db; border-radius: 9px; padding: 9px 12px; font-size: 13px; color: #374151; background: #f9fafb; outline: none; box-sizing: border-box; }
.sj-input:focus { border-color: #7c5cfc; background: #fff; }
.sj-row   { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
@media (max-width: 640px) { .sj-row { grid-template-columns: 1fr; } }

.sj-stage-grid { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 14px; }
@media (max-width: 700px) { .sj-stage-grid { grid-template-columns: 1fr; } }
.sj-stage-box { border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; }
.sj-stage-head { padding: 10px 14px; font-size: 13px; font-weight: 800; color: #fff; }
.sj-stage-body { padding: 12px 14px; background: #fafafa; }
.sj-team-list { display: flex; flex-direction: column; gap: 6px; margin-bottom: 10px; }
.sj-member-pick { display: flex; align-items: center; gap: 8px; font-size: 12px; color: #374151; padding: 6px 8px; border-radius: 8px; cursor: pointer; border: 1px solid transparent; transition: background .15s, border-color .15s; }
.sj-member-pick:hover { background: #fff; border-color: #e5e7eb; }
.sj-member-pick input { width: 16px; height: 16px; accent-color: #7c5cfc; cursor: pointer; flex-shrink: 0; }
.sj-member-pick.is-checked { background: #fff; border-color: #c4b5fd; }
.sj-stage-actions { display: flex; gap: 6px; margin-bottom: 8px; flex-wrap: wrap; }
.sj-stage-actions button { background: #fff; border: 1px solid #e5e7eb; border-radius: 6px; padding: 3px 8px; font-size: 10px; font-weight: 700; color: #6b7280; cursor: pointer; }
.sj-stage-actions button:hover { background: #f3f4f6; color: #374151; }
.sj-team-member { display: flex; align-items: center; gap: 8px; font-size: 12px; color: #374151; }
.sj-team-av { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 800; color: #fff; flex-shrink: 0; }
.sj-head-tag { font-size: 9px; font-weight: 800; background: #fef3c7; color: #92400e; padding: 1px 6px; border-radius: 10px; margin-left: 4px; }
.sj-empty-team { font-size: 11px; color: #9ca3af; font-style: italic; }

.sj-task-list { display: flex; flex-direction: column; gap: 8px; margin-top: 10px; }
.sj-task-row { display: grid; grid-template-columns: 120px 1fr 90px 32px; gap: 8px; align-items: center; }
@media (max-width: 640px) { .sj-task-row { grid-template-columns: 1fr; } }
.sj-task-row select, .sj-task-row input { font-size: 12px; padding: 7px 9px; }
.sj-btn-sm { background: #f3f4f6; border: none; border-radius: 8px; padding: 7px 12px; font-size: 12px; font-weight: 700; cursor: pointer; color: #374151; }
.sj-btn-sm:hover { background: #e5e7eb; }
.sj-btn-sm.danger { background: #fee2e2; color: #ef4444; }
.sj-btn-sm.danger:hover { background: #fecaca; }
.sj-btn-add { background: #ede9fe; color: #5b21b6; border: none; border-radius: 8px; padding: 8px 14px; font-size: 12px; font-weight: 700; cursor: pointer; margin-top: 8px; }
.sj-btn-add:hover { background: #ddd6fe; }

.sj-mat-list { display: flex; flex-direction: column; gap: 8px; margin-top: 10px; }
.sj-mat-row { display: grid; grid-template-columns: 1fr 100px 32px; gap: 8px; align-items: center; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 12px; }
.sj-mat-search-wrap { position: relative; }
.sj-mat-results { position: absolute; left: 0; right: 0; top: 100%; background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; box-shadow: 0 8px 24px rgba(0,0,0,.12); z-index: 50; max-height: 200px; overflow-y: auto; display: none; }
.sj-mat-results.show { display: block; }
.sj-mat-opt { padding: 9px 12px; font-size: 12px; cursor: pointer; border-bottom: 1px solid #f3f4f6; }
.sj-mat-opt:hover { background: #f5f3ff; }
.sj-mat-opt small { color: #9ca3af; }

.sj-wa-search { position: relative; }
.sj-wa-results { position: absolute; left: 0; right: 0; top: 100%; background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; box-shadow: 0 8px 24px rgba(0,0,0,.12); z-index: 50; max-height: 220px; overflow-y: auto; display: none; }
.sj-wa-results.show { display: block; }
.sj-wa-opt { padding: 10px 14px; cursor: pointer; border-bottom: 1px solid #f3f4f6; }
.sj-wa-opt:hover { background: #f0fdf4; }
.sj-wa-opt-name { font-size: 13px; font-weight: 700; color: #111; }
.sj-wa-opt-phone { font-size: 11px; color: #6b7280; }
.sj-wa-linked { background: #dcfce7; border: 1px solid #86efac; border-radius: 10px; padding: 10px 14px; font-size: 13px; color: #166534; margin-top: 8px; display: none; }

.sj-footer { padding: 18px 26px; border-top: 1px solid #f3f4f6; display: flex; gap: 10px; justify-content: flex-end; background: #fafafa; }
.sj-submit { background: #16a34a; color: #fff; border: none; border-radius: 10px; padding: 12px 28px; font-size: 14px; font-weight: 800; cursor: pointer; }
.sj-submit:hover { background: #15803d; }
.sj-cancel { background: #f3f4f6; color: #374151; border: none; border-radius: 10px; padding: 10px 20px; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; }
.sj-check-row { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #374151; margin-top: 8px; }
.sj-date-wrap { position: relative; }
.sj-date-wrap .sj-input { padding-right: 38px; cursor: pointer; background: #fff !important; }
.sj-date-icon { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); pointer-events: none; color: #7c5cfc; font-size: 16px; line-height: 1; }
.sj-field-hint { font-size: 11px; color: #9ca3af; margin-top: 4px; }
.sj-field-err { font-size: 11px; color: #ef4444; margin-top: 4px; display: none; }
</style>
@endsection

@section('content')
@php
    $stageColors = ['design'=>'#7c5cfc','production'=>'#3b82f6','quality'=>'#f59e0b','dispatch'=>'#10b981'];
    $defaultTitle = $inquiry ? (($inquiry->inquiry_category ?? 'Print Job') . ' — ' . ($inquiry->customer_name ?? $inquiry->phone_number)) : '';
@endphp
<div class="sj-page">
    <a href="{{ route('production.index') }}" class="sj-back">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
        Back to Production Board
    </a>

    <form method="POST" action="{{ route('production.start-job.store') }}" enctype="multipart/form-data" id="startJobForm">
        @csrf
        <input type="hidden" name="inquiry_id" id="inquiryId" value="{{ old('inquiry_id', $inquiry->id ?? '') }}">
        <input type="hidden" name="whatsapp_phone" id="whatsappPhone" value="{{ old('whatsapp_phone', $inquiry->phone_number ?? '') }}">

        {{-- Hero --}}
        <div class="sj-card">
            <div class="sj-head">
                <h2>Start Production Job</h2>
                <p>Set up the job, assign sections, issue raw materials, and notify the client on WhatsApp.</p>
            </div>
            @if($errors->any())
            <div style="padding:14px 26px;background:#fee2e2;color:#dc2626;font-size:13px;">
                @foreach($errors->all() as $e)<div>• {{ $e }}</div>@endforeach
            </div>
            @endif
        </div>

        {{-- Job details --}}
        <div class="sj-card">
            <div class="sj-body">
                <div class="sj-section-title">Job Details</div>
                <div class="sj-row">
                    <div class="sj-group">
                        <label class="sj-label">Job Name <span style="color:#ef4444">*</span></label>
                        <input type="text" name="title" class="sj-input" value="{{ old('title', $defaultTitle) }}" required placeholder="e.g. Business Cards — Perera &amp; Sons">
                    </div>
                    <div class="sj-group">
                        <label class="sj-label">Customer Name <span style="color:#ef4444">*</span></label>
                        <input type="text" name="customer_name" id="customerName" class="sj-input" value="{{ old('customer_name', $inquiry->customer_name ?? '') }}" required>
                    </div>
                </div>
                <div class="sj-row">
                    <div class="sj-group">
                        <label class="sj-label">Customer Phone (WhatsApp)</label>
                        <input type="text" name="customer_phone" id="customerPhone" class="sj-input" value="{{ old('customer_phone', $inquiry->phone_number ?? '') }}" placeholder="0771234567">
                    </div>
                    <div class="sj-group">
                        <label class="sj-label">Priority</label>
                        <select name="priority" class="sj-input">
                            @foreach(['low'=>'Low','normal'=>'Normal','high'=>'High','urgent'=>'Urgent'] as $v=>$l)
                            <option value="{{ $v }}" @selected(old('priority','normal')===$v)>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="sj-group">
                    <label class="sj-label">Description / Notes</label>
                    <textarea name="description" class="sj-input" rows="3" placeholder="Specs, colors, quantities…">{{ old('description', $inquiry->inquiry_notes ?? '') }}</textarea>
                </div>
                <div class="sj-row">
                    <div class="sj-group">
                        <label class="sj-label">Due Date</label>
                        <div class="sj-date-wrap">
                            <input type="text" id="sj-due-display" class="sj-input" readonly placeholder="Click to select date" autocomplete="off">
                            <input type="hidden" name="due_date" id="sj-due-date" value="{{ old('due_date') }}">
                            <span class="sj-date-icon" aria-hidden="true">📅</span>
                        </div>
                        <div class="sj-field-hint">Today or a future date only — pick from calendar</div>
                        <div class="sj-field-err" id="sj-due-err">Due date cannot be in the past.</div>
                    </div>
                    <div class="sj-group">
                        <label class="sj-label">Google Drive URL</label>
                        <input type="url" name="google_drive_url" class="sj-input" value="{{ old('google_drive_url') }}" placeholder="https://drive.google.com/…">
                    </div>
                </div>
                <div class="sj-group">
                    <label class="sj-label">Upload Files (optional)</label>
                    <input type="file" name="files[]" multiple class="sj-input" style="padding:6px;">
                </div>
            </div>
        </div>

        {{-- WhatsApp chat link --}}
        <div class="sj-card">
            <div class="sj-body">
                <div class="sj-section-title">WhatsApp Chat <span>optional</span></div>
                <div class="sj-wa-search">
                    <input type="text" class="sj-input" id="waSearch" placeholder="Search WhatsApp chat by name or phone…" autocomplete="off">
                    <div class="sj-wa-results" id="waResults"></div>
                </div>
                <div class="sj-wa-linked" id="waLinked">
                    Linked chat: <strong id="waLinkedName"></strong> (<span id="waLinkedPhone"></span>)
                    <button type="button" class="sj-btn-sm" style="margin-left:8px;" onclick="clearWaLink()">Clear</button>
                </div>
                <div class="sj-check-row">
                    <input type="checkbox" name="notify_customer" value="1" id="notifyCustomer" checked>
                    <label for="notifyCustomer">Send WhatsApp message to client when job starts</label>
                </div>
            </div>
        </div>

        {{-- Section teams + estimates --}}
        <div class="sj-card">
            <div class="sj-body">
                <div class="sj-section-title">Sections &amp; Team</div>
                <p style="font-size:12px;color:#6b7280;margin:-6px 0 14px;">Select team members for <strong>this job</strong> in each section. Optional estimated time per section.</p>
                <div class="sj-stage-grid">
                    @foreach($workableStages as $sk)
                    @php
                        $members = $stageEmployees[$sk] ?? collect();
                        $head = $stageHeads[$sk] ?? null;
                        $color = $stageColors[$sk] ?? '#6b7280';
                        $oldAssigned = old("assignments.{$sk}", []);
                        if ($oldAssigned === null) {
                            $oldAssigned = [];
                        }
                        $defaultAssigned = $head ? [(string) $head->user_id] : [];
                        $hasOldAssignments = old('assignments') !== null;
                        $checkedIds = $hasOldAssignments
                            ? array_map('strval', (array) old("assignments.{$sk}", []))
                            : $defaultAssigned;
                    @endphp
                    <div class="sj-stage-box" data-stage="{{ $sk }}">
                        <div class="sj-stage-head" style="background:{{ $color }};">{{ $stages[$sk] ?? ucfirst($sk) }}</div>
                        <div class="sj-stage-body">
                            @if($head)
                            @php $headName = trim(($head->user->surname??'').' '.($head->user->first_name??'').' '.($head->user->last_name??'')) ?: $head->user->username; @endphp
                            <div style="font-size:11px;color:#92400e;font-weight:700;margin-bottom:8px;">👑 Head: {{ $headName }}</div>
                            @endif
                            @if($members->count())
                            <div class="sj-stage-actions">
                                <button type="button" onclick="selectAllMembers('{{ $sk }}', true)">Select all</button>
                                <button type="button" onclick="selectAllMembers('{{ $sk }}', false)">Clear</button>
                                @if($head)
                                <button type="button" onclick="selectHeadOnly('{{ $sk }}', {{ $head->user_id }})">Head only</button>
                                @endif
                            </div>
                            @endif
                            <div class="sj-team-list">
                                @forelse($members as $m)
                                @php
                                    $u = $m->user;
                                    $nm = trim(($u->surname??'').' '.($u->first_name??'').' '.($u->last_name??'')) ?: ($u->username??'?');
                                    $isChecked = in_array((string) $m->user_id, $checkedIds, true);
                                @endphp
                                <label class="sj-member-pick{{ $isChecked ? ' is-checked' : '' }}">
                                    <input type="checkbox" name="assignments[{{ $sk }}][]" value="{{ $m->user_id }}"
                                        data-stage="{{ $sk }}"
                                        @checked($isChecked)
                                        onchange="this.closest('.sj-member-pick').classList.toggle('is-checked', this.checked)">
                                    <div class="sj-team-av" style="background:{{ $color }};">{{ strtoupper(substr($nm,0,2)) }}</div>
                                    <span>{{ $nm }}@if($m->is_head)<span class="sj-head-tag">Head</span>@endif</span>
                                </label>
                                @empty
                                <div class="sj-empty-team">No team assigned — <a href="{{ route('production.team') }}">Assign team</a></div>
                                @endforelse
                            </div>
                            <label class="sj-label">Est. Time (minutes) — optional</label>
                            <input type="number" name="sections[{{ $sk }}][estimated_minutes]" class="sj-input" min="1" max="10080" placeholder="e.g. 120" value="{{ old("sections.{$sk}.estimated_minutes") }}">
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Section tasks --}}
        <div class="sj-card">
            <div class="sj-body">
                <div class="sj-section-title">Section Tasks <span>optional</span></div>
                <p style="font-size:12px;color:#6b7280;margin:-6px 0 10px;">Add work items for each section. Time per task is optional.</p>
                <div class="sj-task-list" id="taskList"></div>
                <button type="button" class="sj-btn-add" onclick="addTaskRow()">+ Add Task</button>
            </div>
        </div>

        {{-- Issue raw materials --}}
        <div class="sj-card">
            <div class="sj-body">
                <div class="sj-section-title">Issue Raw Materials <span>from inventory</span></div>
                <p style="font-size:12px;color:#6b7280;margin:-6px 0 10px;">Issue materials to the production team when starting the job. Stock will be deducted from inventory.</p>
                <div class="sj-mat-search-wrap">
                    <input type="text" class="sj-input" id="matSearchInput" placeholder="Search material to add…" autocomplete="off">
                    <div class="sj-mat-results" id="matResults"></div>
                </div>
                <div class="sj-mat-list" id="matList"></div>
            </div>
            <div class="sj-footer">
                <a href="{{ route('production.index') }}" class="sj-cancel">Cancel</a>
                <button type="submit" class="sj-submit">▶ Start Job</button>
            </div>
        </div>
    </form>
</div>
@endsection

@section('javascript')
<script>
const WA_SEARCH_URL = @json(route('production.start-job.whatsapp-search'));
const MAT_SEARCH_URL = @json(route('production.materials.search-global'));
const STAGES = @json(collect($workableStages)->mapWithKeys(fn($s) => [$s => $stages[$s] ?? ucfirst($s)])->all());
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

let matIdx = 0;
let taskIdx = 0;
const addedMaterials = new Map();

function esc(s){ const d=document.createElement('div'); d.textContent=s??''; return d.innerHTML; }

function selectAllMembers(stage, checked){
    document.querySelectorAll(`input[data-stage="${stage}"]`).forEach(cb => {
        cb.checked = checked;
        cb.closest('.sj-member-pick')?.classList.toggle('is-checked', checked);
    });
}

function selectHeadOnly(stage, headUserId){
    document.querySelectorAll(`input[data-stage="${stage}"]`).forEach(cb => {
        const on = parseInt(cb.value, 10) === headUserId;
        cb.checked = on;
        cb.closest('.sj-member-pick')?.classList.toggle('is-checked', on);
    });
}

// WhatsApp search
let waTimer;
document.getElementById('waSearch').addEventListener('input', function(){
    clearTimeout(waTimer);
    const q = this.value.trim();
    if(q.length < 2){ document.getElementById('waResults').classList.remove('show'); return; }
    waTimer = setTimeout(async ()=>{
        try {
            const r = await fetch(WA_SEARCH_URL + '?q=' + encodeURIComponent(q), {headers:{Accept:'application/json'}});
            const list = await r.json();
            const box = document.getElementById('waResults');
            if(!list.length){ box.innerHTML = '<div style="padding:12px;font-size:12px;color:#9ca3af;">No chats found</div>'; box.classList.add('show'); return; }
            box.innerHTML = list.map(c => `
                <div class="sj-wa-opt" onclick="selectWaChat('${esc(c.phone)}','${esc(c.name)}',${c.inquiry_id||'null'})">
                    <div class="sj-wa-opt-name">${esc(c.name)}</div>
                    <div class="sj-wa-opt-phone">+${esc(c.phone)}${c.category ? ' · '+esc(c.category) : ''}</div>
                </div>`).join('');
            box.classList.add('show');
        } catch(e){}
    }, 300);
});

function selectWaChat(phone, name, inquiryId){
    document.getElementById('whatsappPhone').value = phone;
    document.getElementById('customerPhone').value = phone;
    if(!document.getElementById('customerName').value) document.getElementById('customerName').value = name;
    if(inquiryId) document.getElementById('inquiryId').value = inquiryId;
    document.getElementById('waLinkedName').textContent = name;
    document.getElementById('waLinkedPhone').textContent = '+' + phone;
    document.getElementById('waLinked').style.display = 'block';
    document.getElementById('waResults').classList.remove('show');
    document.getElementById('waSearch').value = '';
}

function clearWaLink(){
    document.getElementById('whatsappPhone').value = '';
    document.getElementById('inquiryId').value = '';
    document.getElementById('waLinked').style.display = 'none';
}

@if($inquiry)
selectWaChat(@json($inquiry->phone_number), @json($inquiry->customer_name ?? $inquiry->phone_number), @json($inquiry->id));
@endif

// Material search
let matTimer;
document.getElementById('matSearchInput').addEventListener('input', function(){
    clearTimeout(matTimer);
    const q = this.value.trim();
    if(q.length < 1){ document.getElementById('matResults').classList.remove('show'); return; }
    matTimer = setTimeout(async ()=>{
        try {
            const r = await fetch(MAT_SEARCH_URL + '?q=' + encodeURIComponent(q), {headers:{Accept:'application/json'}});
            const list = await r.json();
            const box = document.getElementById('matResults');
            box.innerHTML = list.map(m => `
                <div class="sj-mat-opt" onclick='addMaterial(${JSON.stringify(m)})'>
                    <strong>${esc(m.name)}</strong> <small>Stock: ${m.stock} ${esc(m.unit)} · Rs ${m.price}</small>
                </div>`).join('');
            box.classList.add('show');
        } catch(e){}
    }, 250);
});

document.addEventListener('click', e => {
    if(!e.target.closest('.sj-mat-search-wrap')) document.getElementById('matResults').classList.remove('show');
    if(!e.target.closest('.sj-wa-search')) document.getElementById('waResults').classList.remove('show');
});

function addMaterial(m){
    if(addedMaterials.has(m.id)) return;
    addedMaterials.set(m.id, m);
    const i = matIdx++;
    const row = document.createElement('div');
    row.className = 'sj-mat-row';
    row.dataset.matId = m.id;
    row.innerHTML = `
        <div><strong>${esc(m.name)}</strong><br><small style="color:#9ca3af;">Stock: ${m.stock} ${esc(m.unit)}</small>
            <input type="hidden" name="materials[${i}][material_id]" value="${m.id}">
        </div>
        <input type="number" name="materials[${i}][quantity]" class="sj-input" min="0.001" step="0.001" placeholder="Qty" required>
        <button type="button" class="sj-btn-sm danger" onclick="removeMaterial(${m.id}, this)">✕</button>`;
    document.getElementById('matList').appendChild(row);
    document.getElementById('matSearchInput').value = '';
    document.getElementById('matResults').classList.remove('show');
}

function removeMaterial(id, btn){
    addedMaterials.delete(id);
    btn.closest('.sj-mat-row').remove();
}

// Tasks
function addTaskRow(stage='', title='', mins=''){
    const i = taskIdx++;
    const row = document.createElement('div');
    row.className = 'sj-task-row';
    const opts = Object.entries(STAGES).map(([k,v]) => `<option value="${k}" ${k===stage?'selected':''}>${esc(v)}</option>`).join('');
    row.innerHTML = `
        <select name="tasks[${i}][stage]" class="sj-input" required>${opts}</select>
        <input type="text" name="tasks[${i}][title]" class="sj-input" placeholder="Work description" value="${esc(title)}" required>
        <input type="number" name="tasks[${i}][estimated_minutes]" class="sj-input" min="1" max="10080" placeholder="Min" value="${mins}">
        <button type="button" class="sj-btn-sm danger" onclick="this.closest('.sj-task-row').remove()">✕</button>`;
    document.getElementById('taskList').appendChild(row);
}

@foreach(old('tasks', []) as $t)
addTaskRow(@json($t['stage'] ?? 'design'), @json($t['title'] ?? ''), @json($t['estimated_minutes'] ?? ''));
@endforeach

// Due date — calendar picker, no past dates
$(function() {
    const $display = $('#sj-due-display');
    const $hidden  = $('#sj-due-date');
    const $err     = $('#sj-due-err');

    if (typeof $.fn.datepicker === 'undefined') return;

    $display.datepicker({
        autoclose: true,
        format: typeof datepicker_date_format !== 'undefined' ? datepicker_date_format : 'dd/mm/yyyy',
        startDate: new Date(),
        todayHighlight: true,
        clearBtn: false,
        orientation: 'bottom auto',
    }).on('changeDate', function(e) {
        if (e.date && typeof moment !== 'undefined') {
            $hidden.val(moment(e.date).format('YYYY-MM-DD'));
        }
        $err.hide();
    });

    if ($hidden.val()) {
        const parts = $hidden.val().match(/^(\d{4})-(\d{2})-(\d{2})$/);
        if (parts) {
            $display.datepicker('update', new Date(+parts[1], +parts[2] - 1, +parts[3]));
        }
    }

    $display.on('click focus', function() {
        $(this).datepicker('show');
    });

    document.getElementById('startJobForm')?.addEventListener('submit', function(e) {
        const val = $hidden.val();
        if (!val) {
            $err.hide();
            return;
        }
        const picked = new Date(val + 'T00:00:00');
        const today  = new Date();
        today.setHours(0, 0, 0, 0);
        if (picked < today) {
            e.preventDefault();
            $err.show();
            $display.focus();
        }
    });
});
</script>
@endsection

@extends('layouts.app')
@section('title', 'Production Team & Sections')

@section('css')
<style>
.pteam-page   { padding: 0 20px 60px; max-width: 1100px; margin: 0 auto; }
.pteam-back   { display: inline-flex; align-items: center; gap: 6px; color: #7c5cfc; font-size: 13px; font-weight: 600; text-decoration: none; margin: 18px 0 14px; }
.pteam-back:hover { color: #5b3fd9; text-decoration: none; }
.pteam-hero   {
    background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 55%, #4f46e5 100%);
    border-radius: 16px;
    padding: 24px 28px;
    color: #fff !important;
    margin-bottom: 22px;
    box-shadow: inset 0 -3px 0 #4f46e5, 0 4px 20px rgba(15,23,42,.25);
}
.pteam-hero h1 { font-size: 22px; font-weight: 800; margin: 0 0 6px; color: #fff !important; }
.pteam-hero p  { margin: 0; font-size: 13px; color: #fff !important; opacity: .9; }
.pteam-hero strong { color: #fff !important; }
body.theme-admin-pro #scrollable-container .pteam-hero,
body.theme-admin-pro #scrollable-container .pteam-hero h1,
body.theme-admin-pro #scrollable-container .pteam-hero p,
body.theme-admin-pro #scrollable-container .pteam-hero strong {
    color: #fff !important;
}

.pteam-grid   { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; }
@media (max-width: 800px) { .pteam-grid { grid-template-columns: 1fr; } }

.pteam-card   { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; overflow: hidden; }
.pteam-head   { padding: 14px 18px; display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid transparent; }
.pteam-title  { font-size: 14px; font-weight: 800; display: flex; align-items: center; gap: 8px; }
.pteam-count  { font-size: 11px; font-weight: 700; background: #f3f4f6; padding: 3px 10px; border-radius: 20px; color: #6b7280; }
.pteam-body   { padding: 16px 18px 18px; }

.pteam-add    { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px; }
.pteam-add select, .pteam-add input { flex: 1; min-width: 140px; border: 1px solid #d1d5db; border-radius: 9px; padding: 8px 10px; font-size: 13px; color: #374151; background: #f9fafb; box-sizing: border-box; }
.pteam-add select { min-width: 180px; }
.pteam-wa-label { width: 100%; font-size: 10px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: .05em; margin-bottom: -4px; }
.pteam-add button { background: #7c5cfc; color: #fff; border: none; border-radius: 9px; padding: 8px 14px; font-size: 12px; font-weight: 700; cursor: pointer; white-space: nowrap; align-self: flex-end; }
.pteam-add button:hover { background: #5b3fd9; }

.pteam-list   { display: flex; flex-direction: column; gap: 8px; min-height: 60px; }
.pteam-member { display: flex; align-items: center; gap: 10px; padding: 10px 12px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; }
.pteam-av     { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; color: #fff; flex-shrink: 0; }
.pteam-info   { flex: 1; min-width: 0; }
.pteam-name   { font-size: 13px; font-weight: 700; color: #111827; }
.pteam-email  { font-size: 11px; color: #9ca3af; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.pteam-wa     { font-size: 11px; color: #16a34a; font-weight: 600; margin-top: 2px; }
.pteam-remove { background: #fee2e2; color: #ef4444; border: none; border-radius: 8px; padding: 6px 10px; font-size: 11px; font-weight: 700; cursor: pointer; }
.pteam-remove:hover { background: #fecaca; }
.pteam-head-banner { display:flex; flex-wrap:wrap; align-items:center; gap:8px; padding:10px 12px; margin-bottom:12px; background:#fefce8; border:1px solid #fde68a; border-radius:10px; font-size:12px; }
.pteam-head-badge  { font-weight:800; color:#92400e; }
.pteam-head-name   { font-weight:700; color:#111827; }
.pteam-head-wa     { color:#16a34a; font-weight:600; }
.pteam-head-missing { color:#b45309; font-weight:600; }
.pteam-member.is-head { border-color:#fbbf24; background:#fffbeb; }
.pteam-head-tag { font-size:10px; font-weight:800; color:#92400e; background:#fef3c7; padding:2px 8px; border-radius:20px; margin-left:6px; }
.pteam-make-head { background:#fef3c7; color:#92400e; border:none; border-radius:8px; padding:6px 10px; font-size:11px; font-weight:700; cursor:pointer; white-space:nowrap; }
.pteam-make-head:hover { background:#fde68a; }
.pteam-make-head.is-current { background:#fbbf24; color:#fff; cursor:default; }

.pteam-toast  { position: fixed; bottom: 28px; right: 24px; background: #111827; color: #fff; border-radius: 12px; padding: 12px 20px; font-size: 13px; font-weight: 600; box-shadow: 0 8px 24px rgba(0,0,0,.2); z-index: 9999; display: none; }
.pteam-toast.show { display: block; }
</style>
@endsection

@section('content')
<div class="pteam-page">
    <a href="{{ route('production.index') }}" class="pteam-back">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
        Back to Production Board
    </a>

    <div class="pteam-hero">
        <h1>Production Team & Sections</h1>
        <p>Assign employees to each section and set a <strong>Section Head</strong> for each. The head receives WhatsApp job alerts when work arrives in their section.</p>
    </div>

    <div class="pteam-grid">
        @foreach($workableStages as $stageKey)
        @php
            $color = \App\ProductionJob::stageColor($stageKey);
            $label = $stages[$stageKey];
            $members = $stageEmployees[$stageKey] ?? collect();
            $assignedIds = $members->pluck('user_id')->all();
            $available = $users->filter(fn ($u) => ! in_array($u->id, $assignedIds, true));
        @endphp
        <div class="pteam-card" data-stage="{{ $stageKey }}">
            <div class="pteam-head" style="border-bottom-color:{{ $color }};">
                <div class="pteam-title" style="color:{{ $color }};">{{ $label }}</div>
                <span class="pteam-count" data-count>{{ $members->count() }} members</span>
            </div>
            <div class="pteam-body">
                <div class="pteam-add">
                    <select class="pteam-user-select">
                        <option value="">Select employee…</option>
                        @foreach($available as $user)
                        <option value="{{ $user->id }}">{{ trim($user->full_name) ?: $user->email }}</option>
                        @endforeach
                    </select>
                    <div style="flex:1;min-width:160px;">
                        <div class="pteam-wa-label">WhatsApp Number *</div>
                        <input type="tel" class="pteam-wa-input" placeholder="07XXXXXXXX or 94XXXXXXXXX" required>
                    </div>
                    <button type="button" class="pteam-add-btn" data-stage="{{ $stageKey }}">Add</button>
                </div>

                @php $sectionHead = $members->firstWhere('is_head', true); @endphp
                <div class="pteam-head-banner" data-head-banner>
                    @if($sectionHead && $sectionHead->user)
                    @php
                        $hu = $sectionHead->user;
                        $hname = trim(($hu->surname ?? '') . ' ' . ($hu->first_name ?? '') . ' ' . ($hu->last_name ?? ''));
                    @endphp
                    <span class="pteam-head-badge">👑 Section Head</span>
                    <span class="pteam-head-name">{{ $hname ?: $hu->username }}</span>
                    @if($sectionHead->whatsapp_number)
                    <span class="pteam-head-wa">📱 {{ $sectionHead->whatsapp_number }}</span>
                    @endif
                    @else
                    <span class="pteam-head-missing">⚠ Set a section head — job alerts go to the head's WhatsApp</span>
                    @endif
                </div>

                <div class="pteam-list" id="team-list-{{ $stageKey }}">
                    @forelse($members as $member)
                    @php
                        $u = $member->user;
                        $name = trim(($u->surname ?? '') . ' ' . ($u->first_name ?? '') . ' ' . ($u->last_name ?? ''));
                        $initials = strtoupper(substr($u->first_name ?? $u->username ?? 'U', 0, 1) . substr($u->last_name ?? '', 0, 1));
                    @endphp
                    <div class="pteam-member{{ $member->is_head ? ' is-head' : '' }}" data-id="{{ $member->id }}" data-user-id="{{ $member->user_id }}">
                        <div class="pteam-av" style="background:{{ $color }};">{{ $initials ?: 'U' }}</div>
                        <div class="pteam-info">
                            <div class="pteam-name">
                                {{ $name ?: $u->username }}
                                @if($member->is_head)<span class="pteam-head-tag">Head</span>@endif
                            </div>
                            <div class="pteam-email">{{ $u->email }}</div>
                            @if($member->whatsapp_number)
                            <div class="pteam-wa">📱 {{ $member->whatsapp_number }}</div>
                            @endif
                        </div>
                        <button type="button" class="pteam-make-head{{ $member->is_head ? ' is-current' : '' }}" data-id="{{ $member->id }}" @if($member->is_head) disabled @endif>
                            {{ $member->is_head ? 'Head' : 'Make Head' }}
                        </button>
                        <button type="button" class="pteam-remove" data-id="{{ $member->id }}">Remove</button>
                    </div>
                    @empty
                    <div class="pteam-empty">No employees in this section yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

<div id="pteamToast" class="pteam-toast"></div>
@endsection

@section('javascript')
<script>
if (!window.__pteamInit) {
window.__pteamInit = true;

const ASSIGN_URL = '{{ route('production.team.assign') }}';
const HEAD_URL   = '{{ route('production.team.head') }}';
const REMOVE_URL = '{{ url('production/team') }}';
const CSRF = '{{ csrf_token() }}';

function toast(msg, isError) {
    const t = document.getElementById('pteamToast');
    t.textContent = msg;
    t.style.background = isError ? '#ef4444' : '#111827';
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 2800);
}

function memberHtml(member, color) {
    const wa = member.whatsapp_number ? `<div class="pteam-wa">📱 ${member.whatsapp_number}</div>` : '';
    const headClass = member.is_head ? ' is-head' : '';
    const headTag = member.is_head ? '<span class="pteam-head-tag">Head</span>' : '';
    const headBtnClass = member.is_head ? ' is-current' : '';
    const headBtnDisabled = member.is_head ? ' disabled' : '';
    const headBtnLabel = member.is_head ? 'Head' : 'Make Head';
    return `<div class="pteam-member${headClass}" data-id="${member.id}" data-user-id="${member.user_id}">
        <div class="pteam-av" style="background:${color};">${member.initials}</div>
        <div class="pteam-info">
            <div class="pteam-name">${member.name}${headTag}</div>
            <div class="pteam-email">${member.email || ''}</div>
            ${wa}
        </div>
        <button type="button" class="pteam-make-head${headBtnClass}" data-id="${member.id}"${headBtnDisabled}>${headBtnLabel}</button>
        <button type="button" class="pteam-remove" data-id="${member.id}">Remove</button>
    </div>`;
}

function updateCount(card) {
    const cnt = card.querySelectorAll('.pteam-member').length;
    const el = card.querySelector('[data-count]');
    if (el) el.textContent = cnt + ' member' + (cnt === 1 ? '' : 's');
    const list = card.querySelector('.pteam-list');
    const empty = list.querySelector('.pteam-empty');
    if (cnt === 0 && !empty) {
        list.innerHTML = '<div class="pteam-empty">No employees in this section yet.</div>';
    }
}

document.addEventListener('click', function (e) {
    const addBtn = e.target.closest('.pteam-add-btn');
    if (addBtn) {
        if (addBtn.dataset.loading === '1') return;

        const card = addBtn.closest('.pteam-card');
        const stage = addBtn.dataset.stage;
        const select = card.querySelector('.pteam-user-select');
        const waInput = card.querySelector('.pteam-wa-input');
        const userId = select.value;
        const whatsapp = waInput ? waInput.value.trim() : '';
        if (!userId) { toast('Select an employee first.', true); return; }
        if (!whatsapp || whatsapp.replace(/\D/g, '').length < 9) {
            toast('WhatsApp number is required (min 9 digits).', true);
            return;
        }

        const list = card.querySelector('.pteam-list');
        if (list.querySelector(`.pteam-member[data-user-id="${userId}"]`)) {
            toast('This employee is already in this section.', true);
            return;
        }

        addBtn.dataset.loading = '1';
        addBtn.disabled = true;

        fetch(ASSIGN_URL, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ stage, user_id: parseInt(userId, 10), whatsapp_number: whatsapp })
        })
        .then(r => r.json())
        .then(d => {
            if (!d.success) { toast(d.message || 'Failed.', true); return; }

            if (!list.querySelector(`.pteam-member[data-id="${d.member.id}"], .pteam-member[data-user-id="${d.member.user_id}"]`)) {
                const empty = list.querySelector('.pteam-empty');
                if (empty) empty.remove();
                const stageColors = @json($stageColors);
                list.insertAdjacentHTML('beforeend', memberHtml(d.member, stageColors[stage]));
            }

            select.querySelector(`option[value="${userId}"]`)?.remove();
            select.value = '';
            if (waInput) waInput.value = '';
            updateCount(card);
            toast(d.whatsapp_sent
                ? 'Employee added! Login credentials sent via WhatsApp.'
                : 'Employee added, but WhatsApp failed: ' + (d.whatsapp_message || 'check WhatsApp service.'));
        })
        .catch(() => toast('Request failed.', true))
        .finally(() => {
            addBtn.dataset.loading = '0';
            addBtn.disabled = false;
        });
        return;
    }

    const headBtn = e.target.closest('.pteam-make-head');
    if (headBtn && !headBtn.disabled) {
        const id = headBtn.dataset.id;
        const card = headBtn.closest('.pteam-card');
        fetch(HEAD_URL, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ assignment_id: parseInt(id, 10) })
        })
        .then(r => r.json())
        .then(d => {
            if (!d.success) { toast(d.message || 'Failed to set head.', true); return; }
            card.querySelectorAll('.pteam-member').forEach(row => {
                row.classList.remove('is-head');
                const nameEl = row.querySelector('.pteam-name');
                nameEl?.querySelector('.pteam-head-tag')?.remove();
                const btn = row.querySelector('.pteam-make-head');
                if (btn) {
                    btn.disabled = false;
                    btn.classList.remove('is-current');
                    btn.textContent = 'Make Head';
                }
            });
            const row = headBtn.closest('.pteam-member');
            row.classList.add('is-head');
            row.querySelector('.pteam-name')?.insertAdjacentHTML('beforeend', '<span class="pteam-head-tag">Head</span>');
            headBtn.disabled = true;
            headBtn.classList.add('is-current');
            headBtn.textContent = 'Head';
            const banner = card.querySelector('[data-head-banner]');
            if (banner && d.head) {
                banner.innerHTML = `<span class="pteam-head-badge">👑 Section Head</span>
                    <span class="pteam-head-name">${d.head.name}</span>
                    ${d.head.whatsapp_number ? `<span class="pteam-head-wa">📱 ${d.head.whatsapp_number}</span>` : ''}`;
            }
            toast('Section head updated — job alerts will go to this WhatsApp.');
        })
        .catch(() => toast('Request failed.', true));
        return;
    }

    const btn = e.target.closest('.pteam-remove');
    if (!btn) return;
    if (!confirm('Remove this employee from the section?')) return;

    const id = btn.dataset.id;
    const card = btn.closest('.pteam-card');
    fetch(REMOVE_URL + '/' + id, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(d => {
        if (!d.success) { toast('Failed to remove.', true); return; }
        const row = btn.closest('.pteam-member');
        row.remove();
        updateCount(card);
        toast('Removed.');
        location.reload();
    })
    .catch(() => toast('Request failed.', true));
});

} // __pteamInit
</script>
@endsection

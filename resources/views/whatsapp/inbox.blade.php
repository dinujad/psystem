@extends('layouts.app')
@section('title', 'WhatsApp Inbox')

@section('css')
<style>
/* ── reset ── */
.content-wrapper { background: #ddd !important; padding: 0 !important; }

/* ── shell ── */
.wa-app {
    display: flex;
    height: calc(100vh - 100px);
    min-height: 500px;
    overflow: hidden;
    box-shadow: 0 2px 20px rgba(0,0,0,.25);
    margin: 8px 12px;
    border-radius: 6px;
}

/* ═══════════════ LEFT PANEL ═══════════════ */
.wa-left {
    width: 360px;
    min-width: 300px;
    display: flex;
    flex-direction: column;
    background: #fff;
    border-right: 1px solid #e9edef;
}

/* Header */
.wa-left-head {
    background: #f0f2f5;
    padding: 10px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid #e9edef;
    flex-shrink: 0;
}
.wa-left-head-avatar {
    width: 40px; height: 40px; border-radius: 50%;
    background: #075e54; display: flex; align-items: center;
    justify-content: center; color: #fff; font-weight: 700; font-size: 14px;
}
.wa-left-head-title { font-weight: 700; font-size: 17px; color: #111; }
.wa-left-head-actions { display: flex; gap: 6px; align-items: center; }
.wa-icon-btn {
    background: none; border: none; cursor: pointer;
    width: 36px; height: 36px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    color: #54656f; transition: background .15s;
}
.wa-icon-btn:hover { background: #e9edef; }

/* Status badge */
.wa-status-dot {
    width: 8px; height: 8px; border-radius: 50%;
    display: inline-block; margin-right: 4px;
}
.wa-status-dot.on  { background: #25d366; }
.wa-status-dot.off { background: #ef4444; }

/* Search */
.wa-search-bar {
    padding: 8px 12px;
    background: #f0f2f5;
    border-bottom: 1px solid #e9edef;
    flex-shrink: 0;
}
.wa-search-inner {
    background: #fff;
    border-radius: 8px;
    padding: 6px 12px;
    display: flex;
    align-items: center;
    gap: 8px;
    border: none;
}
.wa-search-inner input {
    border: none; outline: none; background: transparent;
    font-size: 14px; flex: 1; color: #3b4a54;
}
.wa-search-inner svg { flex-shrink: 0; color: #54656f; }

/* New chat bar */
.wa-new-bar {
    padding: 6px 12px;
    background: #f0f2f5;
    border-bottom: 1px solid #e9edef;
    display: flex; gap: 6px;
    flex-shrink: 0;
}
.wa-new-bar input {
    flex: 1; border: 1px solid #d1d5db; border-radius: 20px;
    padding: 5px 12px; font-size: 13px; outline: none; background: #fff;
}
.wa-new-bar input:focus { border-color: #25d366; }
.wa-new-bar button {
    background: #25d366; border: none; color: #fff;
    border-radius: 20px; padding: 5px 14px; font-size: 12px;
    font-weight: 600; cursor: pointer; white-space: nowrap;
}
.wa-new-bar button:hover { background: #1da851; }

/* Thread list */
.wa-threads { overflow-y: auto; flex: 1; }

.wa-thread {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 16px;
    border-bottom: 1px solid #f0f2f5;
    cursor: pointer; transition: background .1s; position: relative;
}
.wa-thread:hover  { background: #f5f6f6; }
.wa-thread.active { background: #f0f2f5; }
@keyframes wa-flash { 0%{background:#d9f7e5}100%{background:transparent} }
.wa-thread-new { animation: wa-flash 3s ease-out; }

.wa-avatar {
    width: 48px; height: 48px; border-radius: 50%;
    background: #dfe5e7; display: flex; align-items: center;
    justify-content: center; color: #54656f; font-weight: 700;
    font-size: 17px; flex-shrink: 0;
}
.wa-avatar.green  { background: #25d366; color: #fff; }
.wa-avatar.teal   { background: #128c7e; color: #fff; }
.wa-avatar.purple { background: #6c63ff; color: #fff; }
.wa-avatar-img { padding: 0; overflow: hidden; background: #dfe5e7; }
.wa-avatar-img img { width: 100%; height: 100%; object-fit: cover; display: block; }

.wa-thread-body { flex: 1; min-width: 0; }
.wa-thread-top  { display: flex; justify-content: space-between; align-items: baseline; }
.wa-thread-name { font-weight: 600; font-size: 14.5px; color: #111; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.wa-thread-time { font-size: 11.5px; color: #667781; flex-shrink: 0; margin-left: 4px; }
.wa-thread-bot  { display: flex; justify-content: space-between; align-items: center; margin-top: 2px; }
.wa-thread-preview { font-size: 13px; color: #667781; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; flex: 1; }
.wa-thread.has-unread .wa-thread-name { color: #111; }
.wa-thread.has-unread .wa-thread-preview { color: #111; font-weight: 500; }
.wa-thread.has-unread .wa-thread-time { color: #25d366; font-weight: 600; }
.wa-unread {
    background: #25d366; color: #fff; font-size: 11px; font-weight: 700;
    padding: 1px 6px; border-radius: 20px; flex-shrink: 0; margin-left: 4px;
    display: none; min-width: 20px; text-align: center;
}
.wa-unread.show { display: inline-block; }

.wa-no-threads { padding: 50px 20px; text-align: center; color: #667781; font-size: 13px; line-height: 2; }

/* ═══════════════ RIGHT PANEL ═══════════════ */
.wa-right {
    flex: 1;
    display: flex;
    flex-direction: column;
    background: #efeae2;
    overflow: hidden;
    position: relative;
}

/* WhatsApp Web background pattern */
.wa-right::before {
    content: '';
    position: absolute; inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg width='300' height='300' xmlns='http://www.w3.org/2000/svg'%3E%3C/svg%3E");
    opacity: .04; pointer-events: none;
}

/* Splash */
.wa-splash {
    flex: 1; display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    background: #f8f9fa; gap: 14px;
}
.wa-splash-icon { width: 200px; height: 200px; opacity: .15; }
.wa-splash h3 { font-size: 26px; font-weight: 300; color: #41525d; margin: 0; }
.wa-splash p  { font-size: 14px; color: #667781; text-align: center; margin: 0; line-height: 1.7; max-width: 340px; }
.wa-splash-divider { width: 340px; border: none; border-top: 1px solid #e9edef; margin: 8px 0; }

/* Chat header */
.wa-chat-head {
    background: #f0f2f5;
    padding: 10px 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
    border-bottom: 1px solid #e9edef;
    z-index: 1;
}
.wa-chat-head .wa-avatar { width: 40px; height: 40px; font-size: 14px; }
.wa-chat-info { flex: 1; }
.wa-chat-name { font-weight: 600; font-size: 15px; color: #111; }
.wa-chat-status { font-size: 12px; color: #667781; }
.wa-chat-head-actions { display: flex; gap: 4px; }

/* Messages area */
.wa-sync-banner {
    display: none;
    background: #e8f5e9;
    color: #1b5e20;
    font-size: 12px;
    font-weight: 600;
    text-align: center;
    padding: 8px 12px;
    border-bottom: 1px solid #c8e6c9;
}
.wa-msgs {
    flex: 1;
    overflow-y: auto;
    padding: 12px 8% 8px;
    display: flex;
    flex-direction: column;
    gap: 2px;
    position: relative; z-index: 0;
}

/* Date separator */
.wa-date-sep {
    display: flex; align-items: center; justify-content: center;
    margin: 10px 0 6px;
}
.wa-date-sep span {
    background: #fff;
    color: #667781; font-size: 11.5px;
    padding: 4px 12px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,.12);
    font-weight: 500;
}

/* Message rows */
.wa-row { display: flex; flex-direction: column; margin: 1px 0; }
.wa-row.out { align-items: flex-end; }
.wa-row.in  { align-items: flex-start; }

.wa-bubble {
    max-width: 65%;
    padding: 6px 10px 20px;
    border-radius: 8px;
    font-size: 14px;
    line-height: 1.5;
    word-break: break-word;
    white-space: pre-wrap;
    box-shadow: 0 1px 2px rgba(0,0,0,.13);
    position: relative;
    min-width: 80px;
}
.wa-bubble.out {
    background: #d9fdd3;
    border-top-right-radius: 0;
}
.wa-bubble.in {
    background: #fff;
    border-top-left-radius: 0;
}

/* Tail */
.wa-bubble.out::before {
    content: ''; position: absolute; top: 0; right: -8px;
    border: 8px solid transparent;
    border-top: 8px solid #d9fdd3;
    border-right: 0;
}
.wa-bubble.in::before {
    content: ''; position: absolute; top: 0; left: -8px;
    border: 8px solid transparent;
    border-top: 8px solid #fff;
    border-left: 0;
}

.wa-bubble-meta {
    position: absolute; bottom: 3px; right: 8px;
    display: flex; align-items: center; gap: 3px;
    font-size: 11px; color: #667781;
}
.wa-tick { font-size: 12px; }
.wa-tick.sent   { color: #667781; }
.wa-tick.failed { color: #ef4444; }

/* Media in bubbles */
.wa-media-img {
    max-width: 260px; max-height: 260px;
    border-radius: 6px; cursor: pointer;
    display: block; object-fit: cover;
    margin-bottom: 2px;
}
.wa-media-img:hover { opacity: .92; }
.wa-media-doc {
    display: flex; align-items: center; gap: 10px;
    background: rgba(0,0,0,.06); border-radius: 6px;
    padding: 10px 12px; margin-bottom: 2px;
    text-decoration: none; color: #111;
    font-size: 13px;
}
.wa-media-doc:hover { background: rgba(0,0,0,.1); }
.wa-media-doc-icon {
    width: 40px; height: 40px; border-radius: 50%;
    background: #25d366; display: flex; align-items: center;
    justify-content: center; font-size: 18px; flex-shrink: 0;
    color: #fff;
}
.wa-media-doc-info { min-width: 0; }
.wa-media-doc-name { font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 160px; }
.wa-media-doc-size { font-size: 11px; color: #667781; }

.wa-loading-msg { text-align: center; color: #667781; font-size: 13px; padding: 20px 0; }

/* Input area */
.wa-input-zone {
    background: #f0f2f5;
    padding: 8px 16px;
    display: flex;
    flex-direction: column;
    gap: 6px;
    flex-shrink: 0;
    border-top: 1px solid #e9edef;
    z-index: 1;
}
.wa-file-strip {
    background: #fff; border-radius: 10px; padding: 8px 12px;
    display: flex; align-items: center; gap: 10px; font-size: 13px;
}
.wa-file-strip-icon { font-size: 22px; }
.wa-file-strip-info { flex: 1; min-width: 0; }
.wa-file-strip-name { font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.wa-file-strip-size { color: #9ca3af; font-size: 11px; }
.wa-file-strip-rm { background: none; border: none; color: #ef4444; cursor: pointer; font-size: 18px; padding: 2px 6px; }

.wa-input-row {
    display: flex; align-items: flex-end; gap: 8px;
}
.wa-input-row textarea {
    flex: 1;
    border: none; outline: none;
    border-radius: 10px;
    padding: 9px 14px;
    font-size: 14px;
    resize: none;
    max-height: 120px;
    line-height: 1.45;
    background: #fff;
    font-family: inherit;
}
.wa-attach-btn, .wa-send-btn {
    width: 42px; height: 42px; border-radius: 50%;
    border: none; cursor: pointer; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    transition: background .15s;
}
.wa-attach-btn { background: transparent; color: #54656f; }
.wa-attach-btn:hover { background: #e9edef; }
.wa-send-btn { background: #00a884; color: #fff; }
.wa-send-btn:hover { background: #008f72; }
.wa-send-btn:disabled { background: #ccc; cursor: not-allowed; }

/* Lightbox */
#wa-lb { display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.92);align-items:center;justify-content:center; }
#wa-lb.open { display:flex; }
#wa-lb img { max-width:90vw;max-height:88vh;border-radius:6px; }
#wa-lb-close { position:absolute;top:14px;right:20px;color:#fff;font-size:30px;cursor:pointer;background:none;border:none;line-height:1; }
#wa-lb-dl { position:absolute;bottom:20px;right:20px; background:#00a884;color:#fff;border:none;padding:8px 16px;border-radius:8px;cursor:pointer;font-size:13px; }

/* Label pills */
.wa-label-pill {
    display: inline-block;
    padding: 1px 7px;
    border-radius: 10px;
    font-size: 10px;
    font-weight: 700;
    color: #fff;
    margin-right: 2px;
    line-height: 1.6;
}
.wa-thread-labels { margin-top: 2px; }

/* Contact panel (shown below chat header when editing) */
.wa-contact-panel {
    background: #fff;
    border-bottom: 1px solid #e9edef;
    padding: 10px 16px;
    flex-shrink: 0;
    display: none;
}
.wa-contact-panel.open { display: flex; flex-direction: column; gap: 8px; }
.wa-cp-row { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.wa-cp-row label { font-size: 12px; color: #667781; font-weight: 600; min-width: 44px; }
.wa-cp-row input {
    border: 1px solid #d1d5db; border-radius: 6px;
    padding: 4px 10px; font-size: 13px; outline: none; flex: 1; max-width: 220px;
}
.wa-cp-row input:focus { border-color: #25d366; }
.wa-cp-save {
    background: #25d366; border: none; color: #fff;
    border-radius: 6px; padding: 4px 14px; font-size: 12px;
    font-weight: 600; cursor: pointer;
}
.wa-cp-save:hover { background: #1da851; }
.wa-label-picker { display: flex; flex-wrap: wrap; gap: 5px; }
.wa-label-opt {
    padding: 2px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 700;
    cursor: pointer; border: 2px solid transparent; color: #fff; transition: opacity .12s, border-color .12s;
}
.wa-label-opt:hover { opacity: .85; }
.wa-label-opt.active { border-color: rgba(0,0,0,.35) !important; box-shadow: 0 0 0 1px rgba(255,255,255,.5) inset; }
.wa-no-labels-msg { font-size: 11.5px; color: #999; }

/* Assignment badges on thread list */
.wa-agent-badge {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 10.5px; font-weight: 600; padding: 1px 7px;
    border-radius: 10px; margin-top: 2px;
}
.wa-agent-badge.mine  { background: #d9f7e5; color: #075e54; }
.wa-agent-badge.other { background: #f0f2f5; color: #54656f; }
.wa-agent-badge.unassigned { background: #fff3cd; color: #856404; }

/* Assignment panel */
.wa-assign-panel {
    background: #fff;
    border-bottom: 1px solid #e9edef;
    padding: 10px 16px;
    flex-shrink: 0;
    display: none;
}
.wa-assign-panel.open { display: flex; flex-direction: column; gap: 8px; }
.wa-assign-row { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.wa-assign-row label { font-size: 12px; color: #667781; font-weight: 600; min-width: 80px; }
.wa-assign-status {
    font-size: 12.5px; font-weight: 600; padding: 3px 12px; border-radius: 20px;
}
.wa-assign-status.mine  { background: #d9f7e5; color: #075e54; }
.wa-assign-status.other { background: #f0f2f5; color: #54656f; }
.wa-assign-status.none  { background: #fff3cd; color: #856404; }
.wa-assign-btn {
    font-size: 11.5px; font-weight: 600; padding: 3px 12px;
    border-radius: 20px; border: none; cursor: pointer; transition: opacity .15s;
}
.wa-assign-btn:hover { opacity: .8; }
.wa-assign-btn.claim    { background: #25d366; color: #fff; }
.wa-assign-btn.transfer { background: #128c7e; color: #fff; }
.wa-assign-btn.close-chat { background: #ef4444; color: #fff; }
.wa-assign-btn.unassign { background: #e9edef; color: #54656f; }

/* Agent picker dropdown */
.wa-agent-picker select {
    border: 1px solid #d1d5db; border-radius: 6px;
    padding: 4px 10px; font-size: 13px; outline: none;
}
.wa-agent-picker select:focus { border-color: #25d366; }

/* Close inquiry modal — force light inputs (dark skin override) */
#close-inquiry-modal { color-scheme: light; }
#close-inquiry-modal > div {
    background: #ffffff !important;
    color: #111827 !important;
    color-scheme: light;
}
#close-inquiry-modal h4 { color: #111827 !important; }
#close-inquiry-modal label { color: #374151 !important; }
#close-inquiry-modal input,
#close-inquiry-modal select,
#close-inquiry-modal textarea {
    background-color: #ffffff !important;
    color: #111827 !important;
    border-color: #e5e7eb !important;
    -webkit-appearance: menulist;
    appearance: auto;
}
#close-inquiry-modal select option {
    background-color: #ffffff !important;
    color: #111827 !important;
}
#close-inquiry-modal p { color: #667781 !important; }
#ci-error { color: #ef4444 !important; }
</style>
@endsection

@section('content')
<div class="wa-app">

    {{-- ═══════════ LEFT ═══════════ --}}
    <div class="wa-left">

        {{-- Header --}}
        <div class="wa-left-head">
            <div style="display:flex;align-items:center;gap:10px;">
                <div class="wa-left-head-avatar">W</div>
                <div>
                    <div class="wa-left-head-title">WhatsApp</div>
                    <div style="font-size:11.5px;color:#667781;">
                        <span class="wa-status-dot off" id="wa-dot"></span>
                        <span id="wa-status-lbl">Checking...</span>
                    </div>
                </div>
            </div>
            <div class="wa-left-head-actions">
                <a href="{{ route('whatsapp.link') }}" class="wa-icon-btn" title="Manage Connection">
                    <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/></svg>
                </a>
            </div>
        </div>

        {{-- New chat --}}
        <div class="wa-new-bar">
            <input type="text" id="wa-new-num" placeholder="New: 0771234567 or 94771234567" maxlength="15"
                   onkeydown="if(event.key==='Enter')startNewChat()">
            <button onclick="startNewChat()">+ New</button>
        </div>

        {{-- Search --}}
        <div class="wa-search-bar">
            <div class="wa-search-inner">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" placeholder="Search or start new chat" oninput="filterThreads(this.value)">
            </div>
        </div>

        {{-- Threads --}}
        <div class="wa-threads" id="wa-thread-list">
            @forelse($threads as $thread)
                @php
                    $colors = ['green','teal','purple'];
                    $ci = abs(crc32($thread->phone_number)) % 3;
                    $unread = (int) ($thread->unread_count ?? 0);
                    $contact = $contacts[$thread->phone_number] ?? null;
                    $displayName = $contact ? $contact->displayName() : ('+' . $thread->phone_number);
                    $hasAvatar = $contact && $contact->hasProfilePicture();
                    $asgn = $assignments[$thread->phone_number] ?? null;
                @endphp
                <div class="wa-thread {{ $unread > 0 ? 'has-unread' : '' }}"
                     data-phone="{{ $thread->phone_number }}"
                     data-last-at="{{ $thread->last_at }}"
                     onclick="openConversation('{{ $thread->phone_number }}', this)">
                    @if($hasAvatar)
                    <div class="wa-avatar wa-avatar-img"><img src="{{ route('whatsapp.avatar', $thread->phone_number) }}" alt=""></div>
                    @else
                    <div class="wa-avatar {{ $colors[$ci] }}">{{ strtoupper(substr($thread->phone_number, -2)) }}</div>
                    @endif
                    <div class="wa-thread-body">
                        <div class="wa-thread-top">
                            <div class="wa-thread-name">{{ $displayName }}</div>
                            <div class="wa-thread-time">{{ \Carbon\Carbon::parse($thread->last_at)->diffForHumans(null, true) }}</div>
                        </div>
                        <div class="wa-thread-bot">
                            <div class="wa-thread-preview">
                                @if($thread->last_direction === 'out')<span style="color:#00a884;">✓✓ </span>@endif
                                @if($thread->last_media_type === 'image')🖼️ Image
                                @elseif($thread->last_media_type === 'document')📄 Document
                                @else{{ Str::limit($thread->last_message, 38) }}
                                @endif
                            </div>
                            <span class="wa-unread {{ $unread > 0 ? 'show' : '' }}">{{ $unread > 99 ? '99+' : $unread }}</span>
                        </div>
                        @if($contact && $contact->labels->isNotEmpty())
                        <div class="wa-thread-labels">
                            @foreach($contact->labels as $lbl)
                            <span class="wa-label-pill" style="background:{{ $lbl->color }};">{{ $lbl->name }}</span>
                            @endforeach
                        </div>
                        @endif
                        @if($asgn && $asgn->assigned_to)
                            @php
                                $agentName = $asgn->agent ? trim(($asgn->agent->first_name ?? '') . ' ' . ($asgn->agent->last_name ?? '')) ?: $asgn->agent->username : '?';
                                $isMe = $asgn->assigned_to === $userId;
                            @endphp
                            <div class="wa-agent-badge {{ $isMe ? 'mine' : 'other' }}">
                                <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                {{ $isMe ? 'You' : $agentName }}
                            </div>
                        @elseif($isAdmin)
                            <div class="wa-agent-badge unassigned">⚠ Unassigned</div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="wa-no-threads">
                    <svg width="60" height="60" fill="none" stroke="#ccc" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21l1.65-3.8a9 9 0 1 1 3.4 2.9z"/></svg>
                    <br>No conversations yet.<br>
                    <small>Messages will appear here automatically.</small>
                </div>
            @endforelse
        </div>
    </div>

    {{-- ═══════════ RIGHT ═══════════ --}}
    <div class="wa-right" id="wa-right">

        {{-- Splash --}}
        <div class="wa-splash" id="wa-splash">
            <svg class="wa-splash-icon" viewBox="0 0 212 212" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M106 0C47.5 0 0 47.5 0 106c0 18.6 4.8 36 13.3 51.2L0 212l56.1-13.1A106 106 0 1 0 106 0zm0 193c-17.2 0-33.4-4.5-47.4-12.5L14 192l11.7-43.4C16.7 134 10 120.7 10 106 10 53 53 10 106 10s96 43 96 96-43 87-96 87z" fill="#BFC6CB"/>
            </svg>
            <h3>WhatsApp Inbox</h3>
            <hr class="wa-splash-divider">
            <p>Select a conversation from the left panel<br>or start a new chat by entering a number above.</p>
        </div>

        {{-- Chat (hidden until thread selected) --}}
        <div id="wa-chat" style="display:none;flex:1;flex-direction:column;overflow:hidden;">

            {{-- Chat header --}}
            <div class="wa-chat-head">
                <div class="wa-avatar green" id="ch-avatar">--</div>
                <div class="wa-chat-info">
                    <div class="wa-chat-name" id="ch-name">+000</div>
                    <div class="wa-chat-status" id="ch-status">loading...</div>
                </div>
                <div class="wa-chat-head-actions" style="display:flex;align-items:center;gap:2px;">
                    {{-- Assignment status pill (always visible when chat open) --}}
                    <span id="ch-assign-pill" style="display:none;font-size:11px;font-weight:700;padding:2px 9px;border-radius:20px;margin-right:4px;"></span>
                    <button class="wa-icon-btn" id="ch-assign-btn" onclick="toggleAssignPanel()" title="Assign / Transfer chat">
                        <svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </button>
                    <button class="wa-icon-btn" id="ch-contact-btn" onclick="toggleContactPanel()" title="Edit name &amp; labels">
                        <svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </button>
                    <button class="wa-icon-btn" id="ch-fix-btn" onclick="showFixNumber()" title="Set real phone number" style="display:none;">
                        ✏️
                    </button>
                    <button class="wa-icon-btn" id="ch-delete-btn" onclick="deleteChat()" title="Delete chat" style="color:#ef4444;">
                        <svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                    </button>
                </div>
            </div>

            {{-- Contact info panel (name editor + label picker) --}}
            <div class="wa-contact-panel" id="wa-contact-panel">
                <div class="wa-cp-row">
                    <label>Name</label>
                    <input type="text" id="cp-name" placeholder="e.g. Kasun Perera" maxlength="120">
                    <button class="wa-cp-save" onclick="saveContactName()">Save</button>
                </div>
                <div class="wa-cp-row" style="align-items:flex-start;">
                    <label style="padding-top:3px;">Labels</label>
                    <div class="wa-label-picker" id="cp-label-picker">
                        <span class="wa-no-labels-msg">Loading...</span>
                    </div>
                </div>
            </div>

            {{-- Assignment panel --}}
            <div class="wa-assign-panel" id="wa-assign-panel">
                <div class="wa-assign-row">
                    <label>Assigned to</label>
                    <span id="ap-status" class="wa-assign-status none">Unassigned</span>
                    <button class="wa-assign-btn claim" id="ap-claim-btn" onclick="claimChat()" style="display:none;">Claim for me</button>
                    @if($isAdmin)
                    <button class="wa-assign-btn unassign" id="ap-unassign-btn" onclick="unassignChat()" style="display:none;">Remove</button>
                    @endif
                </div>
                <div class="wa-assign-row wa-agent-picker" id="ap-picker-row" style="display:none;">
                    <label>{{ $isAdmin ? 'Assign to' : 'Transfer to' }}</label>
                    <select id="ap-agent-select">
                        <option value="">— pick agent —</option>
                    </select>
                    <button class="wa-assign-btn transfer" onclick="assignToSelected()">{{ $isAdmin ? 'Assign' : 'Transfer' }}</button>
                </div>
                <div class="wa-assign-row">
                    <label></label>
                    <button class="wa-assign-btn close-chat" onclick="closeChat()">✓ Close chat</button>
                </div>
            </div>

            {{-- LID fix banner --}}
            <div id="ch-lid-banner" style="display:none;background:#fff3cd;padding:8px 16px;font-size:12.5px;display:none;align-items:center;gap:8px;border-bottom:1px solid #ffc107;flex-shrink:0;">
                <span>⚠️ This number is a WhatsApp internal ID. Enter the real phone number:</span>
                <input id="ch-lid-input" type="text" placeholder="e.g. 94771234567"
                    style="border:1px solid #ffc107;border-radius:6px;padding:3px 8px;font-size:12px;outline:none;width:150px;">
                <button onclick="applyRealNumber()"
                    style="background:#25d366;border:none;color:#fff;border-radius:6px;padding:4px 12px;font-size:12px;cursor:pointer;">Save</button>
                <button onclick="document.getElementById('ch-lid-banner').style.display='none'"
                    style="background:none;border:none;color:#666;cursor:pointer;font-size:16px;">✕</button>
            </div>

            <div class="wa-sync-banner" id="wa-sync-banner">Syncing chat history from WhatsApp…</div>

            {{-- Messages --}}
            <div class="wa-msgs" id="wa-msgs">
                <div class="wa-loading-msg">Select a chat to begin</div>
            </div>

            {{-- Input --}}
            <div class="wa-input-zone">
                <div id="wa-file-strip" class="wa-file-strip" style="display:none;">
                    <span class="wa-file-strip-icon" id="fs-icon">📎</span>
                    <div class="wa-file-strip-info">
                        <div class="wa-file-strip-name" id="fs-name"></div>
                        <div class="wa-file-strip-size" id="fs-size"></div>
                    </div>
                    <button class="wa-file-strip-rm" onclick="clearFile()" title="Remove">✕</button>
                </div>
                <div class="wa-input-row">
                    <input type="file" id="wa-file-in" accept="image/*,.pdf,video/mp4" style="display:none" onchange="onFile(this)">
                    <button class="wa-attach-btn" onclick="document.getElementById('wa-file-in').click()" title="Attach">
                        <svg width="21" height="21" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                    </button>
                    <textarea id="wa-txt" rows="1" placeholder="Type a message"
                        onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();send();}"
                        oninput="this.style.height='auto';this.style.height=Math.min(this.scrollHeight,120)+'px'"></textarea>
                    <button class="wa-send-btn" id="wa-send" onclick="send()">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Lightbox --}}
<div id="wa-lb">
    <button id="wa-lb-close" onclick="document.getElementById('wa-lb').classList.remove('open')">✕</button>
    <img id="wa-lb-img" src="" alt="">
    <button id="wa-lb-dl" onclick="dlImg()">⬇ Download</button>
</div>

{{-- ── Close Chat Inquiry Modal ── --}}
<div id="close-inquiry-modal" style="display:none;position:fixed;inset:0;z-index:9000;background:rgba(0,0,0,.45);align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;width:100%;max-width:480px;padding:28px 28px 24px;box-shadow:0 20px 60px rgba(0,0,0,.22);margin:16px;">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
            <div style="width:40px;height:40px;border-radius:12px;background:#ef4444;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="20" height="20" fill="none" stroke="#fff" stroke-width="2.2" viewBox="0 0 24 24"><path d="M5 12l5 5L20 7"/></svg>
            </div>
            <div>
                <h4 style="margin:0;font-size:16px;font-weight:700;color:#111;">Close Chat — Save Inquiry Details</h4>
                <p style="margin:2px 0 0;font-size:12.5px;color:#667781;">Fill in the details before closing. Status starts as <strong>Quotation Waiting</strong> — update later from Inquiry Reports.</p>
            </div>
        </div>

        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">Customer Name <span style="color:#ef4444;">*</span></label>
            <input id="ci-name" type="text" placeholder="e.g. Kamal Perera"
                style="width:100%;border:1.5px solid #e5e7eb;border-radius:8px;padding:9px 12px;font-size:13.5px;outline:none;box-sizing:border-box;"
                onfocus="this.style.borderColor='#7c5cfc'" onblur="this.style.borderColor='#e5e7eb'">
        </div>

        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">Phone Number</label>
            <input id="ci-phone" type="text" readonly
                style="width:100%;border:1.5px solid #e5e7eb;border-radius:8px;padding:9px 12px;font-size:13.5px;background:#f9fafb;color:#6b7280;box-sizing:border-box;">
        </div>

        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">Inquiry Category <span style="color:#ef4444;">*</span></label>
            <select id="ci-cat"
                style="width:100%;border:1.5px solid #e5e7eb;border-radius:8px;padding:9px 12px;font-size:13.5px;outline:none;background:#fff;box-sizing:border-box;"
                onfocus="this.style.borderColor='#7c5cfc'" onblur="this.style.borderColor='#e5e7eb'">
                <option value="">— Select category —</option>
                @foreach(\App\Http\Controllers\WhatsappAgentController::categories() as $cat)
                    <option value="{{ $cat }}">{{ $cat }}</option>
                @endforeach
            </select>
        </div>

        <div style="margin-bottom:18px;">
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">Inquiry Details / Notes</label>
            <textarea id="ci-notes" rows="3" placeholder="Brief description of the customer's inquiry…"
                style="width:100%;border:1.5px solid #e5e7eb;border-radius:8px;padding:9px 12px;font-size:13.5px;resize:vertical;outline:none;box-sizing:border-box;"
                onfocus="this.style.borderColor='#7c5cfc'" onblur="this.style.borderColor='#e5e7eb'"></textarea>
        </div>

        <p id="ci-error" style="color:#ef4444;font-size:12.5px;margin:0 0 12px;min-height:18px;"></p>

        <div style="display:flex;gap:10px;justify-content:flex-end;">
            <button id="ci-cancel-btn"
                style="border:1.5px solid #e5e7eb;background:#fff;color:#374151;border-radius:8px;padding:9px 20px;font-size:13.5px;font-weight:600;cursor:pointer;">
                Cancel
            </button>
            <button id="ci-submit-btn"
                style="background:#ef4444;color:#fff;border:none;border-radius:8px;padding:9px 22px;font-size:13.5px;font-weight:600;cursor:pointer;">
                Close Chat
            </button>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
(function(){
    const CSRF          = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const SEND_URL      = @json(route('whatsapp.sendFromInbox'));
    const THREADS_URL   = @json(route('whatsapp.pollThreads'));
    const LABELS_URL    = @json(route('admin.whatsapp.labels.all'));
    const AGENTS_URL    = @json(route('admin.whatsapp.agents.list'));
    const ASSIGN_BASE   = @json(url('admin/whatsapp/agents'));
    const IS_ADMIN      = @json($isAdmin);
    const MY_USER_ID    = @json($userId);
    const OPEN_PHONE    = @json($openPhone ?? '');

    let phone       = null;
    let lastId      = 0;
    let pollTimer   = null;
    let historySyncTimer = null;
    let selFile     = null;
    let knownPhones = new Set();
    const COLORS    = ['green','teal','purple'];

    // Cache of phone → {name, labels:[]} — populated from server data + AJAX
    const contactMap    = {};
    let   allLabels     = [];   // all system labels, loaded once
    let   allAgents     = [];   // all agents, loaded once
    // Cache of phone → {agent_id, agent_name, is_me} or null
    const assignmentMap = {};

    /* ── Init ── */
    // Seed contactMap from server-rendered PHP data
    @foreach($contacts as $phone_num => $c)
    contactMap['{{ $phone_num }}'] = {
        name:   @json($c->name),
        wa_name: @json($c->wa_name),
        has_avatar: @json($c->hasProfilePicture()),
        labels: @json($c->labels->map(fn($l) => ['id'=>$l->id,'name'=>$l->name,'color'=>$l->color])->values())
    };
    @endforeach

    function displayNameFor(p){
        const c = contactMap[p] || {};
        if (c.name) return c.name;
        if (c.wa_name) return c.wa_name;
        const digits = String(p).replace(/\D/g, '');
        // WhatsApp LID — not a real phone; show label until mapped
        if (digits.length >= 13) return 'Unknown Contact';
        return '+' + p;
    }

    function avatarHtml(p){
        const c = contactMap[p] || {};
        const init = p.slice(-2).toUpperCase();
        const col = COLORS[Math.abs(hashCode(p)) % 3];
        if(c.has_avatar){
            return `<div class="wa-avatar wa-avatar-img"><img src="/whatsapp/avatar/${p}" alt="" loading="lazy"></div>`;
        }
        return `<div class="wa-avatar ${col}">${init}</div>`;
    }

    function mergeContactData(p, data){
        if(!contactMap[p]) contactMap[p] = { labels: [] };
        if(data.contact_name !== undefined) contactMap[p].name = data.contact_name;
        if(data.wa_name !== undefined) contactMap[p].wa_name = data.wa_name;
        if(data.has_avatar !== undefined) contactMap[p].has_avatar = !!data.has_avatar;
        if(data.labels) contactMap[p].labels = data.labels;
    }

    function updateChatAvatar(p){
        const box = document.getElementById('ch-avatar');
        if(!box) return;
        const c = contactMap[p] || {};
        if(c.has_avatar){
            box.className = 'wa-avatar wa-avatar-img';
            box.innerHTML = `<img src="/whatsapp/avatar/${p}" alt="" loading="lazy">`;
        } else {
            const ci = Math.abs(hashCode(p)) % 3;
            box.className = 'wa-avatar ' + COLORS[ci];
            box.textContent = p.slice(-2).toUpperCase();
        }
    }

    // Seed assignmentMap from server-rendered PHP data
    @foreach($assignments as $phone_num => $a)
    assignmentMap['{{ $phone_num }}'] = @json($a->assigned_to ? ['agent_id' => $a->assigned_to, 'agent_name' => \App\Http\Controllers\WhatsappAgentController::agentDisplayName($a->agent), 'is_me' => $a->assigned_to === $userId] : null);
    @endforeach

    document.querySelectorAll('.wa-thread').forEach(el => knownPhones.add(el.dataset.phone));
    sortThreadList(document.getElementById('wa-thread-list'));
    refreshStatus();   setInterval(refreshStatus, 8000);
    refreshThreads();  setInterval(refreshThreads, 5000);
    loadAllLabels();
    loadAllAgents();
    syncDeviceContacts();

    async function syncDeviceContacts(){
        try {
            await fetch('/whatsapp/sync-contacts', {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': CSRF },
            });
            // Names/avatars arrive via webhook — refresh thread list shortly after
            setTimeout(() => refreshThreads(), 3000);
            setTimeout(() => refreshThreads(), 10000);
        } catch(e){}
    }

    /* ── Status ── */
    async function refreshStatus(){
        try {
            const r = await fetch('/whatsapp/status',{headers:{Accept:'application/json'}});
            if(!r.ok) return;
            const d = await r.json();
            const on = d.status === 'connected';
            document.getElementById('wa-dot').className = 'wa-status-dot ' + (on ? 'on' : 'off');
            document.getElementById('wa-status-lbl').textContent = on ? 'Connected' : 'Disconnected';
            if(phone){
                document.getElementById('ch-status').textContent = on ? 'online' : 'reconnecting...';
            }
        } catch(e){
            // Network error — don't update status dot to avoid false "disconnected"
        }
    }

    /* ── Thread poll ── */
    async function refreshThreads(){
        try {
            const r = await fetch(THREADS_URL,{headers:{Accept:'application/json'}});
            const d = await r.json();
            if(!d.threads) return;
            const list = document.getElementById('wa-thread-list');
            d.threads.forEach(t => renderThread(t, list));
            sortThreadList(list);
        } catch(e){}
    }

    function sortThreadList(list){
        if(!list) return;
        const items = [...list.querySelectorAll('.wa-thread')];
        items.sort((a, b) => {
            const ta = parseTS(a.dataset.lastAt).getTime() || 0;
            const tb = parseTS(b.dataset.lastAt).getTime() || 0;
            return tb - ta;
        });
        items.forEach(el => list.appendChild(el));
    }

    // Local "YYYY-MM-DD HH:MM:SS" — same format the server sends, so parseTS
    // interprets it identically to other thread timestamps.
    function nowLocalTS(){
        const d = new Date();
        const p = n => String(n).padStart(2, '0');
        return `${d.getFullYear()}-${p(d.getMonth()+1)}-${p(d.getDate())} ` +
               `${p(d.getHours())}:${p(d.getMinutes())}:${p(d.getSeconds())}`;
    }

    function unreadBadgeHtml(count){
        const n = Number(count);
        if(!Number.isFinite(n) || n <= 0) return '';
        return `<span class="wa-unread show">${n > 99 ? '99+' : n}</span>`;
    }

    function reorderThreads(list, threads){
        sortThreadList(list);
    }

    async function markRead(p){
        try {
            await fetch(`/whatsapp/inbox/${p}/read`, {
                method: 'POST',
                headers: {Accept:'application/json', 'X-CSRF-TOKEN': CSRF},
            });
        } catch(e){}
    }

    function clearUnreadUI(el){
        if(!el) return;
        el.classList.remove('has-unread');
        const badge = el.querySelector('.wa-unread');
        if(badge){ badge.textContent = ''; badge.classList.remove('show'); }
    }

    function renderThread(t, list){
        const p    = String(t.phone_number);
        const isNew = !knownPhones.has(p);
        const unread = Number(t.unread_count) || 0;
        const ci   = Math.abs(hashCode(p)) % 3;
        const col  = COLORS[ci];
        const prev = t.last_media_type === 'image' ? '🖼️ Image'
                   : t.last_media_type === 'document' ? '📄 Document'
                   : esc((t.last_message || '').substring(0, 38));
        const arrow = t.last_direction === 'out' ? '<span style="color:#00a884">✓✓ </span>' : '';
        const ago  = timeAgo(t.last_at);
        const init = p.slice(-2).toUpperCase();
        const isActive = phone === p;

        // Merge contact data from server response into cache
        mergeContactData(p, t);
        if(t.assignment !== undefined){
            assignmentMap[p] = t.assignment;
        }
        const cached = contactMap[p] || {};
        const displayName = esc(displayNameFor(p));
        const labelPills  = (cached.labels || []).map(l =>
            `<span class="wa-label-pill" style="background:${l.color};">${esc(l.name)}</span>`
        ).join('');

        // Assignment badge
        const asgn = assignmentMap[p];
        let agentBadgeHtml = '';
        if(asgn && asgn.agent_id){
            const cls  = asgn.is_me ? 'mine' : 'other';
            const lbl  = asgn.is_me ? 'You' : esc(asgn.agent_name || '?');
            agentBadgeHtml = `<div class="wa-agent-badge ${cls}">👤 ${lbl}</div>`;
        } else if(IS_ADMIN){
            agentBadgeHtml = `<div class="wa-agent-badge unassigned">⚠ Unassigned</div>`;
        }

        let el = list.querySelector(`.wa-thread[data-phone="${p}"]`);
        if(!el){
            el = document.createElement('div');
            el.className     = 'wa-thread';
            el.dataset.phone = p;
            el.onclick       = () => openConversation(p, el);
            knownPhones.add(p);
            const ph = list.querySelector('.wa-no-threads');
            if(ph) ph.remove();
            list.appendChild(el);
            if(isNew && unread > 0){
                el.classList.add('wa-thread-new');
                const notifName = cached.name ? cached.name : ('+' + p);
                showToast('📩 New message from ' + notifName, 'success');
                setTimeout(()=>el.classList.remove('wa-thread-new'), 3000);
            }
        }

        el.dataset.lastAt = t.last_at || nowLocalTS();

        const active = el.classList.contains('active') ? 'active' : '';
        const showUnread = !isActive && unread > 0;
        el.className = `wa-thread ${active}${showUnread ? ' has-unread' : ''}`;
        el.innerHTML = `
            ${avatarHtml(p)}
            <div class="wa-thread-body">
                <div class="wa-thread-top">
                    <div class="wa-thread-name">${displayName}</div>
                    <div class="wa-thread-time">${ago}</div>
                </div>
                <div class="wa-thread-bot">
                    <div class="wa-thread-preview">${arrow}${prev}</div>
                    ${unreadBadgeHtml(showUnread ? unread : 0)}
                </div>
                ${labelPills ? `<div class="wa-thread-labels">${labelPills}</div>` : ''}
                ${agentBadgeHtml}
            </div>`;
        el.onclick = () => openConversation(p, el);
    }

    function bumpThreadToTop(p, preview, direction){
        const list = document.getElementById('wa-thread-list');
        const el = list?.querySelector(`.wa-thread[data-phone="${p}"]`);
        if(!el) return;
        el.dataset.lastAt = nowLocalTS();
        const timeEl = el.querySelector('.wa-thread-time');
        if(timeEl) timeEl.textContent = 'just now';
        const prevEl = el.querySelector('.wa-thread-preview');
        if(prevEl){
            const arrow = direction === 'out' ? '<span style="color:#00a884">✓✓ </span>' : '';
            prevEl.innerHTML = arrow + esc(preview.substring(0, 38));
        }
        sortThreadList(list);
    }

    /* ── Filter threads ── */
    window.filterThreads = function(q){
        const lower = q.toLowerCase().trim();
        const digits = q.replace(/\D/g,'');
        document.querySelectorAll('.wa-thread').forEach(el=>{
            const p = el.dataset.phone;
            const name = (contactMap[p]?.name || '').toLowerCase();
            const matchPhone = !digits || p.includes(digits);
            const matchName  = !lower  || name.includes(lower) || p.includes(lower);
            el.style.display = (matchPhone || matchName) ? '' : 'none';
        });
    };

    /* ── Open conversation ── */
    window.openConversation = async function(p, el){
        phone = p; lastId = 0;
        if(pollTimer) clearInterval(pollTimer);

        document.querySelectorAll('.wa-thread').forEach(t=>t.classList.remove('active'));
        if(el) el.classList.add('active');
        clearUnreadUI(el);
        markRead(p);

        document.getElementById('wa-splash').style.display = 'none';
        const chat = document.getElementById('wa-chat');
        chat.style.display = 'flex';
        chat.style.flex    = '1';
        chat.style.flexDirection = 'column';
        chat.style.overflow = 'hidden';

        const ci  = Math.abs(hashCode(p)) % 3;
        updateChatAvatar(p);
        document.getElementById('ch-status').textContent = 'loading messages...';

        // Close panels when switching chats
        document.getElementById('wa-contact-panel').classList.remove('open');
        document.getElementById('wa-assign-panel').classList.remove('open');

        // Show "Set Real Number" button if this looks like a LID (>12 digits)
        const isLid = p.replace(/\D/g,'').length >= 13;
        document.getElementById('ch-fix-btn').style.display = isLid ? 'flex' : 'none';
        document.getElementById('ch-lid-banner').style.display = isLid ? 'flex' : 'none';
        if(isLid) document.getElementById('ch-lid-input').value = '';

        // Update header with cached name (instant) — then fetch fresh contact data
        updateChatHeaderName(p);
        loadContactPanel(p);
        updateAssignPill(p);

        const box = document.getElementById('wa-msgs');
        box.innerHTML = '<div class="wa-loading-msg">Loading messages...</div>';

        await loadAll(p);
        startHistorySyncWatch(p);
        document.getElementById('wa-txt').focus();
        pollTimer = setInterval(()=>pollNew(), 4000);
    };

    function updateChatHeaderName(p){
        document.getElementById('ch-name').textContent = displayNameFor(p);
    }

    async function loadContactPanel(p){
        try {
            const r = await fetch(`/whatsapp/contact/${p}`, { headers:{ Accept:'application/json' } });
            const d = await r.json();
            if(d.contact){
                mergeContactData(p, {
                    contact_name: d.contact.name,
                    wa_name: d.contact.wa_name,
                    has_avatar: d.contact.has_avatar,
                    labels: d.contact.labels || [],
                });
                updateChatHeaderName(p);
                updateChatAvatar(p);
                // Re-render thread item to reflect updated name/labels
                const threadEl = document.querySelector(`.wa-thread[data-phone="${p}"]`);
                if(threadEl){
                    const nameEl = threadEl.querySelector('.wa-thread-name');
                    if(nameEl) nameEl.textContent = displayNameFor(p);
                    const av = threadEl.querySelector('.wa-avatar');
                    if(av) av.outerHTML = avatarHtml(p);
                    // Rebuild label pills in thread
                    let labelsDiv = threadEl.querySelector('.wa-thread-labels');
                    const pills = (d.contact.labels||[]).map(l =>
                        `<span class="wa-label-pill" style="background:${l.color};">${esc(l.name)}</span>`
                    ).join('');
                    if(pills){
                        if(!labelsDiv){ labelsDiv = document.createElement('div'); labelsDiv.className='wa-thread-labels'; threadEl.querySelector('.wa-thread-body').appendChild(labelsDiv); }
                        labelsDiv.innerHTML = pills;
                    } else if(labelsDiv){ labelsDiv.remove(); }
                }
            } else {
                if(!contactMap[p]) contactMap[p] = { name: null, wa_name: null, has_avatar: false, labels: [] };
            }
            // Populate contact panel inputs
            document.getElementById('cp-name').value = contactMap[p]?.name || '';
            renderLabelPicker(p);
        } catch(e){}
    }

    function renderLabelPicker(p){
        const picker = document.getElementById('cp-label-picker');
        if(!picker) return;
        const assigned = (contactMap[p]?.labels || []).map(l => l.id);
        if(!allLabels.length){
            picker.innerHTML = '<span class="wa-no-labels-msg">No labels yet. <a href="' + @json(route('admin.whatsapp.labels.index')) + '">Create labels</a> first.</span>';
            return;
        }
        picker.innerHTML = allLabels.map(l => {
            const active = assigned.includes(l.id) ? 'active' : '';
            return `<span class="wa-label-opt ${active}" data-lid="${l.id}" style="background:${l.color};" onclick="toggleLabel(${l.id})">${esc(l.name)}</span>`;
        }).join('');
    }

    /* ── Load all labels once ── */
    async function loadAllLabels(){
        try {
            const r = await fetch(LABELS_URL, { headers:{ Accept:'application/json' } });
            const d = await r.json();
            allLabels = d.labels || [];
        } catch(e){}
    }

    /* ── Load all agents once ── */
    async function loadAllAgents(){
        try {
            const r = await fetch(AGENTS_URL, { headers:{ Accept:'application/json' } });
            const d = await r.json();
            allAgents = d.agents || [];
            // Populate the agent select dropdown
            const sel = document.getElementById('ap-agent-select');
            if(sel){
                // Keep the placeholder option, rebuild the rest
                sel.innerHTML = '<option value="">— pick agent —</option>';
                allAgents.forEach(a => {
                    const o = document.createElement('option');
                    o.value = a.id;
                    o.textContent = a.name;
                    sel.appendChild(o);
                });
            }
        } catch(e){}
    }

    /* ── Assignment panel ── */
    window.toggleAssignPanel = function(){
        const panel = document.getElementById('wa-assign-panel');
        const cpPanel = document.getElementById('wa-contact-panel');
        cpPanel.classList.remove('open'); // close contact panel
        if(panel.classList.toggle('open') && phone){
            renderAssignPanel(phone);
        }
    };

    function renderAssignPanel(p){
        const asgn = assignmentMap[p];
        const statusEl   = document.getElementById('ap-status');
        const claimBtn   = document.getElementById('ap-claim-btn');
        const unassignBtn = document.getElementById('ap-unassign-btn');
        const pickerRow  = document.getElementById('ap-picker-row');

        if(asgn && asgn.agent_id){
            if(asgn.is_me){
                statusEl.textContent  = '✅ You';
                statusEl.className    = 'wa-assign-status mine';
                if(claimBtn)   claimBtn.style.display   = 'none';
                if(unassignBtn) unassignBtn.style.display = IS_ADMIN ? 'inline-block' : 'none';
            } else {
                statusEl.textContent  = '👤 ' + (asgn.agent_name || '?');
                statusEl.className    = 'wa-assign-status other';
                if(claimBtn)   claimBtn.style.display   = IS_ADMIN ? 'inline-block' : 'none';
                if(unassignBtn) unassignBtn.style.display = IS_ADMIN ? 'inline-block' : 'none';
            }
        } else {
            statusEl.textContent  = 'Unassigned';
            statusEl.className    = 'wa-assign-status none';
            if(claimBtn)   claimBtn.style.display   = 'inline-block';
            if(unassignBtn) unassignBtn.style.display = 'none';
        }

        // Show assign/transfer picker
        if(pickerRow) pickerRow.style.display = 'flex';
        // Pre-select current agent if any
        const sel = document.getElementById('ap-agent-select');
        if(sel && asgn) sel.value = asgn.agent_id || '';
    }

    function updateAssignPill(p){
        const pill = document.getElementById('ch-assign-pill');
        if(!pill) return;
        const asgn = assignmentMap[p];
        if(asgn && asgn.agent_id){
            pill.textContent  = asgn.is_me ? '✅ You' : '👤 ' + (asgn.agent_name || '?');
            pill.style.cssText = asgn.is_me
                ? 'display:inline-block;background:#d9f7e5;color:#075e54;'
                : 'display:inline-block;background:#f0f2f5;color:#54656f;';
        } else {
            pill.textContent  = '⚠ Unassigned';
            pill.style.cssText = 'display:inline-block;background:#fff3cd;color:#856404;';
        }
    }

    window.claimChat = async function(){
        if(!phone) return;
        const r = await fetch(`${ASSIGN_BASE}/claim/${phone}`, {
            method:'POST', headers:{ Accept:'application/json','X-CSRF-TOKEN':CSRF }
        });
        const d = await r.json();
        if(d.success){
            assignmentMap[phone] = { agent_id: MY_USER_ID, agent_name: d.agent_name, is_me: true };
            renderAssignPanel(phone);
            updateAssignPill(phone);
            updateThreadBadge(phone);
            showToast('✅ Chat claimed', 'success');
        } else { showToast(d.message || 'Failed', 'error'); }
    };

    window.assignToSelected = async function(){
        if(!phone) return;
        const sel = document.getElementById('ap-agent-select');
        const agentId = parseInt(sel?.value);
        if(!agentId){ showToast('Please select an agent', 'error'); return; }

        const endpoint = IS_ADMIN ? `${ASSIGN_BASE}/assign/${phone}` : `${ASSIGN_BASE}/transfer/${phone}`;
        const r = await fetch(endpoint, {
            method:'POST',
            headers:{ 'Content-Type':'application/json', Accept:'application/json','X-CSRF-TOKEN':CSRF },
            body: JSON.stringify({ agent_id: agentId }),
        });
        const d = await r.json();
        if(d.success){
            assignmentMap[phone] = { agent_id: d.agent_id, agent_name: d.agent_name, is_me: d.agent_id === MY_USER_ID };
            renderAssignPanel(phone);
            updateAssignPill(phone);
            updateThreadBadge(phone);
            showToast(`✅ Chat ${IS_ADMIN ? 'assigned' : 'transferred'} to ${d.agent_name}`, 'success');
        } else { showToast(d.message || 'Failed', 'error'); }
    };

    window.unassignChat = async function(){
        if(!phone) return;
        const r = await fetch(`${ASSIGN_BASE}/unassign/${phone}`, {
            method:'POST', headers:{ Accept:'application/json','X-CSRF-TOKEN':CSRF }
        });
        const d = await r.json();
        if(d.success){
            assignmentMap[phone] = null;
            renderAssignPanel(phone);
            updateAssignPill(phone);
            updateThreadBadge(phone);
            showToast('Assignment removed', 'success');
        }
    };

    window.closeChat = function(){
        if(!phone) return;
        // Pre-fill customer name from contact map
        const contact = contactMap[phone] || {};
        document.getElementById('ci-name').value  = contact.name || '';
        document.getElementById('ci-phone').value = phone;
        document.getElementById('ci-cat').value   = '';
        document.getElementById('ci-notes').value = '';
        document.getElementById('ci-error').textContent = '';
        document.getElementById('close-inquiry-modal').style.display = 'flex';
    };

    document.getElementById('ci-cancel-btn').addEventListener('click', function(){
        document.getElementById('close-inquiry-modal').style.display = 'none';
    });

    document.getElementById('ci-submit-btn').addEventListener('click', async function(){
        const name  = document.getElementById('ci-name').value.trim();
        const cat   = document.getElementById('ci-cat').value;
        const notes = document.getElementById('ci-notes').value.trim();
        const errEl = document.getElementById('ci-error');

        if(!name){ errEl.textContent = 'Customer name is required.'; return; }
        if(!cat){  errEl.textContent = 'Please select an inquiry category.'; return; }
        errEl.textContent = '';

        this.disabled = true;
        this.textContent = 'Closing…';

        const r = await fetch(`${ASSIGN_BASE}/close/${phone}`, {
            method:'POST',
            headers:{ Accept:'application/json','X-CSRF-TOKEN':CSRF,'Content-Type':'application/json' },
            body: JSON.stringify({ customer_name: name, inquiry_category: cat, inquiry_notes: notes })
        });
        const d = await r.json();
        this.disabled = false;
        this.textContent = 'Close Chat';

        if(d.success){
            document.getElementById('close-inquiry-modal').style.display = 'none';
            assignmentMap[phone] = null;
            document.getElementById('wa-assign-panel').classList.remove('open');
            updateAssignPill(phone);
            updateThreadBadge(phone);
            showToast('Chat closed & inquiry saved', 'success');
        } else {
            errEl.textContent = d.message || 'Failed to close chat.';
        }
    });

    function updateThreadBadge(p){
        const el = document.querySelector(`.wa-thread[data-phone="${p}"]`);
        if(!el) return;
        let badge = el.querySelector('.wa-agent-badge');
        const asgn = assignmentMap[p];
        if(!badge){
            badge = document.createElement('div');
            const body = el.querySelector('.wa-thread-body');
            if(body) body.appendChild(badge);
        }
        if(asgn && asgn.agent_id){
            badge.className = 'wa-agent-badge ' + (asgn.is_me ? 'mine' : 'other');
            badge.innerHTML = `👤 ${esc(asgn.is_me ? 'You' : (asgn.agent_name || '?'))}`;
        } else if(IS_ADMIN){
            badge.className = 'wa-agent-badge unassigned';
            badge.textContent = '⚠ Unassigned';
        } else {
            badge.remove();
        }
    }

    /* ── Contact panel ── */
    window.toggleContactPanel = function(){
        const panel = document.getElementById('wa-contact-panel');
        document.getElementById('wa-assign-panel').classList.remove('open');
        if(panel.classList.toggle('open') && phone){
            document.getElementById('cp-name').value = contactMap[phone]?.name || '';
            renderLabelPicker(phone);
        }
    };

    window.saveContactName = async function(){
        if(!phone) return;
        const name = document.getElementById('cp-name').value.trim();
        if(!name){ showToast('Please enter a name', 'error'); return; }
        try {
            const r = await fetch(`/whatsapp/contact/${phone}`, {
                method: 'POST',
                headers:{ 'Content-Type':'application/json', Accept:'application/json', 'X-CSRF-TOKEN':CSRF },
                body: JSON.stringify({ name }),
            });
            const d = await r.json();
            if(d.success){
                if(!contactMap[phone]) contactMap[phone] = { name, labels:[] };
                else contactMap[phone].name = name;
                // Update header
                document.getElementById('ch-name').textContent = name;
                // Update thread list item
                const threadEl = document.querySelector(`.wa-thread[data-phone="${phone}"]`);
                if(threadEl){ const ne = threadEl.querySelector('.wa-thread-name'); if(ne) ne.textContent = name; }
                showToast('✅ Name saved', 'success');
            } else { showToast(d.message || 'Failed', 'error'); }
        } catch(e){ showToast('Error saving name', 'error'); }
    };

    window.toggleLabel = async function(labelId){
        if(!phone) return;
        const c = contactMap[phone] || { name:null, labels:[] };
        const has = c.labels.some(l => l.id === labelId);
        const endpoint = has ? `/admin/whatsapp/labels/${labelId}/remove` : `/admin/whatsapp/labels/${labelId}/assign`;
        try {
            const r = await fetch(endpoint, {
                method: 'POST',
                headers:{ 'Content-Type':'application/json', Accept:'application/json', 'X-CSRF-TOKEN':CSRF },
                body: JSON.stringify({ phone }),
            });
            const d = await r.json();
            if(d.success){
                if(has){ c.labels = c.labels.filter(l => l.id !== labelId); }
                else {
                    const lbl = allLabels.find(l => l.id === labelId);
                    if(lbl) c.labels.push(lbl);
                }
                contactMap[phone] = c;
                renderLabelPicker(phone);
                // Refresh label pills in thread
                const threadEl = document.querySelector(`.wa-thread[data-phone="${phone}"]`);
                if(threadEl){
                    let labelsDiv = threadEl.querySelector('.wa-thread-labels');
                    const pills = c.labels.map(l =>
                        `<span class="wa-label-pill" style="background:${l.color};">${esc(l.name)}</span>`
                    ).join('');
                    if(pills){
                        if(!labelsDiv){ labelsDiv = document.createElement('div'); labelsDiv.className='wa-thread-labels'; threadEl.querySelector('.wa-thread-body').appendChild(labelsDiv); }
                        labelsDiv.innerHTML = pills;
                    } else if(labelsDiv){ labelsDiv.remove(); }
                }
            }
        } catch(e){ showToast('Error updating label', 'error'); }
    };

    /* ── Delete chat ── */
    window.deleteChat = async function(){
        if(!phone) return;
        const name = contactMap[phone]?.name || ('+' + phone);
        if(!confirm(`Delete all messages with ${name}?\nThis cannot be undone.`)) return;
        try {
            const r = await fetch(`/whatsapp/chat/${phone}`, {
                method: 'DELETE',
                headers:{ Accept:'application/json', 'X-CSRF-TOKEN':CSRF },
            });
            const d = await r.json();
            if(d.success){
                // Remove thread from sidebar
                const threadEl = document.querySelector(`.wa-thread[data-phone="${phone}"]`);
                if(threadEl) threadEl.remove();
                knownPhones.delete(phone);
                // Show splash
                document.getElementById('wa-chat').style.display = 'none';
                document.getElementById('wa-splash').style.display = '';
                document.getElementById('wa-contact-panel').classList.remove('open');
                if(pollTimer){ clearInterval(pollTimer); pollTimer = null; }
                phone = null; lastId = 0;
                showToast('Chat deleted', 'success');
            }
        } catch(e){ showToast('Error deleting chat', 'error'); }
    };

    /* ── Fix LID number ── */
    window.showFixNumber = function(){
        const banner = document.getElementById('ch-lid-banner');
        banner.style.display = banner.style.display === 'none' ? 'flex' : 'none';
    };

    window.applyRealNumber = async function(){
        let real = document.getElementById('ch-lid-input').value.replace(/\D/g,'');
        if(real.startsWith('0') && real.length === 10) real = '94' + real.slice(1);
        if(real.length < 8){ showToast('Enter valid phone number', 'error'); return; }

        try {
            const r = await fetch('/whatsapp/fix-lid', {
                method: 'POST',
                headers: {'Content-Type':'application/json', Accept:'application/json','X-CSRF-TOKEN':CSRF},
                body: JSON.stringify({lid_phone: phone, real_phone: real}),
            });
            const d = await r.json();
            if(d.success){
                showToast('✅ Number updated — reopening chat', 'success');
                document.getElementById('ch-lid-banner').style.display = 'none';
                // Refresh thread list and open updated conversation
                await refreshThreads();
                setTimeout(()=>{
                    const el = document.querySelector(`.wa-thread[data-phone="${real}"]`);
                    if(el) openConversation(real, el);
                }, 500);
            } else {
                showToast(d.message || 'Failed', 'error');
            }
        } catch(e){ showToast('Error', 'error'); }
    };

    /* ── Load ALL messages ── */
    async function checkHistorySync(){
        try {
            const r = await fetch('/whatsapp/sync-status', { headers: { Accept: 'application/json' } });
            const d = await r.json();
            const banner = document.getElementById('wa-sync-banner');
            const syncing = !!(d.history_sync_running || (d.history_sync_queue && d.history_sync_queue > 0));
            if (syncing) {
                const done = d.history_sync_processed || 0;
                const total = d.history_sync_total || 0;
                banner.style.display = 'block';
                banner.textContent = total > 0
                    ? `Syncing chat history from WhatsApp… (${done}/${total})`
                    : 'Syncing chat history from WhatsApp…';
                return true;
            }
            banner.style.display = 'none';
            return false;
        } catch (e) {
            return false;
        }
    }

    function startHistorySyncWatch(p){
        if (historySyncTimer) clearInterval(historySyncTimer);
        checkHistorySync();
        historySyncTimer = setInterval(async ()=>{
            const syncing = await checkHistorySync();
            if (syncing && phone === p) {
                await loadAll(p);
                await refreshThreads();
            } else if (!syncing && historySyncTimer) {
                clearInterval(historySyncTimer);
                historySyncTimer = null;
                if (phone === p) await loadAll(p);
            }
        }, 5000);
    }

    async function loadAll(p){
        try {
            const r = await fetch(`/whatsapp/inbox/${p}/poll?after=0`,{headers:{Accept:'application/json'}});
            const d = await r.json();
            const box = document.getElementById('wa-msgs');
            box.innerHTML = '';

            if(!d.messages || !d.messages.length){
                box.innerHTML = '<div class="wa-loading-msg">No messages yet — say hello! 👋</div>';
                document.getElementById('ch-status').textContent = '0 messages';
                return;
            }

            let lastDate = null;
            d.messages.forEach(m => {
                const mDate = formatDate(m.created_at);
                if(mDate !== lastDate){
                    const sep = document.createElement('div');
                    sep.className = 'wa-date-sep';
                    sep.innerHTML = `<span>${mDate}</span>`;
                    box.appendChild(sep);
                    lastDate = mDate;
                }
                addBubble(m, box);
            });

            const last = d.messages[d.messages.length - 1];
            if(last.id > lastId) lastId = last.id;
            scrollDown(box);
            document.getElementById('ch-status').textContent = d.messages.length + ' message' + (d.messages.length !== 1 ? 's' : '');
        } catch(e){
            document.getElementById('wa-msgs').innerHTML = '<div class="wa-loading-msg" style="color:#ef4444;">Failed to load messages.</div>';
        }
    }

    /* ── Poll new ── */
    async function pollNew(){
        if(!phone) return;
        try {
            const r = await fetch(`/whatsapp/inbox/${phone}/poll?after=${lastId}`,{headers:{Accept:'application/json'}});
            const d = await r.json();
            if(!d.messages || !d.messages.length) return;

            // Always advance lastId so we don't re-fetch these messages
            const last = d.messages[d.messages.length - 1];
            if(last.id > lastId) lastId = last.id;

            // Only render INCOMING messages — outgoing are already shown by
            // optimistic UI when sent, and by loadAll when conversation opens.
            // Adding them here causes duplicate bubbles due to race conditions.
            const incoming = d.messages.filter(m => m.direction === 'in');
            if(!incoming.length) return;

            const box = document.getElementById('wa-msgs');
            let lastDate = box.querySelector('.wa-date-sep:last-of-type span')?.textContent || null;
            incoming.forEach(m => {
                const mDate = formatDate(m.created_at);
                if(mDate !== lastDate){
                    const sep = document.createElement('div');
                    sep.className = 'wa-date-sep';
                    sep.innerHTML = `<span>${mDate}</span>`;
                    box.appendChild(sep);
                    lastDate = mDate;
                }
                addBubble(m, box);
            });
            scrollDown(box);
            markRead(phone);
        } catch(e){}
    }

    /* ── Bubble ── */
    function addBubble(msg, box){
        const ph = box.querySelector('.wa-loading-msg');
        if(ph) ph.remove();

        if(msg.id && !String(msg.id).startsWith('pending-')) {
            if(box.querySelector(`[data-msg-id="${msg.id}"]`)) return;
        }

        const row = document.createElement('div');
        row.className = 'wa-row ' + msg.direction;
        if(msg.id) row.dataset.msgId = msg.id;
        if(msg._pending) row.dataset.pending = '1';

        const t = parseTS(msg.created_at).toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'});

        let tick = '';
        if(msg.direction === 'out'){
            tick = msg.status === 'failed'
                ? '<span class="wa-tick failed">✗</span>'
                : '<span class="wa-tick sent">✓✓</span>';
        }

        let mediaHtml = '';
        if(msg.media_type === 'image'){
            const src = msg.media_path ? `/whatsapp/media/${msg.media_path}` : (msg._objUrl || '');
            if(src) mediaHtml = `<img class="wa-media-img" src="${src}" alt="image"
                onclick="openLb('${src}')" loading="lazy"><br>`;
        } else if(msg.media_type === 'document' || msg.media_type === 'video'){
            const href = msg.media_path ? `/whatsapp/media/${msg.media_path}` : '#';
            const icon = msg.media_type === 'video' ? '🎬' : '📄';
            const fname = esc(msg.media_filename || 'file');
            mediaHtml = `<a class="wa-media-doc" href="${href}" download="${fname}" target="_blank">
                <div class="wa-media-doc-icon">${icon}</div>
                <div class="wa-media-doc-info">
                    <div class="wa-media-doc-name">${fname}</div>
                    <div class="wa-media-doc-size">Tap to download</div>
                </div>
            </a>`;
        } else if(msg.media_type === 'audio'){
            const href = msg.media_path ? `/whatsapp/media/${msg.media_path}` : '#';
            mediaHtml = `<a class="wa-media-doc" href="${href}" target="_blank">
                <div class="wa-media-doc-icon">🎵</div>
                <div class="wa-media-doc-info"><div class="wa-media-doc-name">Audio message</div></div>
            </a>`;
        }

        const textPart = (msg.message && msg.message !== msg.media_filename && msg.message !== ('[' + msg.media_type + ']'))
            ? esc(msg.message) : '';

        row.innerHTML = `<div class="wa-bubble ${msg.direction}">
            ${mediaHtml}${textPart ? '<span>' + textPart + '</span>' : ''}
            <div class="wa-bubble-meta">${t}${tick}</div>
        </div>`;
        box.appendChild(row);
    }

    /* ── Send ── */
    window.send = async function(){
        if(!phone) return;
        const txt  = document.getElementById('wa-txt');
        const btn  = document.getElementById('wa-send');
        const text = txt.value.trim();
        if(!text && !selFile) return;

        btn.disabled = true;
        const box = document.getElementById('wa-msgs');
        const tempId = 'pending-' + Date.now();

        // Optimistic — temp id so poll won't duplicate once server id is known
        addBubble({
            direction:'out', message: text || (selFile ? selFile.name : ''),
            status:'sent', created_at: nowLocalTS(), id: tempId, _pending: true,
            media_type: selFile ? (selFile.type.startsWith('image/') ? 'image' : 'document') : null,
            media_filename: selFile?.name || null,
            _objUrl: selFile && selFile.type.startsWith('image/') ? URL.createObjectURL(selFile) : null,
        }, box);
        scrollDown(box);

        bumpThreadToTop(phone, text || (selFile ? selFile.name : ''), 'out');

        txt.value = ''; txt.style.height = 'auto';
        const f = selFile; clearFile();

        try {
            let body, headers = {Accept:'application/json','X-CSRF-TOKEN':CSRF};
            if(f){
                const fd = new FormData();
                fd.append('phone_number', phone);
                if(text) fd.append('message', text);
                fd.append('file', f);
                body = fd;
            } else {
                headers['Content-Type'] = 'application/json';
                body = JSON.stringify({phone_number: phone, message: text});
            }
            const r = await fetch(SEND_URL,{method:'POST',headers,body});
            const d = await r.json();
            if(!d.success) {
                showToast(d.message || 'Failed to send', 'error');
                box.querySelector(`[data-msg-id="${tempId}"]`)?.remove();
            } else {
                if(d.id) {
                    lastId = Math.max(lastId, Number(d.id));
                    const pending = box.querySelector(`[data-msg-id="${tempId}"]`);
                    if(pending) {
                        pending.dataset.msgId = d.id;
                        pending.removeAttribute('data-pending');
                    }
                }
                refreshThreads();
            }
        } catch(e){ showToast('Connection error','error'); }
        finally { btn.disabled = false; document.getElementById('wa-txt').focus(); }
    };

    /* ── New chat ── */
    window.startNewChat = function(){
        let raw = document.getElementById('wa-new-num').value.replace(/\D/g,'');
        if(raw.startsWith('0') && raw.length === 10) raw = '94' + raw.slice(1);
        if(raw.length < 8){ showToast('Enter a valid number, e.g. 0771234567','error'); return; }
        document.getElementById('wa-new-num').value = '';
        let el = document.querySelector(`.wa-thread[data-phone="${raw}"]`);
        if(!el){
            if(!contactMap[raw]) contactMap[raw] = { name: null, wa_name: null, has_avatar: false, labels: [] };
            const list = document.getElementById('wa-thread-list');
            el = document.createElement('div');
            el.className     = 'wa-thread';
            el.dataset.phone = raw;
            el.onclick       = () => openConversation(raw, el);
            el.innerHTML     = `
                ${avatarHtml(raw)}
                <div class="wa-thread-body">
                    <div class="wa-thread-top">
                        <div class="wa-thread-name">+${raw}</div>
                        <div class="wa-thread-time">now</div>
                    </div>
                    <div class="wa-thread-bot">
                        <div class="wa-thread-preview">New conversation</div>
                    </div>
                </div>`;
            const ph = list.querySelector('.wa-no-threads');
            if(ph) ph.remove();
            list.prepend(el);
            knownPhones.add(raw);
        }
        openConversation(raw, el);
    };

    /* ── File attach ── */
    window.onFile = function(inp){
        const f = inp.files[0];
        if(!f) return;
        if(f.size > 16*1024*1024){ showToast('Max 16 MB','error'); return; }
        selFile = f;
        document.getElementById('fs-icon').textContent = f.type.startsWith('image/') ? '🖼️' : '📄';
        document.getElementById('fs-name').textContent = f.name;
        document.getElementById('fs-size').textContent = (f.size/1024).toFixed(1) + ' KB';
        document.getElementById('wa-file-strip').style.display = 'flex';
    };
    window.clearFile = function(){
        selFile = null;
        document.getElementById('wa-file-in').value = '';
        document.getElementById('wa-file-strip').style.display = 'none';
    };

    /* ── Lightbox ── */
    let lbSrc = '';
    window.openLb = function(src){ lbSrc=src; document.getElementById('wa-lb-img').src=src; document.getElementById('wa-lb').classList.add('open'); };
    window.dlImg  = function(){ const a=document.createElement('a');a.href=lbSrc;a.download='image';a.click(); };
    document.addEventListener('keydown',e=>{ if(e.key==='Escape') document.getElementById('wa-lb').classList.remove('open'); });

    /* ── Helpers ── */
    function scrollDown(b){ if(b) b.scrollTop = b.scrollHeight; }

    function esc(s){
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>');
    }

    // Parse DB timestamps as LOCAL time (DB stores local, not UTC)
    function parseTS(ds){
        if(!ds) return new Date();
        // Strip Z / timezone so browser treats it as local time
        return new Date(String(ds).replace('T',' ').replace(/\.\d+Z?$/,'').replace('Z',''));
    }

    function formatDate(ds){
        const d    = parseTS(ds);
        const now  = new Date();
        const diff = Math.floor((now - d) / 86400000);
        if(diff <= 0)  return 'Today';
        if(diff === 1) return 'Yesterday';
        return d.toLocaleDateString([],{day:'numeric',month:'long',year:'numeric'});
    }

    function timeAgo(ds){
        const diff = (Date.now() - parseTS(ds)) / 1000;
        if(diff <= 0 || diff < 60)  return 'just now';
        if(diff < 3600)  return Math.floor(diff/60) + 'm';
        if(diff < 86400) return Math.floor(diff/3600) + 'h';
        return Math.floor(diff/86400) + 'd';
    }

    function hashCode(s){ let h=0; for(let i=0;i<s.length;i++) h=Math.imul(31,h)+s.charCodeAt(i)|0; return h; }

    function showToast(msg, type){
        const t = document.createElement('div');
        t.style.cssText = `position:fixed;bottom:24px;right:24px;z-index:99999;
            background:${type==='error'?'#ef4444':'#25d366'};color:#fff;
            padding:10px 18px;border-radius:8px;font-size:13px;
            box-shadow:0 2px 10px rgba(0,0,0,.2);max-width:320px;`;
        t.textContent = msg;
        document.body.appendChild(t);
        setTimeout(()=>t.remove(), 3500);
    }

    /* Auto-open chat from ?phone= query (linked from Inquiry Reports) */
    if (OPEN_PHONE) {
        setTimeout(() => {
            let el = document.querySelector(`.wa-thread[data-phone="${OPEN_PHONE}"]`);
            if (el) {
                openConversation(OPEN_PHONE, el);
            } else {
                document.getElementById('wa-new-num').value = OPEN_PHONE;
                startNewChat();
            }
        }, 400);
    }
})();
</script>
@endsection

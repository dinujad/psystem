@extends('layouts.app')
@section('title', 'Conversation')

@section('css')
<style>
.bot-thread { background:#efeae2; border-radius:6px; padding:16px; height:calc(100vh - 320px); min-height:360px; overflow-y:auto; }
.bot-row { display:flex; margin-bottom:8px; }
.bot-row.in  { justify-content:flex-start; }
.bot-row.out { justify-content:flex-end; }
.bot-bubble { max-width:72%; padding:7px 11px; border-radius:8px; font-size:14px; white-space:pre-wrap; word-wrap:break-word; box-shadow:0 1px 1px rgba(0,0,0,.08); }
.bot-bubble.in  { background:#fff; }
.bot-bubble.out { background:#d9fdd3; }
.bot-meta { font-size:10.5px; color:#667781; margin-top:3px; text-align:right; }
.bot-step { font-size:10px; color:#999; }
</style>
@endsection

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        Conversation
        <small class="tw-text-sm md:tw-text-base tw-text-gray-700 tw-font-semibold">+{{ $conversation->phone_number }}</small>
    </h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-9 col-md-offset-1">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        +{{ $conversation->phone_number }}
                        <span id="convStatus" class="label label-default">{{ $conversation->status }}</span>
                    </h3>
                    <div class="box-tools pull-right">
                        <a href="{{ route('admin.whatsapp.conversations.index') }}" class="btn btn-default btn-sm">
                            <i class="fa fa-arrow-left"></i> Back
                        </a>
                        <button id="returnBotBtn" type="button" class="btn btn-warning btn-sm"
                            style="{{ $conversation->status === 'human_takeover' ? '' : 'display:none;' }}">
                            <i class="fa fa-robot"></i> Return to Bot
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="bot-thread" id="botThread">
                        @foreach($logs as $log)
                            <div class="bot-row {{ $log->direction }}" data-id="{{ $log->id }}">
                                <div>
                                    <div class="bot-bubble {{ $log->direction }}">{{ $log->message }}</div>
                                    <div class="bot-meta">
                                        {{ optional($log->created_at)->format('g:i A') }}
                                        @if($log->step_key)<span class="bot-step"> · {{ $log->step_key }}</span>@endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="box-footer">
                    <div class="input-group">
                        <input type="text" id="replyInput" class="form-control" placeholder="Type a reply… (sending takes over from the bot)">
                        <span class="input-group-btn">
                            <button id="replyBtn" class="btn btn-success" type="button"><i class="fa fa-paper-plane"></i> Send</button>
                        </span>
                    </div>
                    <p class="text-muted" style="margin-top:6px;margin-bottom:0;">
                        <small>Sending a manual reply switches this conversation to <b>Human Takeover</b> and pauses the bot.</small>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script>
(function () {
    const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const pollUrl   = "{{ route('admin.whatsapp.conversations.pollThread', $conversation->id) }}";
    const replyUrl  = "{{ route('admin.whatsapp.conversations.reply', $conversation->id) }}";
    const returnUrl = "{{ route('admin.whatsapp.conversations.returnToBot', $conversation->id) }}";

    const STATUS = {
        bot_active:     ['info', 'Bot Active'],
        human_takeover: ['warning', 'Human Takeover'],
        completed:      ['success', 'Completed'],
        idle:           ['default', 'Idle'],
    };

    let lastId = {{ $logs->max('id') ?? 0 }};
    const thread = document.getElementById('botThread');

    function esc(s) { const d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }
    function scrollDown() { thread.scrollTop = thread.scrollHeight; }
    scrollDown();

    function fmtTime(ts) {
        if (!ts) return '';
        const d = new Date(String(ts).replace(' ', 'T'));
        return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    function addBubble(log) {
        if (thread.querySelector('[data-id="' + log.id + '"]')) return;
        const row = document.createElement('div');
        row.className = 'bot-row ' + log.direction;
        row.dataset.id = log.id;
        row.innerHTML =
            '<div><div class="bot-bubble ' + log.direction + '">' + esc(log.message) + '</div>' +
            '<div class="bot-meta">' + fmtTime(log.created_at) +
            (log.step_key ? '<span class="bot-step"> · ' + esc(log.step_key) + '</span>' : '') +
            '</div></div>';
        thread.appendChild(row);
    }

    function setStatus(status) {
        const m = STATUS[status] || ['default', status];
        const el = document.getElementById('convStatus');
        el.className = 'label label-' + m[0];
        el.textContent = m[1];
        document.getElementById('returnBotBtn').style.display = status === 'human_takeover' ? '' : 'none';
    }

    async function poll() {
        try {
            const r = await fetch(pollUrl + '?after=' + lastId, { headers: { Accept: 'application/json' } });
            const d = await r.json();
            if (d.status) setStatus(d.status);
            if (d.logs && d.logs.length) {
                d.logs.forEach(addBubble);
                lastId = d.logs[d.logs.length - 1].id;
                scrollDown();
            }
        } catch (e) {}
    }

    async function sendReply() {
        const input = document.getElementById('replyInput');
        const text = input.value.trim();
        if (!text) return;
        const btn = document.getElementById('replyBtn');
        btn.disabled = true;
        try {
            const r = await fetch(replyUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' },
                body: JSON.stringify({ message: text }),
            });
            const d = await r.json();
            if (d.success) {
                input.value = '';
                if (d.status) setStatus(d.status);
                poll();
            }
        } catch (e) {}
        finally { btn.disabled = false; input.focus(); }
    }

    document.getElementById('replyBtn').addEventListener('click', sendReply);
    document.getElementById('replyInput').addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); sendReply(); }
    });

    document.getElementById('returnBotBtn').addEventListener('click', async function () {
        if (!confirm('Hand this conversation back to the bot?')) return;
        try {
            const r = await fetch(returnUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' },
            });
            const d = await r.json();
            if (d.success) setStatus(d.status);
        } catch (e) {}
    });

    setInterval(poll, 5000);
})();
</script>
@endsection

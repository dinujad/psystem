@extends('layouts.app')
@section('title', 'Bot Conversations')

@php
function statusBadge($status) {
    $map = [
        'bot_active'     => ['info', 'Bot Active'],
        'human_takeover' => ['warning', 'Human Takeover'],
        'completed'      => ['success', 'Completed'],
        'idle'           => ['default', 'Idle'],
    ];
    [$cls, $lbl] = $map[$status] ?? ['default', $status];
    return '<span class="label label-'.$cls.'">'.$lbl.'</span>';
}
@endphp

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        WhatsApp Bot
        <small class="tw-text-sm md:tw-text-base tw-text-gray-700 tw-font-semibold">Live conversations</small>
    </h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Conversations</h3>
                    <div class="box-tools pull-right">
                        <a href="{{ route('admin.whatsapp.flows.index') }}" class="btn btn-default btn-sm">
                            <i class="fa fa-sitemap"></i> Manage Flows
                        </a>
                    </div>
                </div>
                <div class="box-body table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Last Message</th>
                                <th style="width:160px;">Last Activity</th>
                                <th style="width:90px;"></th>
                            </tr>
                        </thead>
                        <tbody id="convBody">
                            @forelse($conversations as $c)
                                <tr data-id="{{ $c['id'] }}">
                                    <td>+{{ $c['phone_number'] }}</td>
                                    <td class="js-status">{!! statusBadge($c['status']) !!}</td>
                                    <td class="js-last">
                                        @if($c['last_direction'] === 'out')<i class="fa fa-reply text-muted"></i> @endif
                                        {{ $c['last_message'] }}
                                    </td>
                                    <td class="js-time">{{ $c['last_interaction_at'] }}</td>
                                    <td>
                                        <a href="{{ route('admin.whatsapp.conversations.show', $c['id']) }}" class="btn btn-xs btn-primary">
                                            <i class="fa fa-comment"></i> Open
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr id="convEmpty"><td colspan="5" class="text-center text-muted">No conversations yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

@section('javascript')
<script>
(function () {
    const pollUrl = "{{ route('admin.whatsapp.conversations.poll') }}";
    const showBase = "{{ url('admin/whatsapp/conversations') }}";

    const STATUS = {
        bot_active:     ['info', 'Bot Active'],
        human_takeover: ['warning', 'Human Takeover'],
        completed:      ['success', 'Completed'],
        idle:           ['default', 'Idle'],
    };

    function esc(s) { const d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }

    function badge(status) {
        const m = STATUS[status] || ['default', status];
        return '<span class="label label-' + m[0] + '">' + m[1] + '</span>';
    }

    async function refresh() {
        try {
            const r = await fetch(pollUrl, { headers: { Accept: 'application/json' } });
            const d = await r.json();
            if (!d.conversations) return;
            const body = document.getElementById('convBody');
            const empty = document.getElementById('convEmpty');
            if (empty && d.conversations.length) empty.remove();

            d.conversations.forEach(function (c) {
                let row = body.querySelector('tr[data-id="' + c.id + '"]');
                if (!row) {
                    row = document.createElement('tr');
                    row.dataset.id = c.id;
                    row.innerHTML =
                        '<td>+' + esc(c.phone_number) + '</td>' +
                        '<td class="js-status"></td>' +
                        '<td class="js-last"></td>' +
                        '<td class="js-time"></td>' +
                        '<td><a href="' + showBase + '/' + c.id + '" class="btn btn-xs btn-primary"><i class="fa fa-comment"></i> Open</a></td>';
                    body.prepend(row);
                }
                row.querySelector('.js-status').innerHTML = badge(c.status);
                row.querySelector('.js-last').innerHTML =
                    (c.last_direction === 'out' ? '<i class="fa fa-reply text-muted"></i> ' : '') + esc(c.last_message);
                row.querySelector('.js-time').textContent = c.last_interaction_at || '';
            });
        } catch (e) {}
    }

    setInterval(refresh, 5000);
})();
</script>
@endsection

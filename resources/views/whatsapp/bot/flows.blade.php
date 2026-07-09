@extends('layouts.app')
@section('title', 'WhatsApp Bot Flows')

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        WhatsApp Bot
        <small class="tw-text-sm md:tw-text-base tw-text-gray-700 tw-font-semibold">Automation flows</small>
    </h1>
</section>

<section class="content">
    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul style="margin:0;padding-left:18px;">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Flows</h3>
                    <div class="box-tools pull-right">
                        <a href="{{ route('admin.whatsapp.conversations.index') }}" class="btn btn-default btn-sm">
                            <i class="fa fa-comments"></i> Live Conversations
                        </a>
                        <button type="button" class="btn btn-primary btn-sm" onclick="openFlowModal()">
                            <i class="fa fa-plus"></i> Add Flow
                        </button>
                    </div>
                </div>
                <div class="box-body table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Trigger Keywords</th>
                                <th>Steps</th>
                                <th>Fallback</th>
                                <th>Active</th>
                                <th style="width:230px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($flows as $flow)
                                <tr>
                                    <td>{{ $flow->name }}</td>
                                    <td>
                                        @forelse($flow->trigger_keywords ?? [] as $kw)
                                            <span class="label label-default">{{ $kw }}</span>
                                        @empty
                                            <span class="text-muted">—</span>
                                        @endforelse
                                    </td>
                                    <td>{{ $flow->steps_count }}</td>
                                    <td>
                                        @if($flow->is_default_fallback)
                                            <span class="label label-warning">Fallback</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <input type="checkbox" class="js-toggle-active" data-id="{{ $flow->id }}"
                                            {{ $flow->is_active ? 'checked' : '' }}>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.whatsapp.flows.builder', $flow->id) }}" class="btn btn-xs btn-success">
                                            <i class="fa fa-sitemap"></i> Builder
                                        </a>
                                        <button class="btn btn-xs btn-info js-edit-flow"
                                            data-id="{{ $flow->id }}"
                                            data-name="{{ $flow->name }}"
                                            data-keywords="{{ implode(', ', $flow->trigger_keywords ?? []) }}"
                                            data-fallback="{{ $flow->is_default_fallback ? 1 : 0 }}"
                                            data-active="{{ $flow->is_active ? 1 : 0 }}">
                                            <i class="fa fa-edit"></i>
                                        </button>
                                        <form action="{{ route('admin.whatsapp.flows.destroy', $flow->id) }}" method="POST"
                                            style="display:inline;" onsubmit="return confirm('Delete this flow and all its steps?');">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-xs btn-danger"><i class="fa fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted">No flows yet. Create one to get started.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Flow create/edit modal --}}
<div class="modal fade" id="flowModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <form class="modal-content" id="flowForm" method="POST" action="{{ route('admin.whatsapp.flows.store') }}">
            @csrf
            <input type="hidden" name="_method" id="flowMethod" value="POST">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" id="flowModalTitle">Add Flow</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" id="flowName" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Trigger Keywords <small class="text-muted">(comma or newline separated, case-insensitive)</small></label>
                    <textarea name="trigger_keywords" id="flowKeywords" class="form-control" rows="2" placeholder="hi, hello, menu"></textarea>
                </div>
                <div class="checkbox">
                    <label><input type="checkbox" name="is_default_fallback" id="flowFallback" value="1"> Default fallback flow (used when no keyword matches)</label>
                </div>
                <div class="checkbox">
                    <label><input type="checkbox" name="is_active" id="flowActive" value="1" checked> Active</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('javascript')
<script>
(function () {
    const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const storeUrl = "{{ route('admin.whatsapp.flows.store') }}";
    const updateBase = "{{ url('admin/whatsapp/flows') }}";

    window.openFlowModal = function () {
        document.getElementById('flowModalTitle').textContent = 'Add Flow';
        document.getElementById('flowForm').action = storeUrl;
        document.getElementById('flowMethod').value = 'POST';
        document.getElementById('flowName').value = '';
        document.getElementById('flowKeywords').value = '';
        document.getElementById('flowFallback').checked = false;
        document.getElementById('flowActive').checked = true;
        $('#flowModal').modal('show');
    };

    document.querySelectorAll('.js-edit-flow').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('flowModalTitle').textContent = 'Edit Flow';
            document.getElementById('flowForm').action = updateBase + '/' + btn.dataset.id;
            document.getElementById('flowMethod').value = 'PUT';
            document.getElementById('flowName').value = btn.dataset.name;
            document.getElementById('flowKeywords').value = btn.dataset.keywords;
            document.getElementById('flowFallback').checked = btn.dataset.fallback === '1';
            document.getElementById('flowActive').checked = btn.dataset.active === '1';
            $('#flowModal').modal('show');
        });
    });

    document.querySelectorAll('.js-toggle-active').forEach(function (cb) {
        cb.addEventListener('change', function () {
            fetch(updateBase + '/' + cb.dataset.id + '/toggle', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            }).catch(function () { cb.checked = !cb.checked; });
        });
    });
})();
</script>
@endsection

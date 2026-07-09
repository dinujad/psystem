@extends('layouts.app')
@section('title', 'Flow Builder')

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        Flow Builder
        <small class="tw-text-sm md:tw-text-base tw-text-gray-700 tw-font-semibold">{{ $flow->name }}</small>
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
                    <h3 class="box-title">Steps</h3>
                    <div class="box-tools pull-right">
                        <a href="{{ route('admin.whatsapp.flows.index') }}" class="btn btn-default btn-sm">
                            <i class="fa fa-arrow-left"></i> Back to Flows
                        </a>
                        <button type="button" class="btn btn-primary btn-sm" onclick="openStepModal()">
                            <i class="fa fa-plus"></i> Add Step
                        </button>
                    </div>
                </div>
                <div class="box-body table-responsive">
                    <p class="text-muted">
                        Use <code>@{{variable}}</code> placeholders in messages to insert collected input
                        (e.g. <code>@{{order_id}}</code>). The <b>first step</b> is where the flow starts.
                    </p>
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th style="width:40px;">#</th>
                                <th>Step Key</th>
                                <th>Type</th>
                                <th>Message</th>
                                <th>Next / Options</th>
                                <th>First</th>
                                <th style="width:110px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($flow->steps as $step)
                                <tr>
                                    <td>{{ $step->sort_order }}</td>
                                    <td><code>{{ $step->step_key }}</code></td>
                                    <td>
                                        <span class="label label-{{ $step->step_type === 'menu' ? 'info' : ($step->step_type === 'final' ? 'success' : 'primary') }}">
                                            {{ $step->step_type }}
                                        </span>
                                        @if($step->triggers_human_takeover)
                                            <span class="label label-warning">human</span>
                                        @endif
                                    </td>
                                    <td style="max-width:280px;white-space:pre-wrap;">{{ \Illuminate\Support\Str::limit($step->message_text, 120) }}</td>
                                    <td>
                                        @if($step->step_type === 'menu')
                                            @foreach($step->options ?? [] as $opt)
                                                <div><small><code>{{ $opt['match'] ?? '' }}</code> → {{ $opt['next_step_key'] ?? '∅' }}</small></div>
                                            @endforeach
                                        @else
                                            <small>{{ $step->next_step_key ? ('→ '.$step->next_step_key) : 'ends flow' }}</small>
                                            @if($step->save_input_as)
                                                <div><small class="text-muted">saves as <code>{{ $step->save_input_as }}</code></small></div>
                                            @endif
                                        @endif
                                    </td>
                                    <td>@if($step->is_first_step)<i class="fa fa-check text-green"></i>@endif</td>
                                    <td>
                                        <button class="btn btn-xs btn-info js-edit-step"
                                            data-step='@json($step)'>
                                            <i class="fa fa-edit"></i>
                                        </button>
                                        <form action="{{ route('admin.whatsapp.steps.destroy', $step->id) }}" method="POST"
                                            style="display:inline;" onsubmit="return confirm('Delete this step?');">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-xs btn-danger"><i class="fa fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted">No steps yet. Add the first step to begin the flow.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Step create/edit modal --}}
<div class="modal fade" id="stepModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <form class="modal-content" id="stepForm" method="POST" action="{{ route('admin.whatsapp.steps.store', $flow->id) }}">
            @csrf
            <input type="hidden" name="_method" id="stepMethod" value="POST">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" id="stepModalTitle">Add Step</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Step Key <small class="text-muted">(letters, numbers, underscore)</small></label>
                        <input type="text" name="step_key" id="stepKey" class="form-control" required pattern="[a-zA-Z0-9_]+">
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Type</label>
                        <select name="step_type" id="stepType" class="form-control" onchange="onStepTypeChange()">
                            <option value="menu">menu</option>
                            <option value="text_input">text_input</option>
                            <option value="final">final</option>
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Sort Order</label>
                        <input type="number" name="sort_order" id="stepSort" class="form-control" value="0">
                    </div>
                </div>

                <div class="form-group">
                    <label>Message Text</label>
                    <textarea name="message_text" id="stepMessage" class="form-control" rows="3" required
                        placeholder="Welcome! How can we help you today?"></textarea>
                </div>

                {{-- Menu options repeater --}}
                <div id="menuOptionsWrap" class="form-group">
                    <label>Menu Options</label>
                    <table class="table table-bordered" style="margin-bottom:6px;">
                        <thead>
                            <tr>
                                <th>Label (shown to user)</th>
                                <th style="width:120px;">Match</th>
                                <th style="width:180px;">Next Step Key</th>
                                <th style="width:40px;"></th>
                            </tr>
                        </thead>
                        <tbody id="optionsBody"></tbody>
                    </table>
                    <button type="button" class="btn btn-default btn-sm" onclick="addOptionRow()">
                        <i class="fa fa-plus"></i> Add Option
                    </button>
                </div>

                {{-- text_input / final next step --}}
                <div id="nextStepWrap" class="form-group">
                    <label>Next Step Key <small class="text-muted">(leave empty to end the flow)</small></label>
                    <input type="text" name="next_step_key" id="stepNext" class="form-control">
                </div>

                {{-- text_input save variable --}}
                <div id="saveInputWrap" class="form-group">
                    <label>Save Input As <small class="text-muted">(variable name, e.g. order_id)</small></label>
                    <input type="text" name="save_input_as" id="stepSaveAs" class="form-control" pattern="[a-zA-Z0-9_]+">
                </div>

                <div class="checkbox">
                    <label><input type="checkbox" name="is_first_step" id="stepFirst" value="1"> This is the first step of the flow</label>
                </div>
                <div class="checkbox">
                    <label><input type="checkbox" name="triggers_human_takeover" id="stepHuman" value="1"> Hand off to a human agent on this step</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Step</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('javascript')
<script>
(function () {
    const storeUrl   = "{{ route('admin.whatsapp.steps.store', $flow->id) }}";
    const updateBase = "{{ url('admin/whatsapp/steps') }}";

    window.onStepTypeChange = function () {
        const type = document.getElementById('stepType').value;
        document.getElementById('menuOptionsWrap').style.display = type === 'menu' ? '' : 'none';
        document.getElementById('nextStepWrap').style.display    = type === 'menu' ? 'none' : '';
        document.getElementById('saveInputWrap').style.display   = type === 'text_input' ? '' : 'none';
    };

    window.addOptionRow = function (label, match, next) {
        const tr = document.createElement('tr');
        tr.innerHTML =
            '<td><input type="text" name="option_label[]" class="form-control input-sm" value="' + (label || '') + '"></td>' +
            '<td><input type="text" name="option_match[]" class="form-control input-sm" value="' + (match || '') + '"></td>' +
            '<td><input type="text" name="option_next[]" class="form-control input-sm" value="' + (next || '') + '"></td>' +
            '<td><button type="button" class="btn btn-xs btn-danger" onclick="this.closest(\'tr\').remove()"><i class="fa fa-times"></i></button></td>';
        document.getElementById('optionsBody').appendChild(tr);
    };

    function clearOptions() { document.getElementById('optionsBody').innerHTML = ''; }

    window.openStepModal = function () {
        document.getElementById('stepModalTitle').textContent = 'Add Step';
        document.getElementById('stepForm').action = storeUrl;
        document.getElementById('stepMethod').value = 'POST';
        document.getElementById('stepKey').value = '';
        document.getElementById('stepType').value = 'menu';
        document.getElementById('stepSort').value = '0';
        document.getElementById('stepMessage').value = '';
        document.getElementById('stepNext').value = '';
        document.getElementById('stepSaveAs').value = '';
        document.getElementById('stepFirst').checked = false;
        document.getElementById('stepHuman').checked = false;
        clearOptions();
        addOptionRow('1. Option one', '1', '');
        onStepTypeChange();
        $('#stepModal').modal('show');
    };

    document.querySelectorAll('.js-edit-step').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const s = JSON.parse(btn.dataset.step);
            document.getElementById('stepModalTitle').textContent = 'Edit Step';
            document.getElementById('stepForm').action = updateBase + '/' + s.id;
            document.getElementById('stepMethod').value = 'PUT';
            document.getElementById('stepKey').value = s.step_key || '';
            document.getElementById('stepType').value = s.step_type || 'menu';
            document.getElementById('stepSort').value = s.sort_order || 0;
            document.getElementById('stepMessage').value = s.message_text || '';
            document.getElementById('stepNext').value = s.next_step_key || '';
            document.getElementById('stepSaveAs').value = s.save_input_as || '';
            document.getElementById('stepFirst').checked = !!s.is_first_step;
            document.getElementById('stepHuman').checked = !!s.triggers_human_takeover;
            clearOptions();
            (s.options || []).forEach(function (o) {
                addOptionRow(o.label || '', o.match || '', o.next_step_key || '');
            });
            onStepTypeChange();
            $('#stepModal').modal('show');
        });
    });
})();
</script>
@endsection

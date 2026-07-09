@extends('layouts.app')
@section('title', 'WhatsApp Labels')

@section('css')
<style>
.label-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    color: #fff;
}
.label-row td { vertical-align: middle !important; }
.color-swatch {
    width: 26px; height: 26px; border-radius: 50%;
    display: inline-block; border: 2px solid rgba(0,0,0,.1);
    cursor: pointer;
}
</style>
@endsection

@section('content')
<div class="content-header">
    <h1>WhatsApp Labels <small>Manage contact labels like WhatsApp Business</small></h1>
</div>
<div class="content">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Labels</h3>
                    <div class="box-tools pull-right">
                        <button class="btn btn-sm btn-success" onclick="openAdd()">
                            <i class="fa fa-plus"></i> New Label
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <table class="table table-bordered table-hover" id="label-table">
                        <thead>
                            <tr>
                                <th width="44">Color</th>
                                <th>Name</th>
                                <th width="120">Contacts</th>
                                <th width="120">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="label-tbody">
                            @foreach($labels as $label)
                            <tr class="label-row" data-id="{{ $label->id }}">
                                <td><span class="color-swatch" style="background:{{ $label->color }};"></span></td>
                                <td>
                                    <span class="label-badge" style="background:{{ $label->color }};">{{ $label->name }}</span>
                                </td>
                                <td>{{ $label->contacts_count }}</td>
                                <td>
                                    <button class="btn btn-xs btn-default" onclick="openEdit({{ $label->id }}, '{{ addslashes($label->name) }}', '{{ $label->color }}')">
                                        <i class="fa fa-edit"></i> Edit
                                    </button>
                                    <button class="btn btn-xs btn-danger" onclick="deleteLabel({{ $label->id }})">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                            @if($labels->isEmpty())
                            <tr id="no-labels-row">
                                <td colspan="4" class="text-center text-muted" style="padding:30px;">
                                    No labels yet. Create one to organise your contacts.
                                </td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Add / Edit Modal --}}
<div class="modal fade" id="label-modal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" id="modal-title">New Label</h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="edit-id">
                <div class="form-group">
                    <label>Label Name</label>
                    <input type="text" class="form-control" id="lbl-name" placeholder="e.g. VIP Customer" maxlength="80">
                </div>
                <div class="form-group">
                    <label>Colour</label><br>
                    <input type="color" id="lbl-color" value="#25d366" style="width:50px;height:36px;border:none;cursor:pointer;">
                    <small class="text-muted" style="margin-left:8px;">Pick a colour for the label badge</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="saveLabel()">Save</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
const CSRF        = document.querySelector('meta[name="csrf-token"]').content;
const BASE_URL    = @json(route('admin.whatsapp.labels.store'));
const UPDATE_BASE = @json(url('admin/whatsapp/labels'));

function openAdd() {
    document.getElementById('edit-id').value  = '';
    document.getElementById('lbl-name').value  = '';
    document.getElementById('lbl-color').value = '#25d366';
    document.getElementById('modal-title').textContent = 'New Label';
    $('#label-modal').modal('show');
}

function openEdit(id, name, color) {
    document.getElementById('edit-id').value  = id;
    document.getElementById('lbl-name').value  = name;
    document.getElementById('lbl-color').value = color;
    document.getElementById('modal-title').textContent = 'Edit Label';
    $('#label-modal').modal('show');
}

async function saveLabel() {
    const id    = document.getElementById('edit-id').value;
    const name  = document.getElementById('lbl-name').value.trim();
    const color = document.getElementById('lbl-color').value;

    if (!name) { alert('Please enter a label name.'); return; }

    const url    = id ? `${UPDATE_BASE}/${id}` : BASE_URL;
    const method = id ? 'PUT' : 'POST';

    const r = await fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' },
        body: JSON.stringify({ name, color }),
    });
    const d = await r.json();
    if (!d.success) { alert(d.message || 'Error saving label'); return; }

    $('#label-modal').modal('hide');
    location.reload();
}

async function deleteLabel(id) {
    if (!confirm('Delete this label? It will be removed from all contacts.')) return;
    const r = await fetch(`${UPDATE_BASE}/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' },
    });
    const d = await r.json();
    if (d.success) {
        const row = document.querySelector(`tr[data-id="${id}"]`);
        if (row) row.remove();
        const tbody = document.getElementById('label-tbody');
        if (!tbody.querySelector('.label-row')) {
            tbody.innerHTML = '<tr id="no-labels-row"><td colspan="4" class="text-center text-muted" style="padding:30px;">No labels yet.</td></tr>';
        }
    }
}
</script>
@endsection

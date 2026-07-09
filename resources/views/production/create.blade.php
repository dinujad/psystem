@extends('layouts.app')
@section('title', 'New Production Job')

@section('css')
<style>
.pcreate-page  { padding: 0 20px 60px; max-width: 760px; margin: 0 auto; }
.pcreate-back  { display: inline-flex; align-items: center; gap: 6px; color: #7c5cfc; font-size: 13px; font-weight: 600; text-decoration: none; margin: 18px 0 14px; }
.pcreate-back:hover { text-decoration: none; color: #5b3fd9; }
.pcreate-card  { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; overflow: hidden; }
.pcreate-head  { background: linear-gradient(135deg, #1e1b4b, #4f46e5); padding: 24px 28px; color: #fff; }
.pcreate-head h2 { font-size: 20px; font-weight: 800; margin: 0 0 4px; }
.pcreate-head p  { font-size: 13px; opacity: .75; margin: 0; }
.pcreate-body  { padding: 28px; }
.pcreate-group { margin-bottom: 18px; }
.pcreate-label { font-size: 12px; font-weight: 700; color: #374151; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 6px; display: block; }
.pcreate-input { width: 100%; border: 1px solid #d1d5db; border-radius: 9px; padding: 9px 12px; font-size: 13px; color: #374151; background: #f9fafb; outline: none; box-sizing: border-box; transition: border-color .15s; }
.pcreate-input:focus { border-color: #7c5cfc; background: #fff; }
.pcreate-row   { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
@media (max-width: 560px) { .pcreate-row { grid-template-columns: 1fr; } }
.pcreate-footer { padding: 18px 28px; border-top: 1px solid #f3f4f6; display: flex; gap: 10px; justify-content: flex-end; }
.pcreate-submit { background: #7c5cfc; color: #fff; border: none; border-radius: 10px; padding: 10px 26px; font-size: 14px; font-weight: 700; cursor: pointer; }
.pcreate-submit:hover { background: #5b3fd9; }
.pcreate-cancel { background: #f3f4f6; color: #374151; border: none; border-radius: 10px; padding: 10px 20px; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; }
</style>
@endsection

@section('content')
<div class="pcreate-page">
    <a href="{{ route('production.index') }}" class="pcreate-back">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
        Back to Production Board
    </a>

    <div class="pcreate-card">
        <div class="pcreate-head">
            <h2>New Production Job</h2>
            <p>Fill in details to start the job through the production workflow.</p>
        </div>
        <form method="POST" action="{{ route('production.store') }}" enctype="multipart/form-data">
            @csrf
            @if($inquiry)
            <input type="hidden" name="inquiry_id" value="{{ $inquiry->id }}">
            @endif
            <div class="pcreate-body">

                @if($errors->any())
                <div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:9px;padding:10px 14px;margin-bottom:18px;font-size:13px;color:#dc2626;">
                    @foreach($errors->all() as $e) <div>• {{ $e }}</div> @endforeach
                </div>
                @endif

                @if($inquiry)
                <div style="background:#ede9fe;border-radius:10px;padding:12px 16px;margin-bottom:18px;font-size:13px;color:#5b21b6;">
                    <strong>📋 Linked from Inquiry #{{ $inquiry->id }}</strong> — {{ $inquiry->customer_name ?? $inquiry->phone_number }}
                    @if($inquiry->inquiry_category) · {{ $inquiry->inquiry_category }} @endif
                </div>
                @endif

                <div class="pcreate-row">
                    <div class="pcreate-group">
                        <label class="pcreate-label">Customer Name <span style="color:#ef4444">*</span></label>
                        <input type="text" name="customer_name" class="pcreate-input" value="{{ old('customer_name', $inquiry->customer_name ?? '') }}" required>
                    </div>
                    <div class="pcreate-group">
                        <label class="pcreate-label">Customer Phone</label>
                        <input type="text" name="customer_phone" class="pcreate-input" value="{{ old('customer_phone', $inquiry->phone_number ?? '') }}">
                    </div>
                </div>

                <div class="pcreate-group">
                    <label class="pcreate-label">Job Title <span style="color:#ef4444">*</span></label>
                    <input type="text" name="title" class="pcreate-input" value="{{ old('title', $inquiry ? (($inquiry->inquiry_category ?? 'Print Job') . ' — ' . ($inquiry->customer_name ?? $inquiry->phone_number)) : '') }}" required placeholder="e.g. Business Cards – Perera &amp; Sons">
                </div>

                <div class="pcreate-group">
                    <label class="pcreate-label">Description / Notes</label>
                    <textarea name="description" class="pcreate-input" rows="3" placeholder="Specs, colors, quantities…">{{ old('description', $inquiry->inquiry_notes ?? '') }}</textarea>
                </div>

                <div class="pcreate-row">
                    <div class="pcreate-group">
                        <label class="pcreate-label">Priority <span style="color:#ef4444">*</span></label>
                        <select name="priority" class="pcreate-input">
                            @foreach(['low' => 'Low', 'normal' => 'Normal', 'high' => 'High', 'urgent' => 'Urgent'] as $v => $l)
                            <option value="{{ $v }}" @selected(old('priority', 'normal') === $v)>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="pcreate-group">
                        <label class="pcreate-label">Due Date</label>
                        <input type="date" name="due_date" class="pcreate-input" value="{{ old('due_date') }}">
                    </div>
                </div>

                <div class="pcreate-group">
                    <label class="pcreate-label">Google Drive Folder URL</label>
                    <input type="url" name="google_drive_url" class="pcreate-input" value="{{ old('google_drive_url') }}" placeholder="https://drive.google.com/drive/folders/…">
                    <div style="font-size:11px;color:#9ca3af;margin-top:4px;">Paste the shared Google Drive folder link for logo and design files.</div>
                </div>

                <div class="pcreate-group">
                    <label class="pcreate-label">Upload Files (optional)</label>
                    <input type="file" name="files[]" multiple class="pcreate-input" style="padding:6px;">
                    <div style="font-size:11px;color:#9ca3af;margin-top:4px;">You can also upload files after creating the job. Max 20 MB each.</div>
                </div>

            </div>
            <div class="pcreate-footer">
                <a href="{{ route('production.index') }}" class="pcreate-cancel">Cancel</a>
                <button type="submit" class="pcreate-submit">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle;margin-right:5px;"><path d="M12 5v14M5 12h14"/></svg>
                    Create Job
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

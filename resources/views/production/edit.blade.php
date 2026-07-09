@extends('layouts.app')
@section('title', 'Edit ' . $job->job_number)

@section('css')
<style>
.pcreate-page  { padding: 0 20px 60px; max-width: 760px; margin: 0 auto; }
.pcreate-back  { display: inline-flex; align-items: center; gap: 6px; color: #7c5cfc; font-size: 13px; font-weight: 600; text-decoration: none; margin: 18px 0 14px; }
.pcreate-back:hover { text-decoration: none; color: #5b3fd9; }
.pcreate-card  { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; overflow: hidden; }
.pcreate-head  { background: linear-gradient(135deg, #1e1b4b, #4f46e5); padding: 22px 28px; color: #fff; }
.pcreate-head h2 { font-size: 19px; font-weight: 800; margin: 0 0 4px; }
.pcreate-head p  { font-size: 12px; opacity: .7; margin: 0; }
.pcreate-body  { padding: 26px 28px; }
.pcreate-group { margin-bottom: 16px; }
.pcreate-label { font-size: 12px; font-weight: 700; color: #374151; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 5px; display: block; }
.pcreate-input { width: 100%; border: 1px solid #d1d5db; border-radius: 9px; padding: 9px 12px; font-size: 13px; color: #374151; background: #f9fafb; outline: none; box-sizing: border-box; transition: border-color .15s; }
.pcreate-input:focus { border-color: #7c5cfc; background: #fff; }
.pcreate-row   { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
@media (max-width: 560px) { .pcreate-row { grid-template-columns: 1fr; } }
.pcreate-footer { padding: 16px 28px; border-top: 1px solid #f3f4f6; display: flex; gap: 10px; justify-content: flex-end; }
.pcreate-submit { background: #7c5cfc; color: #fff; border: none; border-radius: 10px; padding: 10px 26px; font-size: 14px; font-weight: 700; cursor: pointer; }
.pcreate-submit:hover { background: #5b3fd9; }
.pcreate-cancel { background: #f3f4f6; color: #374151; border: none; border-radius: 10px; padding: 10px 20px; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; }
</style>
@endsection

@section('content')
<div class="pcreate-page">
    <a href="{{ route('production.show', $job) }}" class="pcreate-back">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
        Back to Job
    </a>

    <div class="pcreate-card">
        <div class="pcreate-head">
            <h2>Edit {{ $job->job_number }}</h2>
            <p>{{ $job->title }}</p>
        </div>
        <form method="POST" action="{{ route('production.update', $job) }}">
            @csrf @method('PUT')
            <div class="pcreate-body">
                @if($errors->any())
                <div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:9px;padding:10px 14px;margin-bottom:16px;font-size:13px;color:#dc2626;">
                    @foreach($errors->all() as $e) <div>• {{ $e }}</div> @endforeach
                </div>
                @endif

                <div class="pcreate-group">
                    <label class="pcreate-label">Job Title <span style="color:#ef4444">*</span></label>
                    <input type="text" name="title" class="pcreate-input" value="{{ old('title', $job->title) }}" required>
                </div>
                <div class="pcreate-group">
                    <label class="pcreate-label">Description / Notes</label>
                    <textarea name="description" class="pcreate-input" rows="3">{{ old('description', $job->description) }}</textarea>
                </div>
                <div class="pcreate-row">
                    <div class="pcreate-group">
                        <label class="pcreate-label">Priority <span style="color:#ef4444">*</span></label>
                        <select name="priority" class="pcreate-input">
                            @foreach(['low' => 'Low', 'normal' => 'Normal', 'high' => 'High', 'urgent' => 'Urgent'] as $v => $l)
                            <option value="{{ $v }}" @selected(old('priority', $job->priority) === $v)>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="pcreate-group">
                        <label class="pcreate-label">Due Date</label>
                        <input type="date" name="due_date" class="pcreate-input" value="{{ old('due_date', $job->due_date?->format('Y-m-d')) }}">
                    </div>
                </div>
                <div class="pcreate-group">
                    <label class="pcreate-label">Google Drive Folder URL</label>
                    <input type="url" name="google_drive_url" class="pcreate-input" value="{{ old('google_drive_url', $job->google_drive_url) }}" placeholder="https://drive.google.com/…">
                </div>
            </div>
            <div class="pcreate-footer">
                <a href="{{ route('production.show', $job) }}" class="pcreate-cancel">Cancel</a>
                <button type="submit" class="pcreate-submit">Save Changes</button>
            </div>
        </form>
    </div>
</div>
@endsection

@extends('layouts.app')
@section('title', 'WhatsApp Inbox')

@section('content')
<section class="content-header">
    <h1>WhatsApp Inbox</h1>
</section>
<section class="content">
    <div class="alert alert-danger">
        <h4><i class="icon fa fa-warning"></i> Inbox could not load</h4>
        <p>{{ $message ?? 'Something went wrong.' }}</p>
        @if(! empty($detail))
            <pre style="white-space:pre-wrap;font-size:12px;margin-top:10px;">{{ $detail }}</pre>
        @endif
        <p style="margin-top:12px;">
            In Coolify ERP terminal run:<br>
            <code>php artisan migrate --force</code><br>
            <code>php artisan config:clear</code>
        </p>
        <a href="{{ route('whatsapp.link') }}" class="btn btn-primary">Back to Link Device</a>
    </div>
</section>
@endsection

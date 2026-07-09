@extends('layouts.app')
@section('title', 'E MEDIA WhatsApp API')

@section('css')
<style>
.emwa-brand { color: #128c7e; font-weight: 700; }
.emwa-code { background: #1e1e2e; color: #cdd6f4; padding: 12px 14px; border-radius: 8px; font-size: 12px; white-space: pre-wrap; margin: 0; }
.emwa-client-row { border: 1px solid #e9edef; border-radius: 8px; padding: 14px 16px; margin-bottom: 10px; }
</style>
@endsection

@section('content')
<section class="content-header">
    <h1><span class="emwa-brand">E MEDIA WhatsApp API</span> <small>External API via linked WhatsApp device</small></h1>
</section>

<section class="content">
    @if(session('status'))
        <div class="alert alert-{{ !empty(session('status.success')) ? 'success' : 'danger' }} alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            {{ session('status.msg') }}
        </div>
    @endif

    <div class="row">
        <div class="col-md-5">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">WhatsApp Connection</h3>
                </div>
                <div class="box-body">
                    @php $connected = ($waStatus['status'] ?? '') === 'connected'; @endphp
                    <p>
                        Status:
                        <span class="label label-{{ $connected ? 'success' : 'warning' }}">
                            {{ $connected ? 'Connected' : 'Not Connected' }}
                        </span>
                    </p>
                    @if(!$connected)
                        <p class="text-muted">Link your WhatsApp device before using the API.</p>
                        <a href="{{ action([\App\Http\Controllers\WhatsappController::class, 'showQr']) }}" class="btn btn-success btn-sm">Link WhatsApp (QR)</a>
                    @else
                        <p class="text-success">Linked device is ready. All API messages send through this WhatsApp.</p>
                    @endif
                </div>
            </div>

            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title">Create API Client</h3>
                </div>
                <form action="{{ route('admin.whatsapp.emwa.store') }}" method="POST">
                    @csrf
                    <div class="box-body">
                        <div class="form-group">
                            <label>Name / Company</label>
                            <input type="text" name="name" class="form-control" required placeholder="E.g. PrintWorks POS">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" required placeholder="youremail@gmail.com">
                        </div>
                    </div>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary">Create & Activate</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-md-7">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">API Clients ({{ $clients->count() }})</h3>
                    <div class="box-tools">
                        <a href="{{ route('emwa.register') }}" class="btn btn-default btn-sm" target="_blank">Public Registration Page</a>
                    </div>
                </div>
                <div class="box-body">
                    @forelse($clients as $client)
                    <div class="emwa-client-row">
                        <div class="row">
                            <div class="col-sm-7">
                                <strong>{{ $client->name ?: '—' }}</strong>
                                <div class="text-muted">{{ $client->email }}</div>
                                <code style="font-size:11px; word-break:break-all;">{{ $client->api_key }}</code>
                            </div>
                            <div class="col-sm-5 text-right">
                                <span class="label label-{{ $client->is_active ? 'success' : 'default' }}">
                                    {{ $client->is_active ? 'Active' : 'Pending' }}
                                </span>
                                <div style="margin-top:8px;">
                                    @if(!$client->is_active)
                                        <form action="{{ route('admin.whatsapp.emwa.approve', $client->id) }}" method="POST" style="display:inline;">@csrf<button class="btn btn-xs btn-success">Approve</button></form>
                                    @else
                                        <form action="{{ route('admin.whatsapp.emwa.revoke', $client->id) }}" method="POST" style="display:inline;">@csrf<button class="btn btn-xs btn-warning">Revoke</button></form>
                                    @endif
                                    <form action="{{ route('admin.whatsapp.emwa.regenerate', $client->id) }}" method="POST" style="display:inline;">@csrf<button class="btn btn-xs btn-default">New Key</button></form>
                                    <form action="{{ route('admin.whatsapp.emwa.destroy', $client->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete this client?')">@csrf @method('DELETE')<button class="btn btn-xs btn-danger">Delete</button></form>
                                </div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <p class="text-muted text-center" style="padding:30px;">No API clients yet. Create one or share the registration page.</p>
                    @endforelse
                </div>
            </div>

            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">API Documentation</h3>
                </div>
                <div class="box-body">
                    <p><strong>Base URL:</strong> <code>{{ $baseUrl }}</code></p>
                    <p>All requests require <code>email</code> and <code>api_key</code> in POST body.</p>

                    <h4>Send Text Message</h4>
                    <pre class="emwa-code">curl -X POST {{ $baseUrl }}/api/send-message.php \
  -d "email=youremail@gmail.com" \
  -d "api_key=YOUR_API_KEY" \
  -d "phone=947XXXXXXXX" \
  -d "message=Hello from E MEDIA WhatsApp API"</pre>

                    <h4>Send Image</h4>
                    <pre class="emwa-code">curl -X POST {{ $baseUrl }}/api/send-image.php \
  -d "email=youremail@gmail.com" \
  -d "api_key=YOUR_API_KEY" \
  -d "phone=947XXXXXXXX" \
  -d "image_url=IMAGE_URL" \
  -d "caption=Optional Caption"</pre>

                    <h4>Send Link Preview</h4>
                    <pre class="emwa-code">curl -X POST {{ $baseUrl }}/api/send-link-preview.php \
  -d "email=youremail@gmail.com" \
  -d "api_key=YOUR_API_KEY" \
  -d "phone=947XXXXXXXX" \
  -d "text=Your text" \
  -d "url=YOUR_URL" \
  -d "title=TITLE" \
  -d "description=DESCRIPTION"</pre>

                    <h4>Send Poll</h4>
                    <pre class="emwa-code">curl -X POST {{ $baseUrl }}/api/send-poll.php \
  -d "email=youremail@gmail.com" \
  -d "api_key=YOUR_API_KEY" \
  -d "phone=947XXXXXXXX" \
  -d "question=Your question" \
  -d "options=Option1,Option2,Option3"</pre>

                    <h4>Send Status</h4>
                    <pre class="emwa-code">curl -X POST {{ $baseUrl }}/api/status/send-text.php \
  -d "email=youremail@gmail.com" \
  -d "api_key=YOUR_API_KEY" \
  -d "text=Your status text"</pre>

                    <h4>Delete Status</h4>
                    <pre class="emwa-code">curl -X POST {{ $baseUrl }}/api/status/delete.php \
  -d "email=youremail@gmail.com" \
  -d "api_key=YOUR_API_KEY" \
  -d "status_id=STATUS_ID"</pre>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@extends('layouts.app')
@section('title', 'WhatsApp Agents')

@section('css')
<style>
.agent-card {
    background: #fff;
    border: 1px solid #e9edef;
    border-radius: 10px;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 12px;
}
.agent-avatar {
    width: 44px; height: 44px; border-radius: 50%;
    background: #128c7e; color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 16px; flex-shrink: 0;
}
.agent-info { flex: 1; }
.agent-name { font-weight: 600; font-size: 15px; color: #111; }
.agent-sub  { font-size: 12px; color: #667781; }
.agent-badge {
    background: #d9f7e5; color: #075e54;
    font-size: 11px; font-weight: 700;
    padding: 2px 10px; border-radius: 20px;
}
.step-box {
    background: #f8f9fa; border: 1px solid #e9edef;
    border-radius: 8px; padding: 16px 20px; margin-bottom: 16px;
}
.step-num {
    width: 28px; height: 28px; border-radius: 50%;
    background: #128c7e; color: #fff;
    font-size: 13px; font-weight: 700;
    display: inline-flex; align-items: center; justify-content: center;
    margin-right: 8px; flex-shrink: 0;
}
</style>
@endsection

@section('content')
<div class="content-header">
    <h1>WhatsApp Agents <small>Manage who can handle WhatsApp chats</small></h1>
</div>
<div class="content">
    <div class="row">

        {{-- Left: Active agents --}}
        <div class="col-md-7">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Active Agents ({{ $agents->count() }})</h3>
                </div>
                <div class="box-body">
                    @forelse($agents as $agent)
                    @php $chatCount = $stats[$agent->id] ?? 0; @endphp
                    <div class="agent-card">
                        <div class="agent-avatar">{{ strtoupper(substr($agent->first_name ?: $agent->username, 0, 1)) }}</div>
                        <div class="agent-info">
                            <div class="agent-name">
                                {{ trim(($agent->first_name ?? '') . ' ' . ($agent->last_name ?? '')) ?: $agent->username }}
                            </div>
                            <div class="agent-sub">{{ $agent->email ?? $agent->username }}</div>
                        </div>
                        <span class="agent-badge">{{ $chatCount }} active chat{{ $chatCount !== 1 ? 's' : '' }}</span>
                    </div>
                    @empty
                    <div class="text-center text-muted" style="padding:40px 20px;">
                        <svg width="48" height="48" fill="none" stroke="#ccc" stroke-width="1.5" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        <p style="margin-top:12px;">No agents yet. Follow the setup guide →</p>
                    </div>
                    @endforelse
                </div>
            </div>

            {{-- Current assignments --}}
            @if($assignments->count())
            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">Open Chat Assignments ({{ $assignments->count() }})</h3>
                </div>
                <div class="box-body" style="padding:0;">
                    <table class="table table-bordered" style="margin:0;">
                        <thead><tr><th>Customer</th><th>Assigned To</th><th>Since</th></tr></thead>
                        <tbody>
                        @foreach($assignments as $a)
                        <tr>
                            <td>+{{ $a->phone_number }}</td>
                            <td>
                                @if($a->agent)
                                    {{ trim(($a->agent->first_name ?? '') . ' ' . ($a->agent->last_name ?? '')) ?: $a->agent->username }}
                                @else
                                    <span class="text-muted">Unassigned</span>
                                @endif
                            </td>
                            <td>{{ $a->created_at->diffForHumans() }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>

        {{-- Right: Setup guide --}}
        <div class="col-md-5">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">How to add an agent</h3>
                </div>
                <div class="box-body">
                    <div class="step-box">
                        <span class="step-num">1</span>
                        <strong>Create or find the user</strong>
                        <p style="margin:6px 0 0 36px;font-size:13px;color:#555;">
                            Go to <a href="{{ action([\App\Http\Controllers\ManageUserController::class, 'index']) }}">User Management → Users</a>
                            and create a new user for the agent, or use an existing one.
                        </p>
                    </div>
                    <div class="step-box">
                        <span class="step-num">2</span>
                        <strong>Assign the "WhatsApp Agent" permission</strong>
                        <p style="margin:6px 0 0 36px;font-size:13px;color:#555;">
                            Go to <a href="{{ action([\App\Http\Controllers\RoleController::class, 'index']) }}">User Management → Roles</a>,
                            create or edit a role, and tick the <code>whatsapp.agent</code> permission.
                            Then assign that role to the agent user.
                        </p>
                        <p style="margin:4px 0 0 36px;font-size:12px;color:#888;">
                            If the permission is not listed yet, run:<br>
                            <code>php artisan db:seed --class=WhatsappAgentPermissionSeeder</code>
                        </p>
                    </div>
                    <div class="step-box">
                        <span class="step-num">3</span>
                        <strong>Agent logs in and accesses WhatsApp Inbox</strong>
                        <p style="margin:6px 0 0 36px;font-size:13px;color:#555;">
                            The agent will see the WhatsApp → Inbox menu item.
                            They can <strong>Claim</strong> unassigned chats or be assigned by an admin.
                            They only see chats assigned to them or unassigned chats.
                        </p>
                    </div>
                    <div class="step-box">
                        <span class="step-num">4</span>
                        <strong>Admin manages assignments from the Inbox</strong>
                        <p style="margin:6px 0 0 36px;font-size:13px;color:#555;">
                            In the WhatsApp Inbox, open any chat. The
                            <strong>assignment panel</strong> (person icon) lets you assign or transfer the chat
                            to any agent in real time.
                        </p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

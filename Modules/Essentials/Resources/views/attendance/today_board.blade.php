<div id="today_location_rule" data-require="{{ $can_manage ? 0 : 1 }}"></div>
<div class="row" style="margin-bottom:12px;">
    <div class="col-md-3">
        <div style="background:#ecfdf5;border:1px solid #a7f3d0;border-radius:10px;padding:12px 14px;">
            <div style="font-size:12px;font-weight:700;color:#047857;">Present</div>
            <div style="font-size:28px;font-weight:800;color:#065f46;">{{ $board['present'] }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:12px 14px;">
            <div style="font-size:12px;font-weight:700;color:#b91c1c;">Absent</div>
            <div style="font-size:28px;font-weight:800;color:#991b1b;">{{ $board['absent'] }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:12px 14px;">
            <div style="font-size:12px;font-weight:700;color:#b45309;">Late</div>
            <div style="font-size:28px;font-weight:800;color:#92400e;">{{ $board['late'] }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:12px 14px;">
            <div style="font-size:12px;font-weight:700;color:#1d4ed8;">Still working</div>
            <div style="font-size:28px;font-weight:800;color:#1e3a8a;">{{ $board['still_in'] }}</div>
        </div>
    </div>
</div>
<div class="table-responsive">
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Employee</th>
                <th>Shift</th>
                <th>Arrived</th>
                <th>Working time</th>
                <th>Left</th>
                <th>Late</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($board['rows'] as $row)
                <tr>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['shift'] }}</td>
                    <td>{{ $row['clock_in_label'] }}</td>
                    <td>
                        @if($row['status'] === 'in')
                            <strong class="today-timer" data-started="{{ $row['clock_in'] }}">00:00:00</strong>
                        @elseif($row['status'] === 'out')
                            <span class="today-timer" data-started="{{ $row['clock_in'] }}" data-ended="{{ $row['clock_out'] }}"></span>
                        @endif
                    </td>
                    <td>{{ $row['clock_out_label'] }}</td>
                    <td>
                        @if($row['late_minutes'] > 0)
                            <span class="label label-warning">{{ $row['late_minutes'] }} min</span>
                        @endif
                    </td>
                    <td>
                        @if($row['status'] === 'absent')
                            <button type="button" class="btn btn-success btn-sm today-arrive" data-user="{{ $row['user_id'] }}">Attend mark</button>
                        @elseif($row['status'] === 'in')
                            <button type="button" class="btn btn-warning btn-sm today-out" data-user="{{ $row['user_id'] }}">Out</button>
                        @else
                            <span class="text-muted">Done</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@if($can_manage)
    <button type="button" class="btn btn-primary" id="end_day_btn">End day</button>
    <p class="help-block">Attend mark saves the current time and starts working time. Out stops that person. End day closes everyone still in and shows today's present and absent. Marking someone else does not check location.</p>
@else
    <p class="help-block">You can mark attendance only within 100m of the office. Allow location on your phone.</p>
@endif

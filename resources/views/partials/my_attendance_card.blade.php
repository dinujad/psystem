@if(!empty($myWork))
<div class="tw-px-5 tw-pt-4" id="home_my_work" style="margin-bottom:16px;"
    data-status="{{ $myWork['status'] }}"
    data-clock-in="{{ $myWork['clock_in'] }}"
    data-clock-out="{{ $myWork['clock_out'] }}"
    data-week="{{ $myWork['week_closed_seconds'] }}"
    data-month="{{ $myWork['month_closed_seconds'] }}"
    data-require="{{ $myWork['require_location'] }}"
    data-user="{{ auth()->user()->id }}">
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:16px;">
        <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:center;justify-content:space-between;">
            <div>
                <div style="font-size:13px;font-weight:800;color:#6b7280;">Today my working time</div>
                <div id="home_today_timer" style="font-size:32px;font-weight:800;color:#1e1b4b;letter-spacing:0.5px;">00:00:00</div>
                <div id="home_arrived_label" style="font-size:12px;color:#6b7280;">
                    @if($myWork['status'] === 'absent')
                        Not marked yet
                    @else
                        Marked at {{ $myWork['arrived_label'] }}
                    @endif
                </div>
            </div>
            <div>
                @if($myWork['status'] === 'absent')
                    <button type="button" class="btn btn-success" id="home_attend_btn">Attend mark</button>
                @elseif($myWork['status'] === 'in')
                    <button type="button" class="btn btn-warning" id="home_out_btn">Out</button>
                @else
                    <span class="label label-success">Done for today</span>
                @endif
            </div>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:14px;">
            <span style="background:#eff6ff;color:#1d4ed8;font-weight:800;border-radius:999px;padding:6px 12px;font-size:13px;">This week <span id="home_week_time">0h 0m</span></span>
            <span style="background:#f5f3ff;color:#6d28d9;font-weight:800;border-radius:999px;padding:6px 12px;font-size:13px;">This month <span id="home_month_time">0h 0m</span></span>
            <span style="background:#fef2f2;color:#b91c1c;font-weight:800;border-radius:999px;padding:6px 12px;font-size:13px;">Leaves {{ $myWork['leaves'] }} days</span>
        </div>
    </div>
</div>
@endif

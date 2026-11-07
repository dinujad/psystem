<div class="table-responsive">
    <table class="table table-bordered table-striped" id="ot_board_table" style="width:100%;">
        <thead>
            <tr>
                <th>Name</th>
                <th>OT before shift start<br><small class="text-muted">(e.g. before 9:00)</small></th>
                <th>Other OT<br><small class="text-muted">(after shift end / OT rule)</small></th>
                <th>Total OT</th>
                <th>OT amount</th>
                <th>Days</th>
            </tr>
        </thead>
        <tbody>
            @forelse($board['rows'] as $row)
                <tr>
                    <td>
                        <strong>{{ $row['name'] }}</strong>
                        <button type="button" class="btn btn-xs btn-default ot-day-toggle" data-target="#ot_days_{{ $row['user_id'] }}" style="margin-left:6px;">
                            Details
                        </button>
                    </td>
                    <td>
                        {{ $row['early_hours'] }} h
                        <span class="text-muted">({{ $row['early_days'] }} day{{ $row['early_days'] == 1 ? '' : 's' }})</span>
                    </td>
                    <td>
                        {{ $row['after_hours'] }} h
                        <span class="text-muted">({{ $row['after_days'] }} day{{ $row['after_days'] == 1 ? '' : 's' }})</span>
                    </td>
                    <td><strong>{{ $row['total_hours'] }} h</strong></td>
                    <td>{{ $row['amount_label'] }}</td>
                    <td>{{ count($row['days']) }}</td>
                </tr>
                <tr id="ot_days_{{ $row['user_id'] }}" class="ot-day-detail" style="display:none;background:#fafafa;">
                    <td colspan="6" style="padding:12px;">
                        <table class="table table-condensed" style="margin:0;">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>In</th>
                                    <th>Out</th>
                                    <th>Shift start</th>
                                    <th>Before 9 / early OT</th>
                                    <th>Other OT</th>
                                    <th>Day total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($row['days'] as $day)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($day['date'])->format('d M Y') }}</td>
                                        <td>{{ $day['clock_in'] }}</td>
                                        <td>{{ $day['clock_out'] }}</td>
                                        <td>{{ $day['shift_start'] }}</td>
                                        <td>{{ $day['early_hours'] }} h</td>
                                        <td>{{ $day['after_hours'] }} h</td>
                                        <td><strong>{{ $day['total_hours'] }} h</strong></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-muted">No overtime in this date range.</td>
                </tr>
            @endforelse
        </tbody>
        @if(!empty($board['rows']))
            <tfoot>
                <tr style="background:#f3f4f6;font-weight:700;">
                    <td>Total (all staff)</td>
                    <td>{{ $board['totals']['early_hours'] }} h</td>
                    <td>{{ $board['totals']['after_hours'] }} h</td>
                    <td>{{ $board['totals']['total_hours'] }} h</td>
                    <td>{{ $board['totals']['amount_label'] }}</td>
                    <td></td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>
<p class="text-muted" style="margin-top:8px;font-size:12px;">
    Period: {{ \Carbon\Carbon::parse($start)->format('d M Y') }} — {{ \Carbon\Carbon::parse($end)->format('d M Y') }}.
    Early OT = time worked before shift start (weekday/Saturday usually 09:00). Other OT = time after shift end at that shift’s OT rate.
</p>

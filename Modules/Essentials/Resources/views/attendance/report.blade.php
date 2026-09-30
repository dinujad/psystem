@extends('layouts.app')
@section('title', 'Attendance Report')

@section('content')
@include('essentials::layouts.nav_hrm')
<section class="content-header">
    <h1>Attendance Report</h1>
</section>
<section class="content">
    <div class="box box-solid">
        <div class="box-body">
            {!! Form::open(['url' => route('hrm.attendance.report'), 'method' => 'get']) !!}
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('preset', 'Period') !!}
                        {!! Form::select('preset', [
                            'today' => 'Today',
                            'yesterday' => 'Yesterday',
                            'week' => 'This week',
                            'month' => 'This month',
                            'year' => 'This year',
                            'range' => 'Date range',
                        ], $preset, ['class' => 'form-control', 'id' => 'attendance_preset']) !!}
                    </div>
                </div>
                <div class="col-md-2 attendance-range @if($preset !== 'range') hide @endif">
                    <div class="form-group">
                        {!! Form::label('start_date', 'From') !!}
                        {!! Form::input('date', 'start_date', $start_date, ['class' => 'form-control']) !!}
                    </div>
                </div>
                <div class="col-md-2 attendance-range @if($preset !== 'range') hide @endif">
                    <div class="form-group">
                        {!! Form::label('end_date', 'To') !!}
                        {!! Form::input('date', 'end_date', $end_date, ['class' => 'form-control']) !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('employee_id', 'Employee') !!}
                        {!! Form::select('employee_id', $employees, $employee_id, ['class' => 'form-control select2', 'placeholder' => 'All employees', 'style' => 'width:100%']) !!}
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-block">Show</button>
                    </div>
                </div>
            </div>
            {!! Form::close() !!}

            <div class="row" style="margin-bottom:12px;">
                <div class="col-sm-4"><strong>Present:</strong> {{ $report['present'] }}</div>
                <div class="col-sm-4"><strong>Absent:</strong> {{ $report['absent'] }}</div>
                <div class="col-sm-4"><strong>Late:</strong> {{ $report['late'] }}</div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Employee</th>
                            <th>Shift</th>
                            <th>Arrived</th>
                            <th>Left</th>
                            <th>Worked</th>
                            <th>Late by</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($report['rows'] as $row)
                            <tr>
                                <td>{{ $row['date'] }}</td>
                                <td>{{ $row['employee'] }}</td>
                                <td>{{ $row['shift'] }}</td>
                                <td>{{ $row['in'] }}</td>
                                <td>{{ $row['out'] }}</td>
                                <td>{{ $row['worked'] }}</td>
                                <td>{{ $row['late'] }}</td>
                                <td>{{ $row['status'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center">No rows for this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script type="text/javascript">
    $('#attendance_preset').on('change', function () {
        if ($(this).val() === 'range') {
            $('.attendance-range').removeClass('hide');
        } else {
            $('.attendance-range').addClass('hide');
        }
    });
</script>
@endsection

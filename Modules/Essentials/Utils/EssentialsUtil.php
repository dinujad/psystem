<?php

namespace Modules\Essentials\Utils;

use App\Transaction;
use App\User;
use App\Utils\Util;
use DB;
use Illuminate\Support\Facades\View;
use Modules\Essentials\Entities\EssentialsAllowanceAndDeduction;
use Modules\Essentials\Entities\EssentialsAttendance;
use Modules\Essentials\Entities\EssentialsLeave;
use Modules\Essentials\Entities\EssentialsUserShift;
use Modules\Essentials\Entities\Shift;

class EssentialsUtil extends Util
{
    /**
     * Function to calculate total work duration of a user for a period of time
     *
     * @param  string  $unit
     * @param  int  $user_id
     * @param  int  $business_id
     * @param  int  $start_date = null
     * @param  int  $end_date = null
     */
    public function getTotalWorkDuration(
        $unit,
        $user_id,
        $business_id,
        $start_date = null,
        $end_date = null
    ) {
        $total_work_duration = 0;
        if ($unit == 'hour') {
            $query = EssentialsAttendance::where('business_id', $business_id)
                                        ->where('user_id', $user_id)
                                        ->whereNotNull('clock_out_time');

            if (! empty($start_date) && ! empty($end_date)) {
                $query->whereDate('clock_in_time', '>=', $start_date)
                            ->whereDate('clock_in_time', '<=', $end_date);
            }

            $minutes_sum = $query->select(DB::raw('SUM(TIMESTAMPDIFF(MINUTE, clock_in_time, clock_out_time)) as total_minutes'))->first();
            $total_work_duration = ! empty($minutes_sum->total_minutes) ? $minutes_sum->total_minutes / 60 : 0;
        }

        return number_format($total_work_duration, 2);
    }

    /**
     * Overtime after a fixed shift's end time, priced at each shift's hourly OT rate.
     * Flexible shifts and shifts with no OT rate contribute nothing.
     *
     * @param  int  $user_id
     * @param  int  $business_id
     * @param  string  $start_date
     * @param  string  $end_date
     * @return array{hours: string, amount: float}
     */
    public function getOvertimeForPeriod($user_id, $business_id, $start_date, $end_date, $include_open = false)
    {
        $attendances = EssentialsAttendance::with('shift')
            ->where('business_id', $business_id)
            ->where('user_id', $user_id)
            ->whereDate('clock_in_time', '>=', $start_date)
            ->whereDate('clock_in_time', '<=', $end_date)
            ->where(function ($query) use ($include_open) {
                $query->whereNotNull('clock_out_time');
                if ($include_open) {
                    $query->orWhereDate('clock_in_time', \Carbon::today()->toDateString());
                }
            })
            ->get();

        $assignments = EssentialsUserShift::join('essentials_shifts as s', 's.id', '=', 'essentials_user_shifts.essentials_shift_id')
            ->where('essentials_user_shifts.user_id', $user_id)
            ->where('s.business_id', $business_id)
            ->select(
                'essentials_user_shifts.start_date',
                'essentials_user_shifts.end_date',
                's.type',
                's.start_time',
                's.end_time',
                's.ot_rate_per_hour',
                's.holidays',
                's.working_days'
            )
            ->get();

        $minutes = 0;
        $amount = 0;

        foreach ($attendances as $row) {
            $clock_in = \Carbon::parse($row->clock_in_time);
            $shift = $this->shiftForDate($assignments, $clock_in) ?: $row->shift;
            if (empty($shift) || $shift->type !== 'fixed_shift' || empty($shift->end_time) || empty($shift->start_time)) {
                continue;
            }

            $rate = (float) $shift->ot_rate_per_hour;
            if ($rate <= 0) {
                continue;
            }

            $clock_out = ! empty($row->clock_out_time) ? \Carbon::parse($row->clock_out_time) : \Carbon::now();
            $shift_end = $this->shiftEndDateTime($clock_in, $shift->start_time, $shift->end_time);

            if ($clock_out->lte($shift_end)) {
                continue;
            }

            $ot_minutes = $shift_end->diffInMinutes($clock_out);
            $minutes += $ot_minutes;
            $amount += ($ot_minutes / 60) * $rate;
        }

        return [
            'hours' => number_format($minutes / 60, 2),
            'amount' => round($amount, 2),
        ];
    }

    /**
     * This month's OT for one employee, including time still running after today's shift end.
     */
    public function myOvertimeSummary($user_id, $business_id)
    {
        $today = \Carbon::today()->toDateString();
        $month_start = \Carbon::now()->startOfMonth()->toDateString();
        $month = $this->getOvertimeForPeriod($user_id, $business_id, $month_start, $today, true);
        $today_ot = $this->getOvertimeForPeriod($user_id, $business_id, $today, $today, true);

        return [
            'month_label' => \Carbon::now()->format('F Y'),
            'hours' => $month['hours'],
            'amount' => $month['amount'],
            'amount_label' => $this->num_f($month['amount'], true),
            'today_hours' => $today_ot['hours'],
            'today_amount_label' => $this->num_f($today_ot['amount'], true),
        ];
    }

    /**
     * Home card: today's running work time, week, month, and leave days.
     * The timer is based on the saved clock-in, so it continues after the browser is closed.
     */
    public function myHomeAttendance($user_id, $business_id)
    {
        $now = \Carbon::now();
        $today = $now->toDateString();
        $week_start = $now->copy()->startOfWeek()->toDateString();
        $month_start = $now->copy()->startOfMonth()->toDateString();
        $month_end = $now->copy()->endOfMonth()->toDateString();

        $today_row = EssentialsAttendance::where('business_id', $business_id)
            ->where('user_id', $user_id)
            ->whereDate('clock_in_time', $today)
            ->orderByDesc('clock_in_time')
            ->first();

        $clock_in = ! empty($today_row) ? \Carbon::parse($today_row->clock_in_time) : null;
        $clock_out = ! empty($today_row) && ! empty($today_row->clock_out_time) ? \Carbon::parse($today_row->clock_out_time) : null;
        if ($clock_in && empty($clock_out)) {
            $status = 'in';
        } elseif ($clock_in) {
            $status = 'out';
        } else {
            $status = 'absent';
        }

        return [
            'status' => $status,
            'clock_in' => $clock_in ? $clock_in->format('Y-m-d H:i:s') : '',
            'clock_out' => $clock_out ? $clock_out->format('Y-m-d H:i:s') : '',
            'arrived_label' => $clock_in ? $clock_in->format('h:i A') : '',
            'week_closed_seconds' => $this->closedWorkSeconds($business_id, $user_id, $week_start, $today),
            'month_closed_seconds' => $this->closedWorkSeconds($business_id, $user_id, $month_start, $today),
            'leaves' => $this->leaveDaysOverlapping($business_id, $user_id, $month_start, $month_end),
        ];
    }

    private function closedWorkSeconds($business_id, $user_id, $start_date, $end_date)
    {
        $sum = EssentialsAttendance::where('business_id', $business_id)
            ->where('user_id', $user_id)
            ->whereNotNull('clock_out_time')
            ->whereDate('clock_in_time', '>=', $start_date)
            ->whereDate('clock_in_time', '<=', $end_date)
            ->select(DB::raw('SUM(TIMESTAMPDIFF(SECOND, clock_in_time, clock_out_time)) as seconds'))
            ->first();

        return (int) ($sum->seconds ?? 0);
    }

    private function leaveDaysOverlapping($business_id, $user_id, $start_date, $end_date)
    {
        $range_start = \Carbon::parse($start_date)->startOfDay();
        $range_end = \Carbon::parse($end_date)->endOfDay();
        $leaves = EssentialsLeave::where('business_id', $business_id)
            ->where('user_id', $user_id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $end_date)
            ->whereDate('end_date', '>=', $start_date)
            ->get();

        $days = 0;
        foreach ($leaves as $leave) {
            $start = \Carbon::parse($leave->start_date)->startOfDay();
            $end = \Carbon::parse($leave->end_date)->startOfDay();
            if ($start->lt($range_start)) {
                $start = $range_start->copy();
            }
            if ($end->gt($range_end)) {
                $end = $range_end->copy()->startOfDay();
            }
            $days += $start->diffInDays($end) + 1;
        }

        return $days;
    }

    /**
     * Shift end on the attendance day. Overnight shifts (end earlier than start) end the next day
     * when the employee clocked in on the evening side of the shift.
     */
    private function shiftEndDateTime($clock_in, $start_time, $end_time)
    {
        $day = $clock_in->format('Y-m-d');
        $start = \Carbon::parse($day.' '.\Carbon::parse($start_time)->format('H:i:s'));
        $end = \Carbon::parse($day.' '.\Carbon::parse($end_time)->format('H:i:s'));

        if ($end->lte($start) && $clock_in->gte($end)) {
            $end->addDay();
        }

        return $end;
    }

    /**
     * Day-specific assigned shift for this date, such as a Saturday-only shift.
     * A shift with no working days is the everyday shift and is not returned here.
     */
    private function shiftForDate($assignments, $clock_in)
    {
        $day = strtolower($clock_in->format('l'));
        $date = $clock_in->format('Y-m-d');
        $matches = [];

        foreach ($assignments as $shift) {
            $start = substr((string) $shift->start_date, 0, 10);
            $end = substr((string) $shift->end_date, 0, 10);
            if ($start === '' || $start > $date) {
                continue;
            }
            if ($end !== '' && $end < $date) {
                continue;
            }
            if (! $this->shiftAppliesOnDay($shift, $day)) {
                continue;
            }
            if (count($this->dayList($shift->working_days)) === 0) {
                continue;
            }
            $matches[] = $shift;
        }

        if (empty($matches)) {
            return null;
        }

        usort($matches, function ($a, $b) {
            return $this->workingDaySpecificity($a) <=> $this->workingDaySpecificity($b);
        });

        return $matches[0];
    }

    private function shiftAppliesOnDay($shift, $day_string)
    {
        $holidays = $this->dayList($shift->holidays);
        if (in_array($day_string, $holidays, true)) {
            return false;
        }

        $working = $this->dayList($shift->working_days);
        if (count($working) > 0 && ! in_array($day_string, $working, true)) {
            return false;
        }

        return true;
    }

    private function workingDaySpecificity($shift)
    {
        $count = count($this->dayList($shift->working_days));

        return $count > 0 ? $count : 7;
    }

    private function dayList($value)
    {
        if (is_array($value)) {
            return $value;
        }
        if ($value === null || $value === '') {
            return [];
        }
        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Parses month and year from date
     *
     * @param  string  $month_year
     */
    public function getDateFromMonthYear($month_year)
    {
        $month_year_arr = explode('/', $month_year);
        $month = $month_year_arr[0];
        $year = $month_year_arr[1];

        $transaction_date = $year.'-'.$month.'-01';

        return $transaction_date;
    }

    /**
     * Retrieves all allowances and deductions of an employeee
     *
     * @param  int  $business_id
     * @param  int  $user_id
     * @param  string  $start_date = null
     * @param  string  $end_date = null
     */
    public function getEmployeeAllowancesAndDeductions($business_id, $user_id, $start_date = null, $end_date = null)
    {
        $query = EssentialsAllowanceAndDeduction::join('essentials_user_allowance_and_deductions as euad', 'euad.allowance_deduction_id', '=', 'essentials_allowances_and_deductions.id')
                ->where('business_id', $business_id)
                ->where('euad.user_id', $user_id);

        //Filter if applicable one
        if (! empty($start_date) && ! empty($end_date)) {
            $query->where(function ($q) use ($start_date, $end_date) {
                $q->whereNull('applicable_date')
                    ->orWhereBetween('applicable_date', [$start_date, $end_date]);
            });
        }
        $allowances_and_deductions = $query->get();

        return $allowances_and_deductions;
    }

    /**
     * Validates user clock in and returns available shift id
     */
    public function checkUserShift($user_id, $settings, $clock_in_time = null)
    {
        $shift_id = null;
        $shift_date = ! empty($clock_in_time) ? \Carbon::parse($clock_in_time) : \Carbon::now();
        $shift_datetime = $shift_date->format('Y-m-d');
        $day_string = strtolower($shift_date->format('l'));
        $grace_before_checkin = ! empty($settings['grace_before_checkin']) ? (int) $settings['grace_before_checkin'] : 0;
        $grace_after_checkin = ! empty($settings['grace_after_checkin']) ? (int) $settings['grace_after_checkin'] : 0;
        $clock_in_start = ! empty($clock_in_time) ? \Carbon::parse($clock_in_time)->subMinutes($grace_before_checkin) : \Carbon::now()->subMinutes($grace_before_checkin);
        $clock_in_end = ! empty($clock_in_time) ? \Carbon::parse($clock_in_time)->addMinutes($grace_after_checkin) : \Carbon::now()->addMinutes($grace_after_checkin);

        $user_shifts = EssentialsUserShift::join('essentials_shifts as s', 's.id', '=', 'essentials_user_shifts.essentials_shift_id')
                    ->where('user_id', $user_id)
                    ->where('start_date', '<=', $shift_datetime)
                    ->where(function ($q) use ($shift_datetime) {
                        $q->whereNull('end_date')
                        ->orWhere('end_date', '>=', $shift_datetime);
                    })
                    ->select('essentials_user_shifts.*', 's.holidays', 's.working_days', 's.start_time', 's.end_time', 's.type')
                    ->get();

        $matches = [];
        foreach ($user_shifts as $shift) {
            if (! $this->shiftAppliesOnDay($shift, $day_string)) {
                continue;
            }

            //Check allocated shift time
            if ((! empty($shift->start_time) && \Carbon::parse($shift->start_time)->between($clock_in_start, $clock_in_end)) || $shift->type == 'flexible_shift') {
                $matches[] = $shift;
            }
        }

        if (empty($matches)) {
            return $shift_id;
        }

        usort($matches, function ($a, $b) {
            return $this->workingDaySpecificity($a) <=> $this->workingDaySpecificity($b);
        });

        return $matches[0]->essentials_shift_id;
    }

    /**
     * Validates user clock out
     */
    public function canClockOut($clock_in, $settings, $clock_out_time = null)
    {
        $shift = Shift::find($clock_in->essentials_shift_id);
        if (empty($shift->end_time)) {
            return true;
        }

        $grace_before_checkout = ! empty($settings['grace_before_checkout']) ? (int) $settings['grace_before_checkout'] : 0;
        $grace_after_checkout = ! empty($settings['grace_after_checkout']) ? (int) $settings['grace_after_checkout'] : 0;
        $clock_out_start = empty($clock_out_time) ? \Carbon::now()->subMinutes($grace_before_checkout) : \Carbon::parse($clock_out_time)->subMinutes($grace_before_checkout);

        $clock_out_end = empty($clock_out_time) ? \Carbon::now()->addMinutes($grace_after_checkout) : \Carbon::parse($clock_out_time)->addMinutes($grace_after_checkout);

        if ((\Carbon::parse($shift->end_time)->between($clock_out_start, $clock_out_end)) || $shift->type == 'flexible_shift') {
            return true;
        } else {
            return false;
        }
    }

    public function clockin($data, $essentials_settings)
    {
        //Check user can clockin
        $clock_in_time = is_object($data['clock_in_time']) ? $data['clock_in_time']->toDateTimeString() : $data['clock_in_time'];

        $shift = $this->checkUserShift($data['user_id'], $essentials_settings, $clock_in_time);

        if (empty($shift)) {
            $available_shifts = $this->getAllAvailableShiftsForGivenUser($data['business_id'], $data['user_id']);

            $available_shifts_html = view('essentials::attendance.avail_shifts')
                                        ->with(compact('available_shifts'))
                                        ->render();

            $output = ['success' => false,
                'msg' => __('essentials::lang.shift_not_allocated'),
                'type' => 'clock_in',
                'shift_details' => $available_shifts_html,
            ];

            return $output;
        }

        $data['essentials_shift_id'] = $shift;

        //Check if already clocked in
        $count = EssentialsAttendance::where('business_id', $data['business_id'])
                                ->where('user_id', $data['user_id'])
                                ->whereNull('clock_out_time')
                                ->count();
        if ($count == 0) {
            EssentialsAttendance::create($data);

            $shift_info = Shift::getGivenShiftInfo($data['business_id'], $shift);
            $current_shift_html = view('essentials::attendance.current_shift')
                                    ->with(compact('shift_info'))
                                    ->render();

            $output = ['success' => true,
                'msg' => __('essentials::lang.clock_in_success'),
                'type' => 'clock_in',
                'current_shift' => $current_shift_html,
            ];
        } else {
            $output = ['success' => false,
                'msg' => __('essentials::lang.already_clocked_in'),
                'type' => 'clock_in',
            ];
        }

        return $output;
    }

    public function clockout($data, $essentials_settings)
    {

        //Get clock in
        $clock_in = EssentialsAttendance::where('business_id', $data['business_id'])
                                ->where('user_id', $data['user_id'])
                                ->whereNull('clock_out_time')
                                ->first();
        $clock_out_time = is_object($data['clock_out_time']) ? $data['clock_out_time']->toDateTimeString() : $data['clock_out_time'];

        if (! empty($clock_in)) {
            $can_clockout = $this->canClockOut($clock_in, $essentials_settings, $clock_out_time);
            if (! $can_clockout) {
                $output = ['success' => false,
                    'msg' => __('essentials::lang.shift_not_over'),
                    'type' => 'clock_out',
                ];

                return $output;
            }

            $clock_in->clock_out_time = $data['clock_out_time'];
            $clock_in->clock_out_note = $data['clock_out_note'];
            $clock_in->clock_out_location = $data['clock_out_location'] ?? '';
            $clock_in->save();

            $output = ['success' => true,
                'msg' => __('essentials::lang.clock_out_success'),
                'type' => 'clock_out',
            ];
        } else {
            $output = ['success' => false,
                'msg' => __('essentials::lang.not_clocked_in'),
                'type' => 'clock_out',
            ];
        }

        return $output;
    }

    public function getAllAvailableShiftsForGivenUser($business_id, $user_id)
    {
        $available_user_shifts = EssentialsUserShift::join('essentials_shifts as s', 's.id', '=',
                                    'essentials_user_shifts.essentials_shift_id')
                                    ->where('user_id', $user_id)
                                    ->where('s.business_id', $business_id)
                                    ->whereDate('start_date', '<=', \Carbon::today())
                                    ->whereDate('end_date', '>=', \Carbon::today())
                                    ->select('essentials_user_shifts.start_date', 'essentials_user_shifts.end_date',
                                        's.name', 's.type', 's.start_time', 's.end_time', 's.holidays')
                                    ->get();

        return $available_user_shifts;
    }

    /**
     * get total leaves of and employee for given date
     *
     * @param  int  $business_id
     * @param  int  $employee_id
     * @param  string  $start_date
     * @param  string  $end_date
     */
    public function getTotalLeavesForGivenDateOfAnEmployee($business_id, $employee_id, $start_date, $end_date)
    {
        $leaves = EssentialsLeave::where('business_id', $business_id)
                        ->where('user_id', $employee_id)
                        ->whereDate('start_date', '>=', $start_date)
                        ->whereDate('end_date', '<=', $end_date)
                        ->get();

        $total_leaves = 0;
        foreach ($leaves as $key => $leave) {
            $start_date = \Carbon::parse($leave->start_date);
            $end_date = \Carbon::parse($leave->end_date);

            $diff = $start_date->diffInDays($end_date);
            $diff += 1;
            $total_leaves += $diff;
        }

        return $total_leaves;
    }

    public function getTotalDaysWorkedForGivenDateOfAnEmployee($business_id, $employee_id, $start_date, $end_date)
    {
        $attendances = EssentialsAttendance::where('business_id', $business_id)
                        ->where('user_id', $employee_id)
                        ->whereNotNull('clock_out_time')
                        ->whereDate('clock_in_time', '>=', $start_date)
                        ->whereDate('clock_in_time', '<=', $end_date)
                        ->get()
                        ->groupBy(function ($attendance, $key) {
                            return \Carbon::parse($attendance->clock_in_time)->format('Y-m-d');
                        });

        return count($attendances);
    }

    public function getPayrollQuery($business_id)
    {
        $payrolls = Transaction::where('transactions.business_id', $business_id)
                    ->where('type', 'payroll')
                    ->join('users as u', 'u.id', '=', 'transactions.expense_for')
                    ->leftJoin('categories as dept', 'u.essentials_department_id', '=', 'dept.id')
                    ->leftJoin('categories as dsgn', 'u.essentials_designation_id', '=', 'dsgn.id')
                    ->leftJoin('essentials_payroll_group_transactions as epgt', 'transactions.id', '=', 'epgt.transaction_id')
                    ->leftJoin('essentials_payroll_groups as epg', 'epgt.payroll_group_id', '=', 'epg.id')
                    ->select([
                        'transactions.id',
                        DB::raw("CONCAT(COALESCE(u.surname, ''), ' ', COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as user"),
                        'final_total',
                        'transaction_date',
                        'ref_no',
                        'transactions.payment_status',
                        'dept.name as department',
                        'dsgn.name as designation',
                        'epgt.payroll_group_id',
                    ]);

        return $payrolls;
    }

    public function getEssentialsSettings()
    {
        $settings = request()->session()->get('business.essentials_settings');
        $settings = ! empty($settings) ? json_decode($settings, true) : [];

        return $settings;
    }

    /**
     * Today's attendance board: present, absent, late, and a live row per employee.
     */
    public function todayAttendanceBoard($business_id, $only_user_id = null)
    {
        $now = \Carbon::now();
        $date = $now->toDateString();
        $users = User::where('business_id', $business_id)
            ->where('user_type', 'user')
            ->where('is_cmmsn_agnt', 0)
            ->when($only_user_id, function ($query) use ($only_user_id) {
                $query->where('id', $only_user_id);
            })
            ->orderBy('first_name')
            ->get();

        $attendance = EssentialsAttendance::with('shift')
            ->where('business_id', $business_id)
            ->whereDate('clock_in_time', $date)
            ->get()
            ->keyBy('user_id');

        $rows = [];
        $present = 0;
        $absent = 0;
        $late = 0;
        $still_in = 0;

        foreach ($users as $user) {
            $row = $attendance->get($user->id);
            $shift = ! empty($row) && ! empty($row->shift) ? $row->shift : $this->resolveShiftForUser($user->id, $now);
            $expected = ! empty($shift);
            $clock_in = ! empty($row) ? \Carbon::parse($row->clock_in_time) : null;
            $clock_out = ! empty($row) && ! empty($row->clock_out_time) ? \Carbon::parse($row->clock_out_time) : null;
            $late_minutes = 0;
            if ($clock_in && ! empty($shift) && ! empty($shift->start_time)) {
                $shift_start = \Carbon::parse($date.' '.\Carbon::parse($shift->start_time)->format('H:i:s'));
                if ($clock_in->gt($shift_start)) {
                    $late_minutes = $shift_start->diffInMinutes($clock_in);
                }
            }

            if ($clock_in) {
                $present++;
                if ($late_minutes > 0) {
                    $late++;
                }
                if (empty($clock_out)) {
                    $still_in++;
                    $status = 'in';
                } else {
                    $status = 'out';
                }
            } else {
                if ($expected) {
                    $absent++;
                }
                $status = 'absent';
            }

            $rows[] = [
                'user_id' => $user->id,
                'name' => trim($user->user_full_name ?: ($user->first_name.' '.$user->last_name)),
                'shift' => ! empty($shift) ? $shift->name : '',
                'status' => $status,
                'expected' => $expected,
                'clock_in' => $clock_in ? $clock_in->format('Y-m-d H:i:s') : '',
                'clock_out' => $clock_out ? $clock_out->format('Y-m-d H:i:s') : '',
                'clock_in_label' => $clock_in ? $clock_in->format('h:i A') : '',
                'clock_out_label' => $clock_out ? $clock_out->format('h:i A') : '',
                'late_minutes' => $late_minutes,
                'attendance_id' => ! empty($row) ? $row->id : null,
            ];
        }

        return [
            'date_label' => $now->format('l, d M Y'),
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'still_in' => $still_in,
            'rows' => $rows,
        ];
    }

    public function markArrivalNow($business_id, $user_id, $location = null)
    {
        $now = \Carbon::now();
        $already = EssentialsAttendance::where('business_id', $business_id)
            ->where('user_id', $user_id)
            ->whereDate('clock_in_time', $now->toDateString())
            ->first();
        if ($already) {
            return ['success' => false, 'msg' => 'Already marked for today.'];
        }

        $shift = $this->resolveShiftForUser($user_id, $now);
        EssentialsAttendance::create([
            'business_id' => $business_id,
            'user_id' => $user_id,
            'clock_in_time' => $now->toDateTimeString(),
            'essentials_shift_id' => ! empty($shift) ? $shift->shift_id : null,
            'ip_address' => request()->ip(),
            'clock_in_note' => 'Attend mark',
            'clock_in_location' => $location,
        ]);

        return ['success' => true, 'msg' => 'Attendance marked at '.$now->format('h:i A')];
    }

    public function markOutNow($business_id, $user_id, $location = null)
    {
        $open = EssentialsAttendance::where('business_id', $business_id)
            ->where('user_id', $user_id)
            ->whereNull('clock_out_time')
            ->orderByDesc('clock_in_time')
            ->first();
        if (empty($open)) {
            return ['success' => false, 'msg' => 'Not marked in.'];
        }

        $now = \Carbon::now();
        $open->clock_out_time = $now->toDateTimeString();
        $open->clock_out_note = 'Out';
        if (! empty($location)) {
            $open->clock_out_location = $location;
        }
        $open->save();

        return ['success' => true, 'msg' => 'Out at '.$now->format('h:i A')];
    }

    public function endToday($business_id)
    {
        $now = \Carbon::now();
        $open = EssentialsAttendance::where('business_id', $business_id)
            ->whereDate('clock_in_time', $now->toDateString())
            ->whereNull('clock_out_time')
            ->get();
        foreach ($open as $row) {
            $row->clock_out_time = $now->toDateTimeString();
            $row->clock_out_note = 'End day';
            $row->save();
        }

        $board = $this->todayAttendanceBoard($business_id);

        return [
            'success' => true,
            'msg' => 'Day finished.',
            'present' => $board['present'],
            'absent' => $board['absent'],
            'late' => $board['late'],
            'closed' => $open->count(),
            'rows' => $board['rows'],
        ];
    }

    /**
     * Shift that applies to this user on this date. Day-specific shifts win over everyday shifts.
     */
    public function resolveShiftForUser($user_id, $moment)
    {
        $day = strtolower($moment->format('l'));
        $date = $moment->format('Y-m-d');
        $assignments = EssentialsUserShift::join('essentials_shifts as s', 's.id', '=', 'essentials_user_shifts.essentials_shift_id')
            ->where('essentials_user_shifts.user_id', $user_id)
            ->where('start_date', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $date);
            })
            ->select(
                's.id as shift_id',
                's.name',
                's.type',
                's.start_time',
                's.end_time',
                's.holidays',
                's.working_days'
            )
            ->get();

        $matches = [];
        foreach ($assignments as $shift) {
            if ($this->shiftAppliesOnDay($shift, $day)) {
                $matches[] = $shift;
            }
        }
        if (empty($matches)) {
            return null;
        }
        usort($matches, function ($a, $b) {
            return $this->workingDaySpecificity($a) <=> $this->workingDaySpecificity($b);
        });

        return $matches[0];
    }

    public function attendanceReportRows($business_id, $start_date, $end_date, $employee_id = null)
    {
        $start = \Carbon::parse($start_date)->startOfDay();
        $end = \Carbon::parse($end_date)->endOfDay();
        $users = User::where('business_id', $business_id)
            ->where('user_type', 'user')
            ->where('is_cmmsn_agnt', 0)
            ->when($employee_id, function ($query) use ($employee_id) {
                $query->where('id', $employee_id);
            })
            ->orderBy('first_name')
            ->get();

        $attendance = EssentialsAttendance::with('shift')
            ->where('business_id', $business_id)
            ->whereDate('clock_in_time', '>=', $start->toDateString())
            ->whereDate('clock_in_time', '<=', $end->toDateString())
            ->when($employee_id, function ($query) use ($employee_id) {
                $query->where('user_id', $employee_id);
            })
            ->orderBy('clock_in_time')
            ->get()
            ->groupBy(function ($row) {
                return \Carbon::parse($row->clock_in_time)->toDateString().'-'.$row->user_id;
            });

        $rows = [];
        $present = 0;
        $absent = 0;
        $late = 0;
        $include_absent = $start->diffInDays($end) <= 31;
        $shiftCache = [];

        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $date = $day->toDateString();
            $weekday = $day->format('l');
            foreach ($users as $user) {
                $key = $date.'-'.$user->id;
                $record = $attendance->get($key);
                $record = $record ? $record->first() : null;
                $cacheKey = $user->id.'-'.$weekday;
                if (! array_key_exists($cacheKey, $shiftCache)) {
                    $shiftCache[$cacheKey] = $this->resolveShiftForUser($user->id, $day->copy());
                }
                $shift = ! empty($record) && ! empty($record->shift) ? $record->shift : $shiftCache[$cacheKey];
                $expected = ! empty($shift);
                if (empty($record)) {
                    if ($include_absent && $expected) {
                        $absent++;
                        $rows[] = $this->reportRow($user, $date, $shift, null, null, 0, 'Absent');
                    } elseif ($expected) {
                        $absent++;
                    }

                    continue;
                }

                $clock_in = \Carbon::parse($record->clock_in_time);
                $clock_out = ! empty($record->clock_out_time) ? \Carbon::parse($record->clock_out_time) : null;
                $late_minutes = 0;
                if (! empty($shift) && ! empty($shift->start_time)) {
                    $shift_start = \Carbon::parse($date.' '.\Carbon::parse($shift->start_time)->format('H:i:s'));
                    if ($clock_in->gt($shift_start)) {
                        $late_minutes = $shift_start->diffInMinutes($clock_in);
                    }
                }
                $present++;
                if ($late_minutes > 0) {
                    $late++;
                }
                $rows[] = $this->reportRow($user, $date, $shift, $clock_in, $clock_out, $late_minutes, empty($clock_out) ? 'In' : 'Out');
            }
        }

        return [
            'rows' => $rows,
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
        ];
    }

    private function reportRow($user, $date, $shift, $clock_in, $clock_out, $late_minutes, $status)
    {
        $worked = '';
        if ($clock_in && $clock_out) {
            $minutes = $clock_in->diffInMinutes($clock_out);
            $worked = intdiv($minutes, 60).'h '.($minutes % 60).'m';
        } elseif ($clock_in) {
            $worked = 'Running';
        }

        return [
            'date' => \Carbon::parse($date)->format('d M Y'),
            'employee' => trim($user->user_full_name ?: ($user->first_name.' '.$user->last_name)),
            'shift' => ! empty($shift) ? $shift->name : '',
            'in' => $clock_in ? $clock_in->format('h:i A') : '',
            'out' => $clock_out ? $clock_out->format('h:i A') : '',
            'worked' => $worked,
            'late' => $late_minutes > 0 ? $late_minutes.' min' : '',
            'status' => $status,
        ];
    }
}

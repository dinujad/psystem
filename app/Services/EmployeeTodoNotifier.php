<?php

namespace App\Services;

use App\EmployeeWeeklyPlan;
use App\EmployeeWeeklyPlanItem;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class EmployeeTodoNotifier
{
    public function __construct(private WhatsappService $whatsapp) {}

    public function notifyNewTask(User $employee, EmployeeWeeklyPlanItem $item, Carbon $weekStart): array
    {
        $phone = $this->resolvePhone($employee);
        if (! $phone) {
            return ['success' => false, 'message' => 'No WhatsApp number for employee.'];
        }

        $item->loadMissing('category');
        $weekEnd = $weekStart->copy()->addDays(6);
        $day     = EmployeeWeeklyPlan::dayLabels()[$item->day_of_week] ?? 'Day '.$item->day_of_week;
        $name    = EmployeeWeeklyPlan::userDisplayName($employee);
        $url     = url(route('employee-todos.my-week', ['week' => $weekStart->toDateString()], false));

        $lines = [
            '📋 *Weekly To-Do — New Task*',
            '',
            'Hello '.$name.',',
            '',
            'A new task was assigned for *'.$day.'*:',
            '',
            '*'.($item->category?->name ?? 'General').':* '.$item->title,
        ];

        if ($item->task_time) {
            $lines[] = 'Time: '.substr((string) $item->task_time, 0, 5);
        }

        $lines[] = '';
        $lines[] = 'Week: '.$weekStart->format('d M').' – '.$weekEnd->format('d M Y');
        $lines[] = '';
        $lines[] = 'View your week:';
        $lines[] = $url;

        return $this->send($phone, implode("\n", $lines), $employee->id);
    }

    public function notifyTemplateAssigned(User $employee, Carbon $weekStart, int $taskCount, ?string $templateName = null): array
    {
        $phone = $this->resolvePhone($employee);
        if (! $phone) {
            return ['success' => false, 'message' => 'No WhatsApp number for employee.'];
        }

        $weekEnd = $weekStart->copy()->addDays(6);
        $name    = EmployeeWeeklyPlan::userDisplayName($employee);
        $url     = url(route('employee-todos.my-week', ['week' => $weekStart->toDateString()], false));

        $lines = [
            '📋 *Weekly Plan Assigned*',
            '',
            'Hello '.$name.',',
            '',
            'Your manager assigned *'.$taskCount.' task'.($taskCount === 1 ? '' : 's').'* for the week of '
            .$weekStart->format('d M').' – '.$weekEnd->format('d M Y').'.',
        ];

        if ($templateName) {
            $lines[] = 'Template: *'.$templateName.'*';
        }

        $lines[] = '';
        $lines[] = 'Open your planner:';
        $lines[] = $url;

        return $this->send($phone, implode("\n", $lines), $employee->id);
    }

    public function resolvePhone(User $user): ?string
    {
        $raw = trim((string) ($user->whatsapp_number ?: $user->contact_number ?: ''));
        if ($raw === '') {
            return null;
        }

        $phone = ProductionNotifier::normalizePhone($raw);

        return strlen($phone) >= 9 ? $phone : null;
    }

    private function send(string $phone, string $message, int $userId): array
    {
        $result = $this->whatsapp->sendMessage($phone, $message);

        if (empty($result['success'])) {
            Log::warning("EmployeeTodoNotifier: failed to notify user {$userId} at {$phone}");
        }

        return $result;
    }
}

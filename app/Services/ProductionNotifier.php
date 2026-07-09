<?php

namespace App\Services;

use App\ProductionJob;
use App\ProductionStageEmployee;
use App\User;
use App\Utils\Util;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProductionNotifier
{
    public function __construct(private WhatsappService $whatsapp) {}

    public static function normalizePhone(string $number): string
    {
        $digits = preg_replace('/\D/', '', $number);

        if (strlen($digits) === 10 && str_starts_with($digits, '0')) {
            $digits = '94' . substr($digits, 1);
        }

        return $digits;
    }

    /**
     * Enable login and return fresh plaintext credentials for WhatsApp delivery.
     */
    public function issueLoginCredentials(User $user, int $businessId): array
    {
        $util = app(Util::class);
        $plainPassword = Str::upper(Str::random(4)) . Str::lower(Str::random(2)) . random_int(10, 99);

        $updates = [
            'allow_login' => 1,
            'password'    => Hash::make($plainPassword),
        ];

        if (empty($user->username)) {
            $refCount = $util->setAndGetReferenceCount('username', $businessId);
            $username = $util->generateReferenceNumber('username', $refCount, $businessId);
            $ext      = $util->getUsernameExtension();
            if (! empty($ext)) {
                $username .= $ext;
            }
            $updates['username'] = $username;
        }

        $user->update($updates);
        $user->refresh();

        return [
            'username' => $user->username,
            'password' => $plainPassword,
        ];
    }

    public function notifyTeamMemberCredentials(
        ProductionStageEmployee $assignment,
        string $stage,
        array $credentials,
        string $whatsappNumber
    ): array {
        $phone = self::normalizePhone($whatsappNumber);
        if (strlen($phone) < 9) {
            return ['success' => false, 'message' => 'Invalid WhatsApp number.'];
        }

        $assignment->loadMissing('user');
        $message = $this->buildTeamCredentialsMessage($assignment->user, $stage, $credentials);

        $result = $this->whatsapp->sendMessage($phone, $message);

        if (empty($result['success'])) {
            Log::warning("ProductionNotifier: failed to send login credentials to {$phone} for user {$assignment->user_id}");
        }

        return $result;
    }

    public function buildTeamCredentialsMessage(User $user, string $stage, array $credentials): string
    {
        $stageLabel    = ProductionJob::allStages()[$stage] ?? ucfirst($stage);
        $name          = trim(($user->surname ?? '') . ' ' . ($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
        $name          = $name ?: ($user->username ?? 'Team Member');
        $loginUrl      = url('/login');
        $dashboardUrl  = url(route('production.section', $stage, false));

        $lines = [
            '👋 *Production Team Assignment*',
            '',
            'Hello ' . $name . ',',
            '',
            'You have been added to the *' . $stageLabel . '* section.',
            '',
            '*Login Credentials:*',
            'Username: ' . ($credentials['username'] ?? ''),
            'Password: ' . ($credentials['password'] ?? ''),
            'Login URL: ' . $loginUrl,
            '',
            '*Your Section Dashboard:*',
            $dashboardUrl,
            '',
            'Sign in and open your section dashboard to view and work on jobs in your section only.',
        ];

        return implode("\n", $lines);
    }

    public function notifyStageTeam(string $stage, ProductionJob $job): void
    {
        if ($stage === 'completed') {
            return;
        }

        $job->loadMissing('assignments');
        $assignedUserIds = $job->assignments->where('stage', $stage)->pluck('user_id');

        if ($assignedUserIds->isNotEmpty()) {
            $recipients = ProductionStageEmployee::where('stage', $stage)
                ->whereIn('user_id', $assignedUserIds)
                ->whereNotNull('whatsapp_number')
                ->where('whatsapp_number', '!=', '')
                ->get();
        } else {
            $head = ProductionStageEmployee::headForStage($stage);

            if ($head) {
                $recipients = collect([$head]);
            } else {
                $recipients = ProductionStageEmployee::where('stage', $stage)
                    ->whereNotNull('whatsapp_number')
                    ->where('whatsapp_number', '!=', '')
                    ->get();
            }
        }

        if ($recipients->isEmpty()) {
            Log::info("ProductionNotifier: no section head or WhatsApp numbers for stage [{$stage}] job [{$job->job_number}]");
            return;
        }

        $message = $this->buildJobMessage($job, $stage);
        $sentTo  = [];

        foreach ($recipients as $member) {
            $phone = self::normalizePhone($member->whatsapp_number);
            if (strlen($phone) < 9 || in_array($phone, $sentTo, true)) {
                continue;
            }

            $result = $this->whatsapp->sendMessage($phone, $message);
            $sentTo[] = $phone;

            if (empty($result['success'])) {
                Log::warning("ProductionNotifier: failed to notify {$phone} for job {$job->job_number}");
            }
        }
    }

    public function buildJobMessage(ProductionJob $job, string $stage): string
    {
        $stageLabel     = ProductionJob::allStages()[$stage] ?? ucfirst($stage);
        $viewUrl        = url(route('production.show', $job, false));
        $dashboardUrl   = url(route('production.section', $stage, false));

        $lines = [
            '🖨️ *New Production Job*',
            '',
            '*Job No:* ' . $job->job_number,
            '*Customer:* ' . $job->customer_name,
            '*Title:* ' . $job->title,
            '*Section:* ' . $stageLabel,
        ];

        if ($job->customer_phone) {
            $lines[] = '*Phone:* ' . $job->customer_phone;
        }

        if ($job->priority) {
            $lines[] = '*Priority:* ' . ucfirst($job->priority);
        }

        if ($job->due_date) {
            $lines[] = '*Due Date:* ' . $job->due_date->format('Y-m-d');
        }

        if ($job->description) {
            $lines[] = '';
            $lines[] = '*Details:*';
            $lines[] = $job->description;
        }

        if ($job->google_drive_url) {
            $lines[] = '';
            $lines[] = '*Google Drive:*';
            $lines[] = $job->google_drive_url;
        }

        $lines[] = '';
        $lines[] = 'A new job has arrived in your section. Open your dashboard:';
        $lines[] = $dashboardUrl;
        $lines[] = '';
        $lines[] = 'View job details:';
        $lines[] = $viewUrl;

        return implode("\n", $lines);
    }

    public function notifyCustomerJobStarted(ProductionJob $job): array
    {
        $phone = self::normalizePhone((string) ($job->customer_phone ?? ''));
        if (strlen($phone) < 9) {
            Log::info("ProductionNotifier: no customer phone for job [{$job->job_number}]");

            return ['success' => false, 'message' => 'No customer phone number.'];
        }

        $message = $this->buildCustomerStartMessage($job);
        $result  = $this->whatsapp->sendMessage($phone, $message);

        if (empty($result['success'])) {
            Log::warning("ProductionNotifier: failed to notify customer {$phone} for job {$job->job_number}");
        }

        return $result;
    }

    public function buildCustomerStartMessage(ProductionJob $job): string
    {
        $lines = [
            '🖨️ *PrintWorks — Job Started*',
            '',
            'Hello ' . ($job->customer_name ?: 'Customer') . ',',
            '',
            'Your production job has been *started*.',
            '',
            '*Job:* ' . $job->title,
            '*Job No:* ' . $job->job_number,
        ];

        if ($job->due_date) {
            $lines[] = '*Expected by:* ' . $job->due_date->format('d M Y');
        }

        $lines[] = '';
        $lines[] = 'We will keep you updated on the progress. Thank you for choosing PrintWorks!';

        return implode("\n", $lines);
    }
}

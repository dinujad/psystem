<?php

/**
 * One-click deploy — route cache bypass කරන file.
 * Deploy අවසන් වෙලා මේ file එක server එකෙන් delete කරන්න.
 */

require dirname(__DIR__).'/vendor/autoload.php';
$app = require_once dirname(__DIR__).'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

$output = [];
$errors = [];

$run = function (string $label, string $command, array $params = []) use (&$output, &$errors) {
    try {
        Artisan::call($command, $params);
        $result = trim(Artisan::output());
        $output[] = 'OK ['.$label.']: '.($result !== '' ? $result : 'Done');
    } catch (Throwable $e) {
        $errors[] = 'ERROR ['.$label.']: '.$e->getMessage();
    }
};

$run('optimize:clear', 'optimize:clear');
$run('migrate', 'migrate', ['--force' => true]);

foreach (['Accounting', 'AssetManagement', 'Cms', 'Crm', 'Essentials', 'InventoryManagement'] as $module) {
    $path = "Modules/{$module}/Database/Migrations";
    if (is_dir(base_path($path))) {
        $run("migrate:{$module}", 'migrate', ['--path' => $path, '--force' => true]);
    }
}

$run('db:seed WalkInCustomer', 'db:seed', [
    '--class' => 'Database\\Seeders\\WalkInCustomerSeeder',
    '--force' => true,
]);

try {
    $layoutCount = DB::table('invoice_layouts')->update(['design' => 'thermal']);
    $output[] = 'OK [invoice_layouts]: design set to thermal ('.$layoutCount.' row(s))';
} catch (Throwable $e) {
    $errors[] = 'ERROR [invoice_layouts]: '.$e->getMessage();
}

$run('storage:link', 'storage:link');
$run('config:cache', 'config:cache');
$run('view:cache', 'view:cache');

$response = "=== MILLENNIUM AGRO DEPLOY ===\n";
$response .= 'URL: '.config('app.url')."\n\n";
$response .= implode("\n", $output);

if (! empty($errors)) {
    $response .= "\n\n=== ERRORS ===\n".implode("\n", $errors);
} else {
    $response .= "\n\nAll steps completed.";
    $response .= "\nLogin: ".url('/login');
    $response .= "\n\nDelete public/millennium-deploy.php from server after deploy.";
}

header('Content-Type: text/plain; charset=UTF-8');
echo $response;

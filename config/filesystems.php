<?php

$useS3 = env('FILESYSTEM_DISK', 'local') === 's3';

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Set FILESYSTEM_DISK=s3 for Cloudflare R2 (S3-compatible).
    | mPDF temp files always use local public/uploads/temp.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Upload disks (see App\Support\UploadStorage)
    |--------------------------------------------------------------------------
    */

    'uploads_disk' => $useS3 ? 's3' : 'local',

    'app_public_disk' => $useS3 ? 's3' : 'public',

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => public_path('uploads'),
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'auto'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => filter_var(env('AWS_USE_PATH_STYLE_ENDPOINT', false), FILTER_VALIDATE_BOOLEAN),
            'throw' => false,
        ],

        'dropbox' => [
            'driver' => 'dropbox',
            'authorization_token' => env('DROPBOX_ACCESS_TOKEN'),
        ],

    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];

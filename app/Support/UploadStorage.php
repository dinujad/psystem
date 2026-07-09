<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UploadStorage
{
    /** User uploads under public/uploads (documents, img, media, logos, …) */
    public static function diskName(): string
    {
        return config('filesystems.uploads_disk', 'local');
    }

    /** Files under storage/app/public (production jobs, WhatsApp media) */
    public static function appDiskName(): string
    {
        return config('filesystems.app_public_disk', 'public');
    }

    public static function usesCloud(): bool
    {
        return self::diskName() === 's3';
    }

    public static function disk()
    {
        return Storage::disk(self::diskName());
    }

    public static function appDisk()
    {
        return Storage::disk(self::appDiskName());
    }

    public static function normalizePath(string $path): string
    {
        return ltrim(str_replace('\\', '/', $path), '/');
    }

    public static function exists(string $relativePath): bool
    {
        $path = self::normalizePath($relativePath);

        return $path !== '' && self::disk()->exists($path);
    }

    public static function appExists(string $relativePath): bool
    {
        $path = self::appKey($relativePath);

        return $path !== '' && self::appDisk()->exists($path);
    }

    /** Public URL for uploads/ paths (documents/foo.pdf, img/bar.jpg). */
    public static function url(string $relativePath): string
    {
        $path = self::normalizePath($relativePath);
        if ($path === '') {
            return '';
        }

        if (self::usesCloud()) {
            return self::disk()->url($path);
        }

        return asset('uploads/'.$path);
    }

    /** Public URL for storage/app/public paths (production/1/file.pdf). */
    public static function appUrl(string $relativePath): string
    {
        $path = self::normalizePath($relativePath);
        if ($path === '') {
            return '';
        }

        if (config('filesystems.app_public_disk') === 's3') {
            return self::appDisk()->url(self::appKey($path));
        }

        return asset('storage/'.$path);
    }

    public static function putFileAs(string $directory, UploadedFile $file, string $name): bool
    {
        return (bool) self::disk()->putFileAs(
            self::normalizePath($directory),
            $file,
            $name,
            self::usesCloud() ? 'public' : []
        );
    }

    public static function put(string $relativePath, $contents): bool
    {
        return self::disk()->put(
            self::normalizePath($relativePath),
            $contents,
            self::usesCloud() ? 'public' : []
        );
    }

    public static function putApp(string $relativePath, $contents): bool
    {
        return self::appDisk()->put(
            self::appKey($relativePath),
            $contents,
            config('filesystems.app_public_disk') === 's3' ? 'public' : []
        );
    }

    public static function putAppFileAs(string $directory, UploadedFile $file, string $name): bool
    {
        $dir = self::normalizePath($directory);
        if (config('filesystems.app_public_disk') === 's3') {
            return (bool) self::appDisk()->putFileAs('app/'.$dir, $file, $name, 'public');
        }

        return (bool) self::appDisk()->putFileAs($dir, $file, $name, 'public');
    }

    public static function appDownload(string $relativePath, string $downloadName)
    {
        $key = self::appKey(self::normalizePath($relativePath));
        if (! self::appDisk()->exists($key)) {
            abort(404);
        }

        return self::appDisk()->download($key, $downloadName);
    }

    public static function delete(string $relativePath): bool
    {
        $path = self::normalizePath($relativePath);
        if ($path === '' || ! self::disk()->exists($path)) {
            return false;
        }

        return self::disk()->delete($path);
    }

    public static function deleteApp(string $relativePath): bool
    {
        $key = self::appKey($relativePath);
        if ($key === '' || ! self::appDisk()->exists($key)) {
            return false;
        }

        return self::appDisk()->delete($key);
    }

    /**
     * Local filesystem path for PDF/image processing (downloads from R2 to temp if needed).
     */
    public static function localPath(string $relativePath): ?string
    {
        $path = self::normalizePath($relativePath);
        if ($path === '') {
            return null;
        }

        if (! self::usesCloud()) {
            $full = public_path('uploads/'.$path);

            return is_file($full) ? $full : null;
        }

        if (! self::disk()->exists($path)) {
            return null;
        }

        $tempDir = public_path('uploads/temp/cloud');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0775, true);
        }

        $local = $tempDir.'/'.md5($path).'_'.basename($path);
        if (! is_file($local)) {
            file_put_contents($local, self::disk()->get($path));
        }

        return $local;
    }

    public static function appLocalPath(string $relativePath): ?string
    {
        $path = self::normalizePath($relativePath);
        if ($path === '') {
            return null;
        }

        if (config('filesystems.app_public_disk') !== 's3') {
            $full = storage_path('app/public/'.$path);

            return is_file($full) ? $full : null;
        }

        $key = self::appKey($path);
        if (! self::appDisk()->exists($key)) {
            return null;
        }

        $tempDir = public_path('uploads/temp/cloud');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0775, true);
        }

        $local = $tempDir.'/'.md5($key).'_'.basename($path);
        if (! is_file($local)) {
            file_put_contents($local, self::appDisk()->get($key));
        }

        return $local;
    }

    private static function appKey(string $relativePath): string
    {
        $path = self::normalizePath($relativePath);

        return config('filesystems.app_public_disk') === 's3'
            ? 'app/'.$path
            : $path;
    }
}

<?php

namespace App\Services;

use App\Models\Attendance;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\FileAttributes;
use Throwable;

class StorageUsageService
{
    public const CACHE_KEY = 'fibernet_storage_usage_stats';

    /**
     * Read bucket/disk metadata (cached for 10 minutes unless forced).
     *
     * @return array{
     *     disk: string,
     *     driver: string,
     *     bucket: string|null,
     *     total_bytes: int,
     *     quota_mb: int,
     *     quota_bytes: int,
     *     percent_used: float,
     *     active_count: int,
     *     active_bytes: int,
     *     temp_count: int,
     *     temp_bytes: int,
     *     orphan_count: int,
     *     orphan_bytes: int,
     *     cleanable_count: int,
     *     cleanable_bytes: int,
     *     checked_at: string,
     *     error: string|null
     * }
     */
    public function getStats(bool $forceRefresh = false): array
    {
        if (! $forceRefresh) {
            $cached = Cache::get(self::CACHE_KEY);
            if (is_array($cached) && isset($cached['disk'], $cached['total_bytes'])) {
                /** @var array{disk: string, driver: string, bucket: string|null, total_bytes: int, quota_mb: int, quota_bytes: int, percent_used: float, active_count: int, active_bytes: int, temp_count: int, temp_bytes: int, orphan_count: int, orphan_bytes: int, cleanable_count: int, cleanable_bytes: int, checked_at: string, error: string|null} $cached */
                return $cached;
            }
        }

        $disk = (string) config('filesystems.default', 'local');
        $driver = (string) config("filesystems.disks.{$disk}.driver", $disk);
        $rawBucket = config("filesystems.disks.{$disk}.bucket");
        $bucket = $driver === 's3' && is_string($rawBucket) && $rawBucket !== '' ? $rawBucket : null;

        $quotaMb = max(1, (int) config('filesystems.storage_quota_mb', 1024));
        $quotaBytes = $quotaMb * 1024 * 1024;

        $totalBytes = 0;
        $activeCount = 0;
        $activeBytes = 0;
        $tempCount = 0;
        $tempBytes = 0;
        $orphanCount = 0;
        $orphanBytes = 0;
        $cleanableCount = 0;
        $cleanableBytes = 0;
        $error = null;

        try {
            $knownPaths = $this->knownAttendancePhotoMap();
            $cutoff = now()->subMinutes(15)->getTimestamp();
            $filesystem = Storage::disk($disk);

            foreach ($filesystem->getDriver()->listContents('', true) as $item) {
                if (! $item instanceof FileAttributes) {
                    continue;
                }

                $path = ltrim($item->path(), '/');
                if ($path === '' || str_starts_with(basename($path), '.')) {
                    continue;
                }

                $size = max(0, (int) ($item->fileSize() ?? 0));
                $lastModified = $item->lastModified();
                $isSafeToClean = $lastModified === null || $lastModified <= $cutoff;

                $totalBytes += $size;

                if (str_starts_with($path, 'livewire-tmp/')) {
                    $tempCount++;
                    $tempBytes += $size;
                    if ($isSafeToClean) {
                        $cleanableCount++;
                        $cleanableBytes += $size;
                    }
                } elseif (isset($knownPaths[$path])) {
                    $activeCount++;
                    $activeBytes += $size;
                } elseif (str_starts_with($path, 'attendance/')) {
                    $orphanCount++;
                    $orphanBytes += $size;
                    if ($isSafeToClean) {
                        $cleanableCount++;
                        $cleanableBytes += $size;
                    }
                }
            }
        } catch (Throwable $e) {
            report($e);
            $error = 'Gagal membaca informasi storage: '.$e->getMessage();
        }

        $stats = [
            'disk' => $disk,
            'driver' => $driver,
            'bucket' => $bucket,
            'total_bytes' => $totalBytes,
            'quota_mb' => $quotaMb,
            'quota_bytes' => $quotaBytes,
            'percent_used' => round(($totalBytes / $quotaBytes) * 100, 2),
            'active_count' => $activeCount,
            'active_bytes' => $activeBytes,
            'temp_count' => $tempCount,
            'temp_bytes' => $tempBytes,
            'orphan_count' => $orphanCount,
            'orphan_bytes' => $orphanBytes,
            'cleanable_count' => $cleanableCount,
            'cleanable_bytes' => $cleanableBytes,
            'checked_at' => now()->format('d M Y, H:i:s'),
            'error' => $error,
        ];

        if ($error === null) {
            Cache::put(self::CACHE_KEY, $stats, now()->addMinutes(10));
        }

        return $stats;
    }

    /**
     * Delete stuck livewire-tmp files and orphan attendance files older than 15 minutes.
     *
     * @return array{deleted_count: int, freed_bytes: int}
     */
    public function cleanupUnusedFiles(): array
    {
        $disk = (string) config('filesystems.default', 'local');
        $filesystem = Storage::disk($disk);
        $knownPaths = $this->knownAttendancePhotoMap();
        $cutoff = now()->subMinutes(15)->getTimestamp();

        /** @var array<int, array{path: string, size: int}> $targets */
        $targets = [];

        foreach ($filesystem->getDriver()->listContents('', true) as $item) {
            if (! $item instanceof FileAttributes) {
                continue;
            }

            $path = ltrim($item->path(), '/');
            if ($path === '' || str_starts_with(basename($path), '.')) {
                continue;
            }

            $lastModified = $item->lastModified();
            if ($lastModified !== null && $lastModified > $cutoff) {
                continue;
            }

            $isTemp = str_starts_with($path, 'livewire-tmp/');
            $isOrphanAttendance = str_starts_with($path, 'attendance/') && ! isset($knownPaths[$path]);

            if ($isTemp || $isOrphanAttendance) {
                $targets[] = [
                    'path' => $path,
                    'size' => max(0, (int) ($item->fileSize() ?? 0)),
                ];
            }
        }

        $deletedCount = 0;
        $freedBytes = 0;

        foreach ($targets as $target) {
            try {
                if (Storage::disk($disk)->delete($target['path'])) {
                    $deletedCount++;
                    $freedBytes += $target['size'];
                }
            } catch (Throwable $e) {
                report($e);
            }
        }

        Cache::forget(self::CACHE_KEY);

        return [
            'deleted_count' => $deletedCount,
            'freed_bytes' => $freedBytes,
        ];
    }

    public static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $value = (float) max(0, $bytes);
        $unitIndex = 0;

        while ($value >= 1024 && $unitIndex < count($units) - 1) {
            $value /= 1024;
            $unitIndex++;
        }

        return ($unitIndex === 0 ? (string) (int) $value : number_format($value, 2)).' '.$units[$unitIndex];
    }

    /**
     * @return array<string, bool>
     */
    protected function knownAttendancePhotoMap(): array
    {
        /** @var array<int, string> $paths */
        $paths = Attendance::query()
            ->whereNotNull('check_in_photo_path')
            ->where('check_in_photo_path', '!=', '')
            ->pluck('check_in_photo_path')
            ->map(static fn (mixed $p): string => ltrim((string) $p, '/'))
            ->all();

        return array_fill_keys($paths, true);
    }
}

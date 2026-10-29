<?php

namespace App\Services;

use App\Models\Student;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class StudentDeletionService
{
    /**
     * Permanently delete a student, their user account, attendances, and check-in photos.
     *
     * @return int Number of check-in photo records processed for storage deletion
     */
    public function delete(Student $student): int
    {
        $user = $student->user()->first();

        if ($user && $user->isAdmin()) {
            throw new RuntimeException('Akun administrator tidak dapat dihapus melalui fitur ini.');
        }

        /** @var array<int, string> $photoPaths */
        $photoPaths = $student->attendances()
            ->whereNotNull('check_in_photo_path')
            ->where('check_in_photo_path', '!=', '')
            ->pluck('check_in_photo_path')
            ->unique()
            ->values()
            ->all();

        DB::transaction(function () use ($student, $user): void {
            if ($user) {
                DB::table('sessions')->where('user_id', $user->id)->delete();
                $user->delete();
            } else {
                $student->delete();
            }
        });

        $deletedPhotos = $this->deletePhotosFromStorage($photoPaths);

        Cache::forget(StorageUsageService::CACHE_KEY);

        return $deletedPhotos;
    }

    /**
     * Delete each photo individually for S3-compatible (Supabase) reliability.
     *
     * @param  array<int, string>  $photoPaths
     */
    protected function deletePhotosFromStorage(array $photoPaths): int
    {
        if ($photoPaths === []) {
            return 0;
        }

        $defaultDisk = (string) config('filesystems.default', 'local');
        $deletedCount = 0;

        foreach ($photoPaths as $path) {
            $deleted = false;

            try {
                if (Storage::disk($defaultDisk)->delete($path)) {
                    $deleted = true;
                }
            } catch (Throwable $e) {
                report($e);
            }

            if ($defaultDisk !== 'local') {
                try {
                    if (Storage::disk('local')->exists($path) && Storage::disk('local')->delete($path)) {
                        $deleted = true;
                    }
                } catch (Throwable $e) {
                    report($e);
                }
            }

            if ($deleted) {
                $deletedCount++;
            }
        }

        return $deletedCount;
    }
}

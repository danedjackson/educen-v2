<?php

namespace App\Http\Controllers;

use App\Models\LessonPlan;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LessonPlanDownloadController
{
    public function __invoke(Request $request, LessonPlan $lessonPlan, string $version): StreamedResponse
    {
        $user = $request->user();
        abort_unless(
            $user->hasRole('admin') || ($user->hasRole('teacher') && $lessonPlan->teacher_id === $user->ulid),
            403
        );

        abort_unless(in_array($version, ['original', 'current'], true), 404);

        $path = $version === 'original' ? $lessonPlan->original_path : $lessonPlan->currentFilePath();
        $filename = $version === 'original' ? $lessonPlan->original_filename : $lessonPlan->currentFilename();
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('local');

        abort_unless($disk->exists($path), 404);

        return $disk->download($path, basename(str_replace('\\', '/', $filename)));
    }
}

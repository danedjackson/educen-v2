<?php

use App\Http\Controllers\LessonPlanDownloadController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/awaiting-confirmation', function () {
    return view('awaiting-confirmation');
})->name('awaiting-confirmation');

Route::middleware(['auth', 'verified', 'confirmed'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::livewire('/students', 'students.index')->name('students.index');
    Route::livewire('/scores', 'scores.index')->name('scores.index');

    Route::middleware('role:teacher|admin')->group(function () {
        Route::livewire('/lesson-plans', 'lesson-plans.index')->name('lesson-plans.index');
        Route::get('/lesson-plans/{lessonPlan}/download/{version}', [LessonPlanDownloadController::class, '__invoke'])
            ->name('lesson-plans.download');
    });

    Route::middleware('role:admin')->group(function () {
        Route::livewire('/teachers', 'teachers.index')->name('teachers.index');
        Route::livewire('/grades', 'grades.index')->name('grades');
        Route::livewire('/admin', 'admin.index')->name('admin');
    });
});

require __DIR__.'/settings.php';

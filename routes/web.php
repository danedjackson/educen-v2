<?php

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
    Route::view('teachers', 'teachers')->name('teachers');
    Route::view('grades', 'grades')->name('grades');
    Route::livewire('/scores', 'scores.index')->name('scores.index');
    Route::livewire('/admin', 'admin.index')->name('admin');
});

require __DIR__.'/settings.php';

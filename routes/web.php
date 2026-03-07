<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::livewire('/students', 'students.index')->name('students.index');
    Route::view('teachers', 'teachers')->name('teachers');
    Route::view('grades', 'grades')->name('grades');
    Route::livewire('/scores', 'scores.index')->name('scores.index');
});

require __DIR__.'/settings.php';

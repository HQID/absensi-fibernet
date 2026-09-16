<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
})->name('home');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::view('/attendance', 'attendance.index')
        ->name('attendance');
});

Route::view('/students', 'students.index')
    ->name('students');

Route::middleware(['auth'])->group(function () {
    Route::get('/reports', [App\Http\Controllers\ReportController::class, 'index'])->name('reports');
    Route::get('/reports/export', [App\Http\Controllers\ReportController::class, 'export'])->name('reports.export');
});

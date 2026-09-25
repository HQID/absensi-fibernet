<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
})->name('home');

Route::view('/offline', 'offline')->name('offline');

Route::middleware(['auth', 'student_active'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::view('/attendance', 'attendance.index')
        ->name('attendance');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/students', \App\Livewire\StudentsIndex::class)->name('students');

    Route::get('/reports', [App\Http\Controllers\ReportController::class, 'index'])->name('reports');
    Route::get('/reports/export', [App\Http\Controllers\ReportController::class, 'export'])->name('reports.export');
    Route::get('/reports/{attendance}', [App\Http\Controllers\ReportController::class, 'show'])->name('reports.show');
    Route::post('/reports/{attendance}/approve', [App\Http\Controllers\ReportController::class, 'approve'])->name('reports.approve');
    Route::get('/reports/{attendance}/photo', [App\Http\Controllers\ReportController::class, 'photo'])->name('reports.photo');
});

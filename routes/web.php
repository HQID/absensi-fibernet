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

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/students', function () {
        return view('students.index', [
            'students' => \App\Models\Student::with('user')->latest()->paginate(10)
        ]);
    })->name('students');

    Route::get('/reports', [App\Http\Controllers\ReportController::class, 'index'])->name('reports');
    Route::get('/reports/export', [App\Http\Controllers\ReportController::class, 'export'])->name('reports.export');
    Route::get('/reports/{attendance}', [App\Http\Controllers\ReportController::class, 'show'])->name('reports.show');
    Route::get('/reports/{attendance}/photo', [App\Http\Controllers\ReportController::class, 'photo'])->name('reports.photo');
});

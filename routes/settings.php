<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::livewire('settings/profile', 'pages::settings.profile')->name('profile.edit');
});

Route::middleware(['auth', 'verified'])->group(function () {

    Route::livewire('settings/security', 'pages::settings.security')
        ->name('security.edit');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::livewire('settings/geofence', 'pages::settings.geofence')
        ->name('geofence.edit');
});

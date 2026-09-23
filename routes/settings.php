<?php

use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use Illuminate\Support\Facades\Route;

/*
 * Settings is one Profile page. The old Security and Appearance addresses
 * send people there; the theme switch lives in the top bar.
 */
Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');
    Route::redirect('settings/security', '/settings/profile')->name('security.edit');
    Route::redirect('settings/appearance', '/settings/profile')->name('appearance.edit');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');
});

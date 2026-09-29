<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)->group(function () {
    Route::post('/logout', 'logout')->name('logout');
    Route::post('/refresh-token', 'setRefreshToken')->name('refresh-token');

    // forgot password
    Route::post('/forgot-password', 'forgotPassword')->name('forgot-password');
    Route::post('/verify-otp-for-password', 'verifyOtpForPassword')->name('verify-otp-for-password');
    Route::post('/reset-password', 'resetPassword')->name('reset-password');
});

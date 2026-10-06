<?php

use App\Http\Controllers\Auth\PublicAuthController;
use Illuminate\Support\Facades\Route;

Route::controller(PublicAuthController::class)->group(function () {
    // Registration with phone number
    Route::post('/register', 'register')->name('register');
    Route::post('/verify-otp', 'verifyOtp')->name('verify-otp');
    Route::post('/confirm-password', 'confirmPassword')->name('confirm-password');

    // Registration with email
    Route::post('/register-with-email', 'registerWithEmail')->name('register-with-email');

    Route::post('/login', 'login')->name('login');

    // forgot password
    Route::post('/forgot-password', 'forgotPassword')->name('forgot-password');
    Route::post('/verify-otp-for-password', 'verifyOtpForPassword')->name('verify-otp-for-password');
    Route::post('/reset-password', 'resetPassword')->name('reset-password');
});

<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

// Route::post('/register', [AuthController::class, 'register'])->name('register');
// Route::post('/login',    [AuthController::class, 'login'])->name('login');

// Route::middleware('auth:sanctum')->group(function () {
//     Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
//     Route::get('/user', function (Request $request) {
//         return $request->user();
//     })->name('user');
// });

Route::controller(AuthController::class)->prefix('v1')->group(function () {
    // Register with phone number
    Route::post('/register', 'register')->name('register');
    Route::post('/verify-otp', 'verifyOtp')->name('verify-otp');
    Route::post('/confirm-password', 'confirmPassword')->name('confirm-password');

    // Register with email
    Route::post('/register-with-email', 'registerWithEmail')->name('register-with-email');

    Route::post('/login', 'login')->name('login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', 'logout')->name('logout');
    });
});

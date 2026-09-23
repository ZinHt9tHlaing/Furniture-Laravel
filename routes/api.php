<?php

use App\Http\Controllers\Admin\UserController;
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

Route::prefix("v1")->group(function () {
    // Authentication Routes
    Route::controller(AuthController::class)->group(function () {
        // Registration with phone number
        Route::post('/register', 'register')->name('register');
        Route::post('/verify-otp', 'verifyOtp')->name('verify-otp');
        Route::post('/confirm-password', 'confirmPassword')->name('confirm-password');

        // Registration with email
        Route::post('/register-with-email', 'registerWithEmail')->name('register-with-email');

        Route::post('/login', 'login')->name('login');
    });

    // Protected Routes (Authenticated)
    Route::middleware(['auth.cookie'])->group(function () {
        Route::controller(AuthController::class)->group(function () {
            Route::post('/logout', 'logout')->name('logout');

            // Refresh Token Rotation
            Route::post('/refresh-token', 'setRefreshToken')->name('refresh-token');
        });

        // User Management
        Route::controller(UserController::class)->prefix('admin')->name('admin.')->group(function () {
            Route::get('/users', 'getAllUsers')->name('users');
        });
    });
});

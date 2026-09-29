<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix("v1")->group(function () {
    // Public auth routes
    Route::as('auth.')->group(base_path('routes/api/v1/auth.php'));

    // Protected Routes (Authenticated)
    Route::middleware(['auth.cookie'])->group(function () {
        // Session & token lifecycle
        Route::controller(AuthController::class)->group(function () {
            Route::post('/logout', 'logout')->name('logout');
            Route::post('/refresh-token', 'setRefreshToken')->name('refresh-token');
        });

        // admin route
        Route::prefix('admin')->as('admin.')->group(base_path('routes/api/v1/admin.php'));
        // profile route
        Route::prefix('user')->as('user.')->group(base_path('routes/api/v1/profile.php'));
    });
});

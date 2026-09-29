<?php

use Illuminate\Support\Facades\Route;

Route::prefix("v1")->group(function () {
    // Public auth routes
    Route::as('public_auth.')->group(base_path('routes/api/v1/public_auth.php'));

    // Protected Routes (Authenticated)
    Route::middleware(['auth.cookie'])->group(function () {
        Route::as('auth.')->group(base_path('routes/api/v1/auth.php'));

        // admin route
        Route::prefix('admin')->as('admin.')->group(base_path('routes/api/v1/admin.php'));
        // profile route
        Route::prefix('user')->as('user.')->group(base_path('routes/api/v1/profile.php'));
    });
});

<?php

use Illuminate\Support\Facades\Route;

Route::prefix("v1")->group(function () {
    // Public auth routes
    Route::as('public_auth.')->group(base_path('routes/v1/auth/public_auth.php'));

    // Protected Routes (Authenticated)
    Route::middleware(['auth.cookie'])->group(function () {
        Route::as('auth.')->group(base_path('routes/v1/auth/auth.php'));

        // admin route (Protected by auth.cookie + role authorization)
        Route::prefix('admin')
            ->as('admin.')
            ->middleware(['authorize:true,ADMIN,AUTHOR'])
            ->group(base_path('routes/v1/admin/admin.php'));

        // profile route
        Route::prefix('user')->as('user.')->group(base_path('routes/v1/profile/profile.php'));
    });
});

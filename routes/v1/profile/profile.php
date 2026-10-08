<?php

use App\Http\Controllers\Api\ProfileController;
use Illuminate\Support\Facades\Route;

Route::controller(ProfileController::class)
    ->group(function () {
        Route::get('/test-permission', 'testPermission')->name('test-permission');
        Route::prefix("profile")->group(function () {
            Route::patch('/upload', 'uploadProfile')->name('upload-profile');
            Route::get('/my-photo', 'getMyPhoto')->name('my-photo');
            Route::get("/get-user-info", "getUserInfo")->name('get-user-info');
            Route::put("/change-name", "changeName")->name('change-name');
            Route::put("/change-email", "changeEmail")->name('change-email');
            Route::put("/change-password", "changePassword")->name('change-password');
        });
    });

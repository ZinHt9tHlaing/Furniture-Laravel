<?php

use App\Http\Controllers\Api\ProfileController;
use Illuminate\Support\Facades\Route;

Route::controller(ProfileController::class)
    ->group(function () {
        Route::get('/test-permission', 'testPermission')->name('test-permission');
        Route::patch('/profile/upload', 'uploadProfile')->name('upload-profile');
        Route::get('/profile/my-photo', 'getMyPhoto')->name('my-photo');
    });

<?php

use App\Http\Controllers\Api\ProfileController;
use Illuminate\Support\Facades\Route;

Route::controller(ProfileController::class)
    ->prefix('user')->group(function () {
        Route::get('/test-permission', 'testPermission')->name('test-permission');
    });

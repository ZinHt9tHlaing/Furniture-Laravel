<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)->group(function () {
    Route::post('/logout', 'logout')->name('logout');
    Route::post('/refresh-token', 'setRefreshToken')->name('refresh-token');
});

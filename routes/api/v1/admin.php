<?php

use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['authorize:true,ADMIN,AUTHOR'])->controller(UserController::class)
    ->group(function () {
        // Users
        Route::get('/users', 'getAllUsers')->name('users');
    });

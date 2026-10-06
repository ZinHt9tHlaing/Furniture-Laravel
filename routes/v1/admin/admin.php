<?php

use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

// User routes
Route::controller(UserController::class)->group(function () {
    Route::get('/users', 'getAllUsers')->name('users');
});

// Post routes
Route::as('posts.')->group(base_path('routes/v1/admin/posts/post_api.php'));

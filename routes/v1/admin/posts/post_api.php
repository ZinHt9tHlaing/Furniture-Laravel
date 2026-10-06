<?php

use App\Http\Controllers\PostController;
use Illuminate\Support\Facades\Route;

Route::controller(PostController::class)->group(function () {
    Route::post('/create-post', 'store')->name('create-post');
});

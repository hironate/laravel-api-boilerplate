<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\User\UserController;

Route::controller(UserController::class)->middleware(['auth:sanctum', 'role:user'])->group(function () {
    Route::get('/me', 'index')->name('user.index');
});

<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;


Route::controller(AuthController::class)->prefix('auth')->group(function () {
    Route::post('/register', 'register')->name('register');
    Route::post('/login', 'login')->name('login');
    Route::get('/email/verify/{id}/{hash}', 'verifyEmail')->middleware('signed')->name('verification.verify');
    Route::post('/forgot-password', 'forgotPassword')->name('password.email');
    Route::post('/reset-password', 'resetPassword')->name('password.reset');

    Route::post('/email/verification-notification', 'resendVerificationEmail')->name('verification.send');
    Route::post('/logout', 'logout')->middleware('auth:sanctum')->name('logout');
    Route::post('/login/google', 'googleLogin')->name('google.login');
});

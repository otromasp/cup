<?php

use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\CasosUso\CU01GestionarAcceso\ControladorAcceso;
use App\Http\Controllers\CasosUso\CU01GestionarAcceso\ControladorRecuperacionContrasena;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [ControladorAcceso::class, 'create'])
        ->name('login');

    Route::post('login', [ControladorAcceso::class, 'store']);

    Route::get('forgot-password', [ControladorRecuperacionContrasena::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [ControladorRecuperacionContrasena::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.email');

    Route::get('reset-password/{token}', [ControladorRecuperacionContrasena::class, 'edit'])
        ->name('password.reset');

    Route::post('reset-password', [ControladorRecuperacionContrasena::class, 'update'])
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::post('logout', [ControladorAcceso::class, 'destroy'])
        ->name('logout');
});

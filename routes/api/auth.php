<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Auth\ChangePasswordController;
use App\Http\Controllers\Api\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutAllController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\MeController;
use App\Http\Controllers\Api\Auth\RefreshTokenController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\ResendVerificationEmailController;
use App\Http\Controllers\Api\Auth\ResetPasswordController;
use App\Http\Controllers\Api\Auth\SessionDestroyController;
use App\Http\Controllers\Api\Auth\SessionsIndexController;
use App\Http\Controllers\Api\Auth\TwoFactorConfirmController;
use App\Http\Controllers\Api\Auth\TwoFactorDisableController;
use App\Http\Controllers\Api\Auth\TwoFactorEnableController;
use App\Http\Controllers\Api\Auth\TwoFactorStatusController;
use App\Http\Controllers\Api\Auth\TwoFactorVerifyController;
use App\Http\Controllers\Api\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication REST API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('api/v1/auth')->group(function () {

    // --- Public Routes ---
    Route::post('/register', RegisterController::class)->middleware('throttle:register');
    Route::post('/login', LoginController::class)->middleware('throttle:login');
    Route::post('/refresh', RefreshTokenController::class)->middleware('throttle:60,1');
    Route::post('/forgot-password', ForgotPasswordController::class)->middleware('throttle:forgot-password');
    Route::post('/reset-password', ResetPasswordController::class)->middleware('throttle:60,1');
    Route::post('/2fa/verify', TwoFactorVerifyController::class)->middleware('throttle:10,1');
    Route::get('/email/verify/{id}/{hash}', VerifyEmailController::class)->name('verification.verify');

    // --- Protected Routes (auth:sanctum) ---
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/me', MeController::class);
        Route::post('/logout', LogoutController::class);
        Route::post('/logout-all', LogoutAllController::class);
        Route::post('/change-password', ChangePasswordController::class);

        // Email Verification
        Route::post('/email/resend', ResendVerificationEmailController::class)->middleware('throttle:6,1');

        // Sessions Management
        Route::get('/sessions', SessionsIndexController::class);
        Route::delete('/sessions/{id}', SessionDestroyController::class);

        // 2FA Management
        Route::get('/2fa/status', TwoFactorStatusController::class);
        Route::post('/2fa/enable', TwoFactorEnableController::class);
        Route::post('/2fa/confirm', TwoFactorConfirmController::class);
        Route::post('/2fa/disable', TwoFactorDisableController::class);
    });
});

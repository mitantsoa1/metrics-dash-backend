<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ScriptController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/script.js', ScriptController::class)->withoutMiddleware([\App\Http\Middleware\AuthenticateFromCookie::class]);
Route::get('/metrics.js', ScriptController::class)->withoutMiddleware([\App\Http\Middleware\AuthenticateFromCookie::class]);
Route::get('/api/v1/script.js', ScriptController::class)->withoutMiddleware([\App\Http\Middleware\AuthenticateFromCookie::class]);
Route::get('/api/v1/reports/download', \App\Http\Controllers\ReportDownloadController::class)->withoutMiddleware([\App\Http\Middleware\AuthenticateFromCookie::class]);




Route::get('/api', function () {
    return redirect('/docs');
});

Route::get('/api/docs', function () {
    return redirect('/docs');
});

// Routes Authentification (Password Reset & Google OAuth)
Route::prefix('api/v1/auth')->group(function () {
    Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword']);
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);

    Route::get('/google/url', [GoogleAuthController::class, 'getUrl']);
    Route::get('/google/redirect', [GoogleAuthController::class, 'redirect']);
    Route::get('/google/callback', [GoogleAuthController::class, 'callback']);
});

Route::get('/api/env', function () {
    // return config('app.frontend_url');
    return env('APP_URL') . " --- " . env('FRONTEND_URL') . " ****" . config('app.frontend_url');
});

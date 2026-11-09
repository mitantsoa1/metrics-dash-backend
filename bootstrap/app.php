<?php

use App\Http\Middleware\AuthenticateFromCookie;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('api')
                ->group(__DIR__.'/../routes/api/auth.php');
        }
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->encryptCookies(except: [
            'access_token',
            'refresh_token',
        ]);
        $middleware->prependToGroup('api', AuthenticateFromCookie::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->booted(function () {
        // Rate Limiter for Login
        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->input('email');
            return Limit::perMinute(5)->by($email . '|' . $request->ip());
        });

        // Rate Limiter for Register
        RateLimiter::for('register', function (Request $request) {
            return Limit::perHour(3)->by($request->ip());
        });

        // Ingestion API Push : 1000 événements/minute par clé (PU-54/55)
        RateLimiter::for('push-ingest', function (Request $request) {
            $key = $request->bearerToken() ?: (string) $request->header('X-Push-Secret-Key', '');

            return Limit::perMinute(1000)->by($key !== '' ? hash('sha256', $key) : 'ip:'.$request->ip());
        });

        // Rate Limiter for Forgot Password
        RateLimiter::for('forgot-password', function (Request $request) {
            return Limit::perHour(3)->by($request->ip());
        });
    })
    ->create();

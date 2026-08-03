<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        // Redirection propre de /api et /api/docs vers la documentation Swagger /docs
        if (! app()->runningInConsole()) {
            $path = request()->path();
            if ($path === 'api' || $path === 'api/docs') {
                header('Location: /docs');
                exit;
            }
        }
    }

}


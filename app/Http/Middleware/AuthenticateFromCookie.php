<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\AuthCookieManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateFromCookie
{
    public function handle(Request $request, Closure $next): Response
    {
        // If Authorization header is missing, check if access_token cookie is present
        if (! $request->hasHeader('Authorization')) {
            $accessToken = $request->cookie(AuthCookieManager::ACCESS_TOKEN_COOKIE);
            if ($accessToken) {
                $request->headers->set('Authorization', 'Bearer ' . $accessToken);
            }
        }

        return $next($request);
    }
}

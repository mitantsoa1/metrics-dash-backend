<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Cookie;

class AuthCookieManager
{
    public const ACCESS_TOKEN_COOKIE = 'access_token';
    public const REFRESH_TOKEN_COOKIE = 'refresh_token';

    /**
     * Attach access_token and refresh_token cookies to a JsonResponse.
     */
    public static function attachAuthCookies(
        JsonResponse $response,
        string $accessToken,
        string $refreshToken,
        bool $rememberMe = false
    ): JsonResponse {
        $isSecure = request()->secure() || config('app.env') === 'production';
        $sameSite = Cookie::SAMESITE_LAX;

        // Access token cookie (15 minutes)
        $accessCookie = new Cookie(
            name: self::ACCESS_TOKEN_COOKIE,
            value: $accessToken,
            expire: time() + (15 * 60), // 15 mins
            path: '/',
            domain: null,
            secure: $isSecure,
            httpOnly: true, // Insensible au XSS JavaScript
            raw: false,
            sameSite: $sameSite
        );

        // Refresh token cookie (14 days or 30 days if remember me)
        $refreshDays = $rememberMe ? 30 : 14;
        $refreshCookie = new Cookie(
            name: self::REFRESH_TOKEN_COOKIE,
            value: $refreshToken,
            expire: time() + ($refreshDays * 86400),
            path: '/',
            domain: null,
            secure: $isSecure,
            httpOnly: true, // Insensible au XSS JavaScript
            raw: false,
            sameSite: $sameSite
        );

        $response->headers->setCookie($accessCookie);
        $response->headers->setCookie($refreshCookie);

        return $response;
    }

    /**
     * Clear auth cookies from a JsonResponse.
     */
    public static function clearAuthCookies(JsonResponse $response): JsonResponse
    {
        $isSecure = request()->secure() || config('app.env') === 'production';

        $expiredAccess = new Cookie(
            name: self::ACCESS_TOKEN_COOKIE,
            value: '',
            expire: time() - 3600,
            path: '/',
            domain: null,
            secure: $isSecure,
            httpOnly: true,
            raw: false,
            sameSite: Cookie::SAMESITE_LAX
        );

        $expiredRefresh = new Cookie(
            name: self::REFRESH_TOKEN_COOKIE,
            value: '',
            expire: time() - 3600,
            path: '/',
            domain: null,
            secure: $isSecure,
            httpOnly: true,
            raw: false,
            sameSite: Cookie::SAMESITE_LAX
        );

        $response->headers->setCookie($expiredAccess);
        $response->headers->setCookie($expiredRefresh);

        return $response;
    }
}

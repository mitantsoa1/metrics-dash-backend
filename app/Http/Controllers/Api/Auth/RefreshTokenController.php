<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Domains\IAM\Actions\RefreshTokenAction;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use App\Support\AuthCookieManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class RefreshTokenController extends Controller
{
    public function __construct(
        private readonly RefreshTokenAction $refreshTokenAction
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            // Read refresh token from HttpOnly cookie or request payload
            $refreshToken = $request->cookie(AuthCookieManager::REFRESH_TOKEN_COOKIE)
                ?? $request->input('refresh_token');

            if (! $refreshToken) {
                return ApiResponse::unauthorized('Jeton de rafraîchissement absent.');
            }

            $newTokens = $this->refreshTokenAction->execute(
                $refreshToken,
                $request->ip(),
                $request->userAgent()
            );

            $response = ApiResponse::success([
                'token' => $newTokens['access_token'],
                'token_type' => 'Bearer',
                'tokenType' => 'Bearer',
                'expires_at' => $newTokens['access_token_expires_at'],
            ], 'Jeton renouvelé avec succès.');

            return AuthCookieManager::attachAuthCookies(
                $response,
                $newTokens['access_token'],
                $newTokens['refresh_token']
            );
        } catch (Throwable $e) {
            $response = ApiResponse::unauthorized($e->getMessage());
            return AuthCookieManager::clearAuthCookies($response);
        }
    }
}

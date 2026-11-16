<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Domains\IAM\Actions\LogoutAction;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use App\Support\AuthCookieManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    public function __construct(
        private readonly LogoutAction $logoutAction
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $refreshToken = $request->cookie(AuthCookieManager::REFRESH_TOKEN_COOKIE)
            ?? $request->input('refresh_token');

        $userId = $request->user()?->id;

        $this->logoutAction->execute(
            $refreshToken,
            $userId,
            $request->ip(),
            $request->userAgent()
        );

        $response = ApiResponse::success(null, 'Déconnexion réussie.');
        return AuthCookieManager::clearAuthCookies($response);
    }
}

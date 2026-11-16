<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Domains\IAM\Infrastructure\Models\UserRefreshTokenModel;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use App\Support\AuthCookieManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionsIndexController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentRefreshTokenCookie = $request->cookie(AuthCookieManager::REFRESH_TOKEN_COOKIE);
        $currentHash = $currentRefreshTokenCookie ? hash('sha256', $currentRefreshTokenCookie) : null;

        $tokens = UserRefreshTokenModel::where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->orderByDesc('created_at')
            ->get();

        $sessions = $tokens->map(function (UserRefreshTokenModel $token) use ($currentHash) {
            return [
                'id' => $token->id,
                'device_name' => $token->device_name,
                'ip_address' => $token->ip_address,
                'user_agent' => $token->user_agent,
                'is_current' => $currentHash !== null && $token->token_hash === $currentHash,
                'created_at' => $token->created_at->toIso8601String(),
                'expires_at' => $token->expires_at->toIso8601String(),
            ];
        });

        return ApiResponse::success($sessions, 'Liste des sessions actives récupérée.');
    }
}

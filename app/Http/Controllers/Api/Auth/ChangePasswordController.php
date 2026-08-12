<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Domains\IAM\Actions\ChangePasswordAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Support\ApiResponse;
use App\Support\AuthCookieManager;
use Illuminate\Http\JsonResponse;
use Throwable;

class ChangePasswordController extends Controller
{
    public function __construct(
        private readonly ChangePasswordAction $changePasswordAction
    ) {}

    public function __invoke(ChangePasswordRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $currentRefreshToken = $request->cookie(AuthCookieManager::REFRESH_TOKEN_COOKIE);

            $this->changePasswordAction->execute(
                $user,
                $request->input('current_password'),
                $request->input('password'),
                $currentRefreshToken,
                $request->ip(),
                $request->userAgent()
            );

            return ApiResponse::success(null, 'Mot de passe modifié avec succès. Vos autres sessions ont été fermées par sécurité.');
        } catch (Throwable $e) {
            return ApiResponse::fromException($e, 400);
        }
    }
}

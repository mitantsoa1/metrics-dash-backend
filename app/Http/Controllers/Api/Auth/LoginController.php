<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Domains\IAM\Actions\AuthenticateUserAction;
use App\Domains\IAM\DTOs\LoginInputDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\Auth\UserResource;
use App\Support\ApiResponse;
use App\Support\AuthCookieManager;
use Illuminate\Http\JsonResponse;
use Throwable;

class LoginController extends Controller
{
    public function __construct(
        private readonly AuthenticateUserAction $authenticateUserAction
    ) {}

    public function __invoke(LoginRequest $request): JsonResponse
    {
        try {
            $dto = new LoginInputDTO(
                email: trim($request->input('email')),
                password: $request->input('password'),
                rememberMe: (bool) $request->input('remember_me', false),
                ipAddress: $request->ip(),
                userAgent: $request->userAgent()
            );

            $result = $this->authenticateUserAction->execute($dto);

            // Handle 2FA requirement
            if ($result->twoFactorRequired) {
                return ApiResponse::success([
                    'two_factor_required' => true,
                    'challenge_token' => $result->challengeToken,
                ], 'Vérification à deux facteurs requise.');
            }

            // Build JsonResponse
            $response = ApiResponse::success([
                'user' => [
                    'id' => $result->userId,
                    'tenant_id' => $result->tenantId,
                    'name' => $result->name,
                    'email' => $result->email,
                    'role' => $result->role,
                    'email_verified_at' => $result->emailVerifiedAt,
                ],
                'token' => $result->accessToken,
                'token_type' => 'Bearer',
                'tokenType' => 'Bearer',
                'expires_at' => $result->accessTokenExpiresAt,
            ], 'Connexion réussie.');

            // Attach HttpOnly Cookies
            return AuthCookieManager::attachAuthCookies(
                $response,
                $result->accessToken,
                $result->refreshToken,
                (bool) $request->input('remember_me', false)
            );
        } catch (Throwable $e) {
            return ApiResponse::fromException($e, 401);
        }
    }
}

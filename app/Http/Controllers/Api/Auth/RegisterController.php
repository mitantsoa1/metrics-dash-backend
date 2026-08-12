<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Domains\IAM\Actions\RegisterTenantAction;
use App\Domains\IAM\DTOs\RegisterTenantInputDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Support\ApiResponse;
use App\Support\AuthCookieManager;
use Illuminate\Http\JsonResponse;
use Throwable;

class RegisterController extends Controller
{
    public function __construct(
        private readonly RegisterTenantAction $registerTenantAction
    ) {}

    public function __invoke(RegisterRequest $request): JsonResponse
    {
        try {
            $dto = new RegisterTenantInputDTO(
                organizationName: trim($request->input('organizationName')),
                ownerName: trim($request->input('ownerName')),
                ownerEmail: trim(strtolower($request->input('ownerEmail'))),
                password: $request->input('password')
            );

            $result = $this->registerTenantAction->execute($dto);

            $response = ApiResponse::success([
                'tenant' => [
                    'id' => $result->tenantId,
                    'name' => $result->organizationName,
                    'slug' => $result->slug,
                ],
                'user' => [
                    'id' => $result->ownerUserId,
                    'name' => $result->ownerName,
                    'email' => $result->ownerEmail,
                    'role' => 'OWNER',
                ],
            ], 'Inscription réussie.', 201);

            if ($result->refreshToken) {
                AuthCookieManager::attachAuthCookies($response, $result->token, $result->refreshToken);
            }

            return $response;
        } catch (Throwable $e) {
            return ApiResponse::fromException($e, 400);
        }
    }
}

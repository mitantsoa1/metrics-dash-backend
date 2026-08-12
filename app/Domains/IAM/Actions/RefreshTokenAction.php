<?php

declare(strict_types=1);

namespace App\Domains\IAM\Actions;

use App\Domains\IAM\Services\TokenService;
use Illuminate\Auth\AuthenticationException;

class RefreshTokenAction
{
    public function __construct(
        private readonly TokenService $tokenService
    ) {}

    public function execute(string $refreshToken, ?string $ipAddress = null, ?string $userAgent = null): array
    {
        $newTokens = $this->tokenService->rotateRefreshToken($refreshToken, $ipAddress, $userAgent);

        if (! $newTokens) {
            throw new AuthenticationException('Le jeton de rafraîchissement est invalide ou a expiré. Veuillez vous reconnecter.');
        }

        return $newTokens;
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\IAM\Actions;

use App\Domains\IAM\Enums\AuditEvent;
use App\Domains\IAM\Services\AuditService;
use App\Domains\IAM\Services\TokenService;

class LogoutAction
{
    public function __construct(
        private readonly TokenService $tokenService,
        private readonly AuditService $auditService
    ) {}

    public function execute(?string $refreshToken, ?string $userId = null, ?string $ipAddress = null, ?string $userAgent = null): void
    {
        if ($refreshToken) {
            $this->tokenService->revokeRefreshToken($refreshToken);
        }

        if ($userId) {
            $this->auditService->log(
                AuditEvent::LOGOUT,
                $userId,
                $ipAddress,
                $userAgent
            );
        }
    }

    public function logoutAll(string $userId, ?string $ipAddress = null, ?string $userAgent = null): void
    {
        $this->tokenService->revokeAllUserTokens($userId);

        $this->auditService->log(
            AuditEvent::LOGOUT_ALL,
            $userId,
            $ipAddress,
            $userAgent
        );
    }
}

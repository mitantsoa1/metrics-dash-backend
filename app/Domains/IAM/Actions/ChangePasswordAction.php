<?php

declare(strict_types=1);

namespace App\Domains\IAM\Actions;

use App\Domains\IAM\Enums\AuditEvent;
use App\Domains\IAM\Infrastructure\Models\UserModel;
use App\Domains\IAM\Services\AuditService;
use App\Domains\IAM\Services\TokenService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ChangePasswordAction
{
    public function __construct(
        private readonly TokenService $tokenService,
        private readonly AuditService $auditService
    ) {}

    public function execute(
        UserModel $user,
        string $currentPassword,
        string $newPassword,
        ?string $currentRefreshToken = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): void {
        if (! Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Le mot de passe actuel est incorrect.'],
            ]);
        }

        $user->password = Hash::make($newPassword);
        $user->save();

        // Invalidate all OTHER refresh tokens and sessions for security
        $this->tokenService->revokeOtherUserTokens($user->id, $currentRefreshToken);

        $this->auditService->log(
            AuditEvent::PASSWORD_CHANGED,
            $user->id,
            $ipAddress,
            $userAgent
        );
    }
}

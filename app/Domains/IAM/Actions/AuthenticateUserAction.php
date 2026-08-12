<?php

declare(strict_types=1);

namespace App\Domains\IAM\Actions;

use App\Domains\IAM\DTOs\AuthTokenOutputDTO;
use App\Domains\IAM\DTOs\LoginInputDTO;
use App\Domains\IAM\Enums\AuditEvent;
use App\Domains\IAM\Exceptions\UserNotFoundException;
use App\Domains\IAM\Infrastructure\Models\UserModel;
use App\Domains\IAM\Repositories\UserRepositoryInterface;
use App\Domains\IAM\Services\AuditService;
use App\Domains\IAM\Services\TokenService;
use App\Domains\IAM\ValueObjects\Email;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthenticateUserAction
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly TokenService $tokenService,
        private readonly AuditService $auditService,
    ) {}

    public function execute(LoginInputDTO $dto): AuthTokenOutputDTO
    {
        $email = Email::fromString($dto->email);
        $user = $this->userRepository->findByEmail($email);

        if (! $user) {
            $this->auditService->log(
                AuditEvent::LOGIN_FAILED,
                null,
                $dto->ipAddress,
                $dto->userAgent,
                ['email' => $dto->email, 'reason' => 'user_not_found']
            );
            throw new AuthenticationException('Identifiants de connexion invalides.');
        }

        if (! Hash::check($dto->password, $user->getPasswordHash())) {
            $this->auditService->log(
                AuditEvent::LOGIN_FAILED,
                $user->getId()->toString(),
                $dto->ipAddress,
                $dto->userAgent,
                ['reason' => 'invalid_password']
            );
            throw new AuthenticationException('Identifiants de connexion invalides.');
        }

        $eloquentUser = UserModel::findOrFail($user->getId()->toString());

        // Check if 2FA is enabled
        if ($eloquentUser->hasTwoFactorEnabled()) {
            $challengeToken = Str::random(40);
            cache()->put("2fa_challenge_{$challengeToken}", $eloquentUser->id, now()->addMinutes(5));

            return new AuthTokenOutputDTO(
                accessToken: '',
                refreshToken: '',
                tokenType: 'Bearer',
                accessTokenExpiresAt: '',
                userId: $user->getId()->toString(),
                tenantId: $user->getTenantId()->toString(),
                role: $user->getRole()->value,
                email: $user->getEmail()->toString(),
                name: $user->getName(),
                emailVerifiedAt: $user->getEmailVerifiedAt()?->format('Y-m-d H:i:s'),
                twoFactorRequired: true,
                challengeToken: $challengeToken
            );
        }

        // Generate Dual-Token Pair
        $tokens = $this->tokenService->generateTokenPair(
            $eloquentUser,
            $dto->ipAddress,
            $dto->userAgent
        );

        $this->auditService->log(
            AuditEvent::LOGIN_SUCCESS,
            $user->getId()->toString(),
            $dto->ipAddress,
            $dto->userAgent
        );

        return new AuthTokenOutputDTO(
            accessToken: $tokens['access_token'],
            refreshToken: $tokens['refresh_token'],
            tokenType: 'Bearer',
            accessTokenExpiresAt: $tokens['access_token_expires_at'],
            userId: $user->getId()->toString(),
            tenantId: $user->getTenantId()->toString(),
            role: $user->getRole()->value,
            email: $user->getEmail()->toString(),
            name: $user->getName(),
            emailVerifiedAt: $user->getEmailVerifiedAt()?->format('Y-m-d H:i:s'),
            twoFactorRequired: false
        );
    }
}

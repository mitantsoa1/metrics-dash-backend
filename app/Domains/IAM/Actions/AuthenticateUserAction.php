<?php

declare(strict_types=1);

namespace App\Domains\IAM\Actions;

use App\Domains\IAM\DTOs\AuthTokenOutputDTO;
use App\Domains\IAM\DTOs\LoginInputDTO;
use App\Domains\IAM\Exceptions\UserNotFoundException;
use App\Domains\IAM\Infrastructure\Models\UserModel;
use App\Domains\IAM\Repositories\UserRepositoryInterface;
use App\Domains\IAM\ValueObjects\Email;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;

class AuthenticateUserAction
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}

    public function execute(LoginInputDTO $dto): AuthTokenOutputDTO
    {
        $email = Email::fromString($dto->email);
        $user = $this->userRepository->findByEmail($email);

        if (! $user) {
            throw UserNotFoundException::forEmail($dto->email);
        }

        if (! Hash::check($dto->password, $user->getPasswordHash())) {
            throw new AuthenticationException('Identifiants de connexion invalides.');
        }

        $eloquentUser = UserModel::findOrFail($user->getId()->toString());
        $token = $eloquentUser->createToken('auth-token')->plainTextToken;

        return new AuthTokenOutputDTO(
            token: $token,
            tokenType: 'Bearer',
            userId: $user->getId()->toString(),
            tenantId: $user->getTenantId()->toString(),
            role: $user->getRole()->value,
            email: $user->getEmail()->toString()
        );
    }
}

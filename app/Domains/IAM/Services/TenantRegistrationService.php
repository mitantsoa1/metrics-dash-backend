<?php

declare(strict_types=1);

namespace App\Domains\IAM\Services;

use App\Domains\IAM\DTOs\RegisterTenantInputDTO;
use App\Domains\IAM\DTOs\RegisterTenantOutputDTO;
use App\Domains\IAM\Entities\Tenant;
use App\Domains\IAM\Entities\User;
use App\Domains\IAM\Enums\AuditEvent;
use App\Domains\IAM\Enums\UserRole;
use App\Domains\IAM\Events\TenantCreatedEvent;
use App\Domains\IAM\Exceptions\EmailAlreadyExistsException;
use App\Domains\IAM\Infrastructure\Models\UserModel;
use App\Domains\IAM\Repositories\TenantRepositoryInterface;
use App\Domains\IAM\Repositories\UserRepositoryInterface;
use App\Domains\IAM\ValueObjects\Email;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantRegistrationService
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly TokenService $tokenService,
        private readonly AuditService $auditService
    ) {}

    public function register(RegisterTenantInputDTO $dto): RegisterTenantOutputDTO
    {
        $ownerEmail = Email::fromString($dto->ownerEmail);

        if ($this->userRepository->findByEmail($ownerEmail) !== null) {
            throw EmailAlreadyExistsException::forEmail($dto->ownerEmail);
        }

        $slug = Str::slug($dto->organizationName);
        if ($this->tenantRepository->findBySlug($slug) !== null) {
            $slug .= '-'.Str::random(4);
        }

        return DB::transaction(function () use ($dto, $ownerEmail, $slug) {
            $tenant = Tenant::create($dto->organizationName, $slug);
            $this->tenantRepository->save($tenant);

            $hashedPassword = Hash::make($dto->password);
            $role = $dto->role !== null ? UserRole::from($dto->role) : UserRole::OWNER;

            $owner = User::create(
                $tenant->getId(),
                $dto->ownerName,
                $ownerEmail,
                $hashedPassword,
                $role
            );

            $this->userRepository->save($owner);

            $eloquentUser = UserModel::findOrFail($owner->getId()->toString());
            $tokens = $this->tokenService->generateTokenPair($eloquentUser);

            Event::dispatch(new TenantCreatedEvent($tenant->getId(), $owner->getId()));

            $this->auditService->log(
                AuditEvent::REGISTER,
                $owner->getId()->toString(),
                request()->ip(),
                request()->userAgent(),
                ['organization' => $dto->organizationName]
            );

            return new RegisterTenantOutputDTO(
                tenantId: $tenant->getId()->toString(),
                organizationName: $tenant->getName(),
                slug: $tenant->getSlug(),
                ownerUserId: $owner->getId()->toString(),
                ownerName: $owner->getName(),
                ownerEmail: $owner->getEmail()->toString(),
                token: $tokens['access_token'],
                refreshToken: $tokens['refresh_token']
            );
        });
    }
}


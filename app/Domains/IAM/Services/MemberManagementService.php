<?php

declare(strict_types=1);

namespace App\Domains\IAM\Services;

use App\Domains\IAM\DTOs\AddMemberInputDTO;
use App\Domains\IAM\DTOs\MemberOutputDTO;
use App\Domains\IAM\Entities\User;
use App\Domains\IAM\Enums\UserRole;
use App\Domains\IAM\Events\MemberAddedEvent;
use App\Domains\IAM\Exceptions\EmailAlreadyExistsException;
use App\Domains\IAM\Exceptions\TenantNotFoundException;
use App\Domains\IAM\Repositories\TenantRepositoryInterface;
use App\Domains\IAM\Repositories\UserRepositoryInterface;
use App\Domains\IAM\ValueObjects\Email;
use App\Domains\IAM\ValueObjects\TenantId;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;

use App\Domains\IAM\ValueObjects\UserId;

class MemberManagementService
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository,
        private readonly UserRepositoryInterface $userRepository
    ) {}

    public function addMember(AddMemberInputDTO $dto): MemberOutputDTO
    {
        $tenantId = TenantId::fromString($dto->tenantId);
        $tenant = $this->tenantRepository->findById($tenantId);
        if (! $tenant) {
            throw TenantNotFoundException::forId($dto->tenantId);
        }

        $email = Email::fromString($dto->memberEmail);
        if ($this->userRepository->findByEmail($email) !== null) {
            throw EmailAlreadyExistsException::forEmail($dto->memberEmail);
        }

        $role = UserRole::from($dto->role);

        return DB::transaction(function () use ($tenantId, $dto, $email, $role) {
            $hashedPassword = Hash::make($dto->password);
            $member = User::create(
                $tenantId,
                $dto->memberName,
                $email,
                $hashedPassword,
                $role
            );

            $this->userRepository->save($member);

            Event::dispatch(new MemberAddedEvent($tenantId, $member->getId()));

            return new MemberOutputDTO(
                userId: $member->getId()->toString(),
                tenantId: $tenantId->toString(),
                name: $member->getName(),
                email: $member->getEmail()->toString(),
                role: $member->getRole()->value,
                createdAt: $member->getCreatedAt()->format(DATE_ATOM)
            );
        });
    }

    /**
     * @return array<int, MemberOutputDTO>
     */
    public function getMembers(TenantId $tenantId): array
    {
        $users = $this->userRepository->findByTenantId($tenantId);

        return array_map(
            fn (User $user) => $this->toDTO($user),
            $users
        );
    }

    /**
     * @return array<int, MemberOutputDTO>
     */
    public function getAllMembers(): array
    {
        $users = $this->userRepository->findAll();

        return array_map(
            fn (User $user) => $this->toDTO($user),
            $users
        );
    }

    public function getMemberById(UserId $userId): ?MemberOutputDTO
    {
        $user = $this->userRepository->findById($userId);
        if (! $user) {
            return null;
        }

        return $this->toDTO($user);
    }

    public function updateMember(UserId $userId, array $data): MemberOutputDTO
    {
        $user = $this->userRepository->findById($userId);
        if (! $user) {
            throw new \RuntimeException("Membre introuvable.");
        }

        if (isset($data['name']) || isset($data['email'])) {
            $name = $data['name'] ?? $user->getName();
            $email = isset($data['email']) ? Email::fromString($data['email']) : $user->getEmail();
            
            if (isset($data['email']) && $data['email'] !== $user->getEmail()->toString()) {
                if ($this->userRepository->findByEmail($email) !== null) {
                    throw EmailAlreadyExistsException::forEmail($data['email']);
                }
            }
            $user->updateProfile($name, $email);
        }

        if (isset($data['role'])) {
            $user->changeRole(UserRole::from($data['role']));
        }

        if (isset($data['password']) && ! empty($data['password'])) {
            $user->updatePassword(Hash::make($data['password']));
        }

        $this->userRepository->save($user);

        return $this->toDTO($user);
    }

    public function deleteMember(UserId $userId): void
    {
        $this->userRepository->delete($userId);
    }

    private function toDTO(User $user): MemberOutputDTO
    {
        return new MemberOutputDTO(
            userId: $user->getId()->toString(),
            tenantId: $user->getTenantId()->toString(),
            name: $user->getName(),
            email: $user->getEmail()->toString(),
            role: $user->getRole()->value,
            createdAt: $user->getCreatedAt()->format(DATE_ATOM)
        );
    }
}

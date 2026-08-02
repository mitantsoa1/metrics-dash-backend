<?php

declare(strict_types=1);

namespace App\Domains\IAM\Entities;

use App\Domains\IAM\Enums\UserRole;
use App\Domains\IAM\ValueObjects\Email;
use App\Domains\IAM\ValueObjects\TenantId;
use App\Domains\IAM\ValueObjects\UserId;
use DateTimeImmutable;

class User
{
    public function __construct(
        private readonly UserId $id,
        private readonly TenantId $tenantId,
        private string $name,
        private Email $email,
        private string $passwordHash,
        private UserRole $role,
        private ?DateTimeImmutable $emailVerifiedAt,
        private readonly DateTimeImmutable $createdAt
    ) {}

    public static function create(
        TenantId $tenantId,
        string $name,
        Email $email,
        string $passwordHash,
        UserRole $role,
        ?DateTimeImmutable $emailVerifiedAt = null
    ): self {
        return new self(
            UserId::generate(),
            $tenantId,
            $name,
            $email,
            $passwordHash,
            $role,
            $emailVerifiedAt,
            new DateTimeImmutable
        );
    }

    public function getId(): UserId
    {
        return $this->id;
    }

    public function getTenantId(): TenantId
    {
        return $this->tenantId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function getRole(): UserRole
    {
        return $this->role;
    }

    public function getEmailVerifiedAt(): ?DateTimeImmutable
    {
        return $this->emailVerifiedAt;
    }

    public function isEmailVerified(): bool
    {
        return $this->emailVerifiedAt !== null;
    }

    public function markEmailAsVerified(): void
    {
        $this->emailVerifiedAt = new DateTimeImmutable;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function changeRole(UserRole $newRole): void
    {
        $this->role = $newRole;
    }

    public function updatePassword(string $newPasswordHash): void
    {
        $this->passwordHash = $newPasswordHash;
    }
}

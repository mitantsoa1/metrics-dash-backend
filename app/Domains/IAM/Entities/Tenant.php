<?php

declare(strict_types=1);

namespace App\Domains\IAM\Entities;

use App\Domains\IAM\Enums\TenantStatus;
use App\Domains\IAM\ValueObjects\TenantId;
use DateTimeImmutable;

class Tenant
{
    public function __construct(
        private readonly TenantId $id,
        private string $name,
        private string $slug,
        private TenantStatus $status,
        private readonly DateTimeImmutable $createdAt
    ) {}

    public static function create(string $name, string $slug): self
    {
        return new self(
            TenantId::generate(),
            $name,
            $slug,
            TenantStatus::ACTIVE,
            new DateTimeImmutable
        );
    }

    public function getId(): TenantId
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getStatus(): TenantStatus
    {
        return $this->status;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updateName(string $newName): void
    {
        $this->name = $newName;
    }

    public function suspend(): void
    {
        $this->status = TenantStatus::SUSPENDED;
    }

    public function activate(): void
    {
        $this->status = TenantStatus::ACTIVE;
    }
}

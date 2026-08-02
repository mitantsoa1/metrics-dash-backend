<?php

declare(strict_types=1);

namespace App\Domains\Instance\Entities;

use App\Domains\IAM\ValueObjects\TenantId;
use App\Domains\Instance\Enums\Environment;
use App\Domains\Instance\Enums\InstanceStatus;
use App\Domains\Instance\ValueObjects\DomainName;
use App\Domains\Instance\ValueObjects\InstanceId;
use DateTimeImmutable;

class Instance
{
    public function __construct(
        private readonly InstanceId $id,
        private readonly TenantId $tenantId,
        private string $name,
        private DomainName $domain,
        private Environment $environment,
        private InstanceStatus $status,
        private readonly DateTimeImmutable $createdAt
    ) {}

    public static function create(
        TenantId $tenantId,
        string $name,
        DomainName $domain,
        Environment $environment
    ): self {
        return new self(
            InstanceId::generate(),
            $tenantId,
            $name,
            $domain,
            $environment,
            InstanceStatus::ACTIVE,
            new DateTimeImmutable
        );
    }

    public function getId(): InstanceId
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

    public function getDomain(): DomainName
    {
        return $this->domain;
    }

    public function getEnvironment(): Environment
    {
        return $this->environment;
    }

    public function getStatus(): InstanceStatus
    {
        return $this->status;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updateDetails(string $name, DomainName $domain): void
    {
        $this->name = $name;
        $this->domain = $domain;
    }

    public function disable(): void
    {
        $this->status = InstanceStatus::DISABLED;
    }

    public function enable(): void
    {
        $this->status = InstanceStatus::ACTIVE;
    }
}

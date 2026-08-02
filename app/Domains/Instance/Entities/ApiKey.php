<?php

declare(strict_types=1);

namespace App\Domains\Instance\Entities;

use App\Domains\Instance\Enums\ApiKeyType;
use App\Domains\Instance\ValueObjects\ApiKeyId;
use App\Domains\Instance\ValueObjects\InstanceId;
use DateTimeImmutable;

class ApiKey
{
    public function __construct(
        private readonly ApiKeyId $id,
        private readonly InstanceId $instanceId,
        private readonly ApiKeyType $type,
        private readonly string $keyHash,
        private readonly string $prefix,
        private ?DateTimeImmutable $lastUsedAt,
        private readonly DateTimeImmutable $createdAt
    ) {}

    public static function create(
        InstanceId $instanceId,
        ApiKeyType $type,
        string $plainKey
    ): self {
        $prefix = $type->getPrefix();
        $keyHash = hash('sha256', $plainKey);

        return new self(
            ApiKeyId::generate(),
            $instanceId,
            $type,
            $keyHash,
            $prefix,
            null,
            new DateTimeImmutable
        );
    }

    public function getId(): ApiKeyId
    {
        return $this->id;
    }

    public function getInstanceId(): InstanceId
    {
        return $this->instanceId;
    }

    public function getType(): ApiKeyType
    {
        return $this->type;
    }

    public function getKeyHash(): string
    {
        return $this->keyHash;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function getLastUsedAt(): ?DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function recordUsage(): void
    {
        $this->lastUsedAt = new DateTimeImmutable;
    }
}

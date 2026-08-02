<?php

declare(strict_types=1);

namespace App\Domains\Instance\Repositories;

use App\Domains\Instance\Entities\ApiKey;
use App\Domains\Instance\Enums\ApiKeyType;
use App\Domains\Instance\ValueObjects\ApiKeyId;
use App\Domains\Instance\ValueObjects\InstanceId;

interface ApiKeyRepositoryInterface
{
    public function findById(ApiKeyId $id): ?ApiKey;

    public function findByKeyHash(string $keyHash): ?ApiKey;

    public function findByInstanceAndType(InstanceId $instanceId, ApiKeyType $type): ?ApiKey;

    /**
     * @return array<int, ApiKey>
     */
    public function findByInstanceId(InstanceId $instanceId): array;

    public function save(ApiKey $apiKey): void;

    public function delete(ApiKeyId $id): void;

    public function deleteByInstanceAndType(InstanceId $instanceId, ApiKeyType $type): void;
}

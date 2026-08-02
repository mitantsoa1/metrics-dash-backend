<?php

declare(strict_types=1);

namespace App\Domains\Instance\Infrastructure\Repositories;

use App\Domains\Instance\Entities\ApiKey;
use App\Domains\Instance\Enums\ApiKeyType;
use App\Domains\Instance\Infrastructure\Models\ApiKeyModel;
use App\Domains\Instance\Repositories\ApiKeyRepositoryInterface;
use App\Domains\Instance\ValueObjects\ApiKeyId;
use App\Domains\Instance\ValueObjects\InstanceId;
use DateTimeImmutable;

class EloquentApiKeyRepository implements ApiKeyRepositoryInterface
{
    public function findById(ApiKeyId $id): ?ApiKey
    {
        $model = ApiKeyModel::find($id->toString());
        if (! $model) {
            return null;
        }

        return $this->toDomain($model);
    }

    public function findByKeyHash(string $keyHash): ?ApiKey
    {
        $model = ApiKeyModel::where('key_hash', $keyHash)->first();
        if (! $model) {
            return null;
        }

        return $this->toDomain($model);
    }

    public function findByInstanceAndType(InstanceId $instanceId, ApiKeyType $type): ?ApiKey
    {
        $model = ApiKeyModel::where('instance_id', $instanceId->toString())
            ->where('type', $type->value)
            ->first();

        if (! $model) {
            return null;
        }

        return $this->toDomain($model);
    }

    public function findByInstanceId(InstanceId $instanceId): array
    {
        $models = ApiKeyModel::where('instance_id', $instanceId->toString())->get();

        return $models->map(fn (ApiKeyModel $model) => $this->toDomain($model))->all();
    }

    public function save(ApiKey $apiKey): void
    {
        ApiKeyModel::updateOrCreate(
            ['id' => $apiKey->getId()->toString()],
            [
                'instance_id' => $apiKey->getInstanceId()->toString(),
                'type' => $apiKey->getType()->value,
                'key_hash' => $apiKey->getKeyHash(),
                'prefix' => $apiKey->getPrefix(),
                'last_used_at' => $apiKey->getLastUsedAt()?->format('Y-m-d H:i:s'),
            ]
        );
    }

    public function delete(ApiKeyId $id): void
    {
        ApiKeyModel::destroy($id->toString());
    }

    public function deleteByInstanceAndType(InstanceId $instanceId, ApiKeyType $type): void
    {
        ApiKeyModel::where('instance_id', $instanceId->toString())
            ->where('type', $type->value)
            ->delete();
    }

    private function toDomain(ApiKeyModel $model): ApiKey
    {
        return new ApiKey(
            ApiKeyId::fromString($model->id),
            InstanceId::fromString($model->instance_id),
            ApiKeyType::from($model->type),
            $model->key_hash,
            $model->prefix,
            $model->last_used_at ? new DateTimeImmutable($model->last_used_at->toDateTimeString()) : null,
            new DateTimeImmutable($model->created_at->toDateTimeString())
        );
    }
}

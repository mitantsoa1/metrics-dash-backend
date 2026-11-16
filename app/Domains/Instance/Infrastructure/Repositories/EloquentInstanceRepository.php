<?php

declare(strict_types=1);

namespace App\Domains\Instance\Infrastructure\Repositories;

use App\Domains\IAM\ValueObjects\TenantId;
use App\Domains\Instance\Entities\Instance;
use App\Domains\Instance\Enums\Environment;
use App\Domains\Instance\Enums\InstanceStatus;
use App\Domains\Instance\Infrastructure\Models\InstanceModel;
use App\Domains\Instance\Repositories\InstanceRepositoryInterface;
use App\Domains\Instance\ValueObjects\DomainName;
use App\Domains\Instance\ValueObjects\InstanceId;
use DateTimeImmutable;

class EloquentInstanceRepository implements InstanceRepositoryInterface
{
    public function findById(InstanceId $id): ?Instance
    {
        $model = InstanceModel::find($id->toString());
        if (! $model) {
            return null;
        }

        return $this->toDomain($model);
    }

    public function findByTenantId(TenantId $tenantId): array
    {
        $models = InstanceModel::where('tenant_id', $tenantId->toString())->get();

        return $models->map(fn (InstanceModel $model) => $this->toDomain($model))->all();
    }

    public function findAll(): array
    {
        $models = InstanceModel::all();

        return $models->map(fn (InstanceModel $model) => $this->toDomain($model))->all();
    }

    public function save(Instance $instance): void
    {
        InstanceModel::updateOrCreate(
            ['id' => $instance->getId()->toString()],
            [
                'tenant_id' => $instance->getTenantId()->toString(),
                'name' => $instance->getName(),
                'domain' => $instance->getDomain()->toString(),
                'environment' => $instance->getEnvironment()->value,
                'status' => $instance->getStatus()->value,
            ]
        );
    }

    public function delete(InstanceId $id): void
    {
        InstanceModel::destroy($id->toString());
    }

    private function toDomain(InstanceModel $model): Instance
    {
        return new Instance(
            InstanceId::fromString($model->id),
            TenantId::fromString($model->tenant_id),
            $model->name,
            DomainName::fromString($model->domain),
            Environment::from($model->environment),
            InstanceStatus::from($model->status),
            new DateTimeImmutable($model->created_at->toDateTimeString())
        );
    }
}

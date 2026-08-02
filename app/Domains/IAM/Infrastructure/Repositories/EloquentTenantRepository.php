<?php

declare(strict_types=1);

namespace App\Domains\IAM\Infrastructure\Repositories;

use App\Domains\IAM\Entities\Tenant;
use App\Domains\IAM\Enums\TenantStatus;
use App\Domains\IAM\Infrastructure\Models\TenantModel;
use App\Domains\IAM\Repositories\TenantRepositoryInterface;
use App\Domains\IAM\ValueObjects\TenantId;
use DateTimeImmutable;

class EloquentTenantRepository implements TenantRepositoryInterface
{
    public function findById(TenantId $id): ?Tenant
    {
        $model = TenantModel::find($id->toString());
        if (! $model) {
            return null;
        }

        return $this->toDomain($model);
    }

    public function findBySlug(string $slug): ?Tenant
    {
        $model = TenantModel::where('slug', $slug)->first();
        if (! $model) {
            return null;
        }

        return $this->toDomain($model);
    }

    public function save(Tenant $tenant): void
    {
        TenantModel::updateOrCreate(
            ['id' => $tenant->getId()->toString()],
            [
                'name' => $tenant->getName(),
                'slug' => $tenant->getSlug(),
                'status' => $tenant->getStatus()->value,
            ]
        );
    }

    public function delete(TenantId $id): void
    {
        TenantModel::destroy($id->toString());
    }

    private function toDomain(TenantModel $model): Tenant
    {
        return new Tenant(
            TenantId::fromString($model->id),
            $model->name,
            $model->slug,
            TenantStatus::from($model->status),
            new DateTimeImmutable($model->created_at->toDateTimeString())
        );
    }
}

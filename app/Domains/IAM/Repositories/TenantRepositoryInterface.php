<?php

declare(strict_types=1);

namespace App\Domains\IAM\Repositories;

use App\Domains\IAM\Entities\Tenant;
use App\Domains\IAM\ValueObjects\TenantId;

interface TenantRepositoryInterface
{
    public function findById(TenantId $id): ?Tenant;

    public function findBySlug(string $slug): ?Tenant;

    public function save(Tenant $tenant): void;

    public function delete(TenantId $id): void;
}

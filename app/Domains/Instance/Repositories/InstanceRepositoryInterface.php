<?php

declare(strict_types=1);

namespace App\Domains\Instance\Repositories;

use App\Domains\IAM\ValueObjects\TenantId;
use App\Domains\Instance\Entities\Instance;
use App\Domains\Instance\ValueObjects\InstanceId;

interface InstanceRepositoryInterface
{
    public function findById(InstanceId $id): ?Instance;

    /**
     * @return array<int, Instance>
     */
    public function findByTenantId(TenantId $tenantId): array;

    /**
     * @return array<int, Instance>
     */
    public function findAll(): array;

    public function save(Instance $instance): void;

    public function delete(InstanceId $id): void;
}

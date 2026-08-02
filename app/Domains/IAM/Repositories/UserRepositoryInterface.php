<?php

declare(strict_types=1);

namespace App\Domains\IAM\Repositories;

use App\Domains\IAM\Entities\User;
use App\Domains\IAM\ValueObjects\Email;
use App\Domains\IAM\ValueObjects\TenantId;
use App\Domains\IAM\ValueObjects\UserId;

interface UserRepositoryInterface
{
    public function findById(UserId $id): ?User;

    public function findByEmail(Email $email): ?User;

    /**
     * @return array<int, User>
     */
    public function findByTenantId(TenantId $tenantId): array;

    public function save(User $user): void;

    public function delete(UserId $id): void;
}

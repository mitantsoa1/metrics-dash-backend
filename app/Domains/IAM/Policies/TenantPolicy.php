<?php

declare(strict_types=1);

namespace App\Domains\IAM\Policies;

use App\Domains\IAM\Enums\UserRole;
use App\Domains\IAM\Infrastructure\Models\TenantModel;
use App\Domains\IAM\Infrastructure\Models\UserModel;

class TenantPolicy
{
    public function view(UserModel $user, TenantModel $tenant): bool
    {
        if (in_array($user->role, [UserRole::SUPERADMIN->value, UserRole::ADMIN->value], true)) {
            return true;
        }

        return $user->tenant_id === $tenant->id;
    }

    public function manageMembers(UserModel $user, TenantModel $tenant): bool
    {
        if (in_array($user->role, [UserRole::SUPERADMIN->value, UserRole::ADMIN->value], true)) {
            return true;
        }

        if ($user->tenant_id !== $tenant->id) {
            return false;
        }

        $role = UserRole::tryFrom($user->role);

        return $role !== null && $role->canManageMembers();
    }

    public function update(UserModel $user, TenantModel $tenant): bool
    {
        if (in_array($user->role, [UserRole::SUPERADMIN->value, UserRole::ADMIN->value], true)) {
            return true;
        }

        if ($user->tenant_id !== $tenant->id) {
            return false;
        }

        $role = UserRole::tryFrom($user->role);

        return $role !== null && $role->canManageInstance();
    }

    public function delete(UserModel $user, TenantModel $tenant): bool
    {
        if (in_array($user->role, [UserRole::SUPERADMIN->value, UserRole::ADMIN->value], true)) {
            return true;
        }

        return $user->tenant_id === $tenant->id && $user->role === UserRole::OWNER->value;
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\Instance\Policies;

use App\Domains\IAM\Enums\UserRole;
use App\Domains\IAM\Infrastructure\Models\UserModel;
use App\Domains\Instance\Infrastructure\Models\InstanceModel;

class InstancePolicy
{
    public function view(UserModel $user, InstanceModel $instance): bool
    {
        if (in_array($user->role, [UserRole::SUPERADMIN->value, UserRole::ADMIN->value], true)) {
            return true;
        }

        return $user->tenant_id === $instance->tenant_id;
    }

    public function create(UserModel $user): bool
    {
        $role = UserRole::tryFrom($user->role);

        return $role !== null && $role->canManageInstance();
    }

    public function update(UserModel $user, InstanceModel $instance): bool
    {
        if (in_array($user->role, [UserRole::SUPERADMIN->value, UserRole::ADMIN->value], true)) {
            return true;
        }

        if ($user->tenant_id !== $instance->tenant_id) {
            return false;
        }

        $role = UserRole::tryFrom($user->role);

        return $role !== null && $role->canManageInstance();
    }

    public function delete(UserModel $user, InstanceModel $instance): bool
    {
        if (in_array($user->role, [UserRole::SUPERADMIN->value, UserRole::ADMIN->value], true)) {
            return true;
        }

        if ($user->tenant_id !== $instance->tenant_id) {
            return false;
        }

        $role = UserRole::tryFrom($user->role);

        return $role !== null && $role->canManageInstance();
    }

    public function rotateKeys(UserModel $user, InstanceModel $instance): bool
    {
        if (in_array($user->role, [UserRole::SUPERADMIN->value, UserRole::ADMIN->value], true)) {
            return true;
        }

        if ($user->tenant_id !== $instance->tenant_id) {
            return false;
        }

        $role = UserRole::tryFrom($user->role);

        return $role !== null && $role->canRotateKeys();
    }
}

<?php

declare(strict_types=1);

namespace App\Support;

use App\Domains\IAM\Enums\UserRole;
use App\Domains\Instance\Infrastructure\Models\InstanceModel;
use Illuminate\Database\Eloquent\Builder;

/**
 * Garde-fous multi-tenant / RBAC partagés par les contrôleurs hors API Platform.
 * Règle alignée sur InstancePolicy : SUPERADMIN et ADMIN voient tous les tenants.
 */
trait AuthorizesTenantAccess
{
    protected function isGlobalUser(): bool
    {
        $user = request()->user();

        return $user && in_array($user->role, [UserRole::SUPERADMIN->value, UserRole::ADMIN->value], true);
    }

    protected function scopeToTenant(Builder $query): Builder
    {
        if (! $this->isGlobalUser()) {
            $query->where('tenant_id', request()->user()?->tenant_id ?? '');
        }

        return $query;
    }

    protected function requireManager(): void
    {
        $role = UserRole::tryFrom((string) request()->user()?->role);
        abort_unless($role?->canManageInstance(), 403, 'Action réservée aux rôles OWNER, ADMIN et SUPERADMIN.');
    }

    protected function assertInstanceAccessible(?string $instanceId): void
    {
        if ($instanceId === null || $instanceId === '') {
            abort_unless($this->isGlobalUser(), 422, 'instance_id est requis.');

            return;
        }

        $instance = InstanceModel::find($instanceId);
        abort_unless(
            $instance && ($this->isGlobalUser() || $instance->tenant_id === request()->user()?->tenant_id),
            404,
            'Instance introuvable.'
        );
    }
}

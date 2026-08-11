<?php

declare(strict_types=1);

namespace App\Domains\IAM\Infrastructure\Repositories;

use App\Domains\IAM\Entities\User;
use App\Domains\IAM\Enums\UserRole;
use App\Domains\IAM\Infrastructure\Models\UserModel;
use App\Domains\IAM\Repositories\UserRepositoryInterface;
use App\Domains\IAM\ValueObjects\Email;
use App\Domains\IAM\ValueObjects\TenantId;
use App\Domains\IAM\ValueObjects\UserId;
use DateTimeImmutable;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function findById(UserId $id): ?User
    {
        $model = UserModel::find($id->toString());
        if (! $model) {
            return null;
        }

        return $this->toDomain($model);
    }

    public function findByEmail(Email $email): ?User
    {
        $model = UserModel::where('email', $email->toString())->first();
        if (! $model) {
            return null;
        }

        return $this->toDomain($model);
    }

    public function findByTenantId(TenantId $tenantId): array
    {
        $models = UserModel::where('tenant_id', $tenantId->toString())->get();

        return $models->map(fn (UserModel $model) => $this->toDomain($model))->all();
    }

    public function findAll(): array
    {
        $models = UserModel::all();

        return $models->map(fn (UserModel $model) => $this->toDomain($model))->all();
    }

    public function save(User $user): void
    {
        UserModel::updateOrCreate(
            ['id' => $user->getId()->toString()],
            [
                'tenant_id' => $user->getTenantId()->toString(),
                'name' => $user->getName(),
                'email' => $user->getEmail()->toString(),
                'password' => $user->getPasswordHash(),
                'email_verified_at' => $user->getEmailVerifiedAt()?->format('Y-m-d H:i:s'),
                'role' => $user->getRole()->value,
            ]
        );
    }

    public function delete(UserId $id): void
    {
        UserModel::destroy($id->toString());
    }

    private function toDomain(UserModel $model): User
    {
        return new User(
            UserId::fromString($model->id),
            TenantId::fromString($model->tenant_id),
            $model->name,
            Email::fromString($model->email),
            $model->password,
            UserRole::from($model->role),
            $model->email_verified_at ? new DateTimeImmutable($model->email_verified_at->toDateTimeString()) : null,
            new DateTimeImmutable($model->created_at->toDateTimeString())
        );
    }
}

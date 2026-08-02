<?php

declare(strict_types=1);

namespace App\Domains\IAM;

use App\Domains\IAM\Infrastructure\Repositories\EloquentTenantRepository;
use App\Domains\IAM\Infrastructure\Repositories\EloquentUserRepository;
use App\Domains\IAM\Repositories\TenantRepositoryInterface;
use App\Domains\IAM\Repositories\UserRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class IAMServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TenantRepositoryInterface::class, EloquentTenantRepository::class);
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
    }

    public function boot(): void {}
}

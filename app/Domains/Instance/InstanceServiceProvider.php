<?php

declare(strict_types=1);

namespace App\Domains\Instance;

use App\Domains\Instance\Infrastructure\Repositories\EloquentApiKeyRepository;
use App\Domains\Instance\Infrastructure\Repositories\EloquentInstanceRepository;
use App\Domains\Instance\Repositories\ApiKeyRepositoryInterface;
use App\Domains\Instance\Repositories\InstanceRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class InstanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(InstanceRepositoryInterface::class, EloquentInstanceRepository::class);
        $this->app->bind(ApiKeyRepositoryInterface::class, EloquentApiKeyRepository::class);
    }

    public function boot(): void {}
}

<?php

declare(strict_types=1);

namespace App\Domains\Ingestion;

use App\Domains\Ingestion\Infrastructure\Repositories\EloquentRawEventRepository;
use App\Domains\Ingestion\Repositories\RawEventRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class IngestionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(RawEventRepositoryInterface::class, EloquentRawEventRepository::class);
    }

    public function boot(): void {}
}

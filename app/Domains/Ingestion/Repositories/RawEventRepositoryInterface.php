<?php

declare(strict_types=1);

namespace App\Domains\Ingestion\Repositories;

use App\Domains\Ingestion\Entities\RawEvent;
use App\Domains\Ingestion\ValueObjects\EventId;

interface RawEventRepositoryInterface
{
    public function findById(EventId $id): ?RawEvent;

    public function save(RawEvent $rawEvent): void;

    public function countByInstanceId(string $instanceId): int;
}

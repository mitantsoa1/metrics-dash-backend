<?php

declare(strict_types=1);

namespace App\Domains\Ingestion\Actions;

use App\Domains\Ingestion\DTOs\IngestEventOutputDTO;
use App\Domains\Ingestion\DTOs\IngestPushEventInputDTO;
use App\Domains\Ingestion\Services\EventIngestionService;

class IngestPushEventAction
{
    public function __construct(
        private readonly EventIngestionService $ingestionService
    ) {}

    public function execute(IngestPushEventInputDTO $dto): IngestEventOutputDTO
    {
        return $this->ingestionService->ingestPushEvent($dto);
    }
}

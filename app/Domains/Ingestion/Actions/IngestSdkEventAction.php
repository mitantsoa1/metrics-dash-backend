<?php

declare(strict_types=1);

namespace App\Domains\Ingestion\Actions;

use App\Domains\Ingestion\DTOs\IngestEventOutputDTO;
use App\Domains\Ingestion\DTOs\IngestSdkEventInputDTO;
use App\Domains\Ingestion\Services\EventIngestionService;

class IngestSdkEventAction
{
    public function __construct(
        private readonly EventIngestionService $ingestionService
    ) {}

    public function execute(IngestSdkEventInputDTO $dto): IngestEventOutputDTO
    {
        return $this->ingestionService->ingestSdkEvent($dto);
    }
}

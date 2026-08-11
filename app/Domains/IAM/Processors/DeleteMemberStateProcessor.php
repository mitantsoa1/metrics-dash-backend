<?php

declare(strict_types=1);

namespace App\Domains\IAM\Processors;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Domains\IAM\Services\MemberManagementService;
use App\Domains\IAM\ValueObjects\UserId;

class DeleteMemberStateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly MemberManagementService $memberService
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        $userIdStr = $uriVariables['userId'] ?? null;
        if (! $userIdStr) {
            throw new \InvalidArgumentException("Identifiant utilisateur manquant.");
        }

        $this->memberService->deleteMember(UserId::fromString($userIdStr));
    }
}

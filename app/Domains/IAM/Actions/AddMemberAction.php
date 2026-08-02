<?php

declare(strict_types=1);

namespace App\Domains\IAM\Actions;

use App\Domains\IAM\DTOs\AddMemberInputDTO;
use App\Domains\IAM\DTOs\MemberOutputDTO;
use App\Domains\IAM\Services\MemberManagementService;

class AddMemberAction
{
    public function __construct(
        private readonly MemberManagementService $memberService
    ) {}

    public function execute(AddMemberInputDTO $dto): MemberOutputDTO
    {
        return $this->memberService->addMember($dto);
    }
}

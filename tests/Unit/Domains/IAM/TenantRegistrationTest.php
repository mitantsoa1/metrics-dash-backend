<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\IAM;

use App\Domains\IAM\DTOs\RegisterTenantInputDTO;
use App\Domains\IAM\Entities\Tenant;
use App\Domains\IAM\Entities\User;
use App\Domains\IAM\Exceptions\EmailAlreadyExistsException;
use App\Domains\IAM\Repositories\TenantRepositoryInterface;
use App\Domains\IAM\Repositories\UserRepositoryInterface;
use App\Domains\IAM\Services\TenantRegistrationService;
use PHPUnit\Framework\TestCase;

class TenantRegistrationTest extends TestCase
{
    public function test_can_instantiate_registration_service(): void
    {
        $tenantRepo = $this->createMock(TenantRepositoryInterface::class);
        $userRepo = $this->createMock(UserRepositoryInterface::class);

        $service = new TenantRegistrationService($tenantRepo, $userRepo);
        $this->assertInstanceOf(TenantRegistrationService::class, $service);
    }

    public function test_throws_exception_if_owner_email_exists(): void
    {
        $tenantRepo = $this->createMock(TenantRepositoryInterface::class);
        $userRepo = $this->createMock(UserRepositoryInterface::class);

        $existingUser = $this->createMock(User::class);
        $userRepo->method('findByEmail')
            ->willReturn($existingUser);

        $service = new TenantRegistrationService($tenantRepo, $userRepo);

        $this->expectException(EmailAlreadyExistsException::class);

        $service->register(new RegisterTenantInputDTO(
            organizationName: 'Acme Corp',
            ownerName: 'Alice',
            ownerEmail: 'alice@acme.com',
            password: 'Password123!'
        ));
    }
}

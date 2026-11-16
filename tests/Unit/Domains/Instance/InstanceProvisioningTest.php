<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Instance;

use App\Domains\IAM\Repositories\TenantRepositoryInterface;
use App\Domains\IAM\ValueObjects\TenantId;
use App\Domains\Instance\DTOs\CreateInstanceInputDTO;
use App\Domains\Instance\Entities\ApiKey;
use App\Domains\Instance\Entities\Instance;
use App\Domains\Instance\Enums\ApiKeyType;
use App\Domains\Instance\Exceptions\InvalidDomainException;
use App\Domains\Instance\Repositories\ApiKeyRepositoryInterface;
use App\Domains\Instance\Repositories\InstanceRepositoryInterface;
use App\Domains\Instance\Services\InstanceProvisioningService;
use App\Domains\Instance\ValueObjects\DomainName;
use PHPUnit\Framework\TestCase;

class InstanceProvisioningTest extends TestCase
{
    public function test_can_validate_fqdn_domain_name_with_https(): void
    {
        $domain = DomainName::fromString('https://app.myclient.com/analytics');
        $this->assertEquals('https://app.myclient.com', $domain->toString());
    }

    public function test_can_validate_domain_name_with_http(): void
    {
        $domain = DomainName::fromString('http://app.myclient.com');
        $this->assertEquals('http://app.myclient.com', $domain->toString());
    }

    public function test_defaults_to_https_when_scheme_omitted(): void
    {
        $domain = DomainName::fromString('app.myclient.com');
        $this->assertEquals('https://app.myclient.com', $domain->toString());
    }

    public function test_throws_exception_on_invalid_domain_name(): void
    {
        $this->expectException(InvalidDomainException::class);
        DomainName::fromString('invalid_domain_format!@@');
    }

    public function test_hashes_api_keys_with_sha256(): void
    {
        $plainKey = 'pk_live_1234567890abcdef1234567890abcdef';
        $instance = Instance::create(
            TenantId::generate(),
            'Test App',
            DomainName::fromString('my-app.com'),
            \App\Domains\Instance\Enums\Environment::PRODUCTION
        );

        $apiKey = ApiKey::create($instance->getId(), ApiKeyType::PUBLIC_SDK, $plainKey);

        $expectedHash = hash('sha256', $plainKey);
        $this->assertEquals($expectedHash, $apiKey->getKeyHash());
        $this->assertEquals('pk_live_', $apiKey->getPrefix());
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\Instance\Services;

use App\Domains\IAM\Repositories\TenantRepositoryInterface;
use App\Domains\IAM\ValueObjects\TenantId;
use App\Domains\Instance\DTOs\ApiKeyPairDTO;
use App\Domains\Instance\DTOs\CreateInstanceInputDTO;
use App\Domains\Instance\DTOs\CreateInstanceOutputDTO;
use App\Domains\Instance\DTOs\InstanceOutputDTO;
use App\Domains\Instance\Entities\ApiKey;
use App\Domains\Instance\Entities\Instance;
use App\Domains\Instance\Enums\ApiKeyType;
use App\Domains\Instance\Enums\Environment;
use App\Domains\Instance\Events\ApiKeyRotatedEvent;
use App\Domains\Instance\Events\InstanceCreatedEvent;
use App\Domains\Instance\Exceptions\InstanceNotFoundException;
use App\Domains\Instance\Repositories\ApiKeyRepositoryInterface;
use App\Domains\Instance\Repositories\InstanceRepositoryInterface;
use App\Domains\Instance\ValueObjects\DomainName;
use App\Domains\Instance\ValueObjects\InstanceId;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

class InstanceProvisioningService
{
    public function __construct(
        private readonly InstanceRepositoryInterface $instanceRepository,
        private readonly ApiKeyRepositoryInterface $apiKeyRepository,
        private readonly TenantRepositoryInterface $tenantRepository
    ) {}

    public function createInstance(CreateInstanceInputDTO $dto): CreateInstanceOutputDTO
    {
        $tenantId = TenantId::fromString($dto->tenantId);
        $domain = DomainName::fromString($dto->domain);
        $env = Environment::from($dto->environment);

        return DB::transaction(function () use ($tenantId, $dto, $domain, $env) {
            $instance = Instance::create($tenantId, $dto->name, $domain, $env);
            $this->instanceRepository->save($instance);

            // Génération des clés brutes en clair (32 chars entropy)
            $plainPublic = ApiKeyType::PUBLIC_SDK->getPrefix().Str::random(32);
            $plainPrivate = ApiKeyType::PRIVATE_PUSH->getPrefix().Str::random(32);

            $publicKey = ApiKey::create($instance->getId(), ApiKeyType::PUBLIC_SDK, $plainPublic);
            $privateKey = ApiKey::create($instance->getId(), ApiKeyType::PRIVATE_PUSH, $plainPrivate);

            $this->apiKeyRepository->save($publicKey);
            $this->apiKeyRepository->save($privateKey);

            Event::dispatch(new InstanceCreatedEvent($instance->getId(), $tenantId));

            return new CreateInstanceOutputDTO(
                id: $instance->getId()->toString(),
                tenantId: $tenantId->toString(),
                name: $instance->getName(),
                domain: $instance->getDomain()->toString(),
                environment: $instance->getEnvironment()->value,
                status: $instance->getStatus()->value,
                apiKeys: new ApiKeyPairDTO(
                    publicSdkKey: $plainPublic,
                    privatePushKey: $plainPrivate
                ),
                createdAt: $instance->getCreatedAt()->format(DATE_ATOM)
            );
        });
    }

    public function rotateApiKey(InstanceId $instanceId, ApiKeyType $type): string
    {
        $instance = $this->instanceRepository->findById($instanceId);
        if (! $instance) {
            throw InstanceNotFoundException::forId($instanceId->toString());
        }

        return DB::transaction(function () use ($instanceId, $type) {
            $this->apiKeyRepository->deleteByInstanceAndType($instanceId, $type);

            $newPlainKey = $type->getPrefix().Str::random(32);
            $newApiKey = ApiKey::create($instanceId, $type, $newPlainKey);

            $this->apiKeyRepository->save($newApiKey);

            Event::dispatch(new ApiKeyRotatedEvent($instanceId, $type));

            return $newPlainKey;
        });
    }

    /**
     * @return array<int, InstanceOutputDTO>
     */
    public function getInstancesByTenant(TenantId $tenantId): array
    {
        $instances = $this->instanceRepository->findByTenantId($tenantId);

        return array_map(function (Instance $instance) {
            $public = $this->apiKeyRepository->findByInstanceAndType($instance->getId(), ApiKeyType::PUBLIC_SDK);
            $private = $this->apiKeyRepository->findByInstanceAndType($instance->getId(), ApiKeyType::PRIVATE_PUSH);

            return new InstanceOutputDTO(
                id: $instance->getId()->toString(),
                tenantId: $instance->getTenantId()->toString(),
                name: $instance->getName(),
                domain: $instance->getDomain()->toString(),
                environment: $instance->getEnvironment()->value,
                status: $instance->getStatus()->value,
                publicSdkPrefix: $public?->getPrefix() ?? 'pk_live_',
                privatePushPrefix: $private?->getPrefix() ?? 'sk_live_',
                createdAt: $instance->getCreatedAt()->format(DATE_ATOM)
            );
        }, $instances);
    }
}

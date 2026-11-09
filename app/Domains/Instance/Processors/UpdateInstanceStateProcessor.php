<?php

declare(strict_types=1);

namespace App\Domains\Instance\Processors;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Domains\Instance\Infrastructure\Models\InstanceModel;
use App\Domains\Instance\Resources\InstanceResource;
use App\Domains\Instance\Policies\InstancePolicy;

class UpdateInstanceStateProcessor implements ProcessorInterface
{
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): InstanceResource
    {
        $id = $uriVariables['id'] ?? $data?->id ?? null;
        $req = request()?->json()?->all() ?? request()?->all() ?? [];

        $model = InstanceModel::findOrFail($id);
        abort_unless(app(InstancePolicy::class)->update(auth()->user(), $model), 403, 'Action non autorisée sur cette instance.');

        if (!empty($data?->name) || !empty($req['name'])) {
            $model->name = trim((string) ($data?->name ?? $req['name']));
        }

        if (!empty($data?->domain) || !empty($req['domain'])) {
            $model->domain = trim((string) ($data?->domain ?? $req['domain']));
        }

        if (!empty($data?->environment) || !empty($req['environment'])) {
            $model->environment = strtoupper(trim((string) ($data?->environment ?? $req['environment'])));
        }

        if (!empty($data?->status) || !empty($req['status'])) {
            $model->status = strtoupper(trim((string) ($data?->status ?? $req['status'])));
        }

        $model->save();

        $resource = new InstanceResource();
        $resource->id = $model->id;
        $resource->tenantId = $model->tenant_id;
        $resource->name = $model->name;
        $resource->domain = $model->domain;
        $resource->environment = $model->environment;
        $resource->status = $model->status;
                $resource->createdAt = $model->created_at?->toIso8601String() ?? date('c');

        return $resource;
    }
}

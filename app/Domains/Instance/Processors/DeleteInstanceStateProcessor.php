<?php

declare(strict_types=1);

namespace App\Domains\Instance\Processors;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Domains\Instance\Infrastructure\Models\ApiKeyModel;
use App\Domains\Instance\Infrastructure\Models\InstanceModel;

class DeleteInstanceStateProcessor implements ProcessorInterface
{
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        $id = $uriVariables['id'] ?? $data?->id ?? null;
        if ($id) {
            $instance = InstanceModel::findOrFail($id);
            abort_unless(app(\App\Domains\Instance\Policies\InstancePolicy::class)->delete(auth()->user(), $instance), 403, 'Action non autorisée sur cette instance.');
            ApiKeyModel::where('instance_id', $id)->delete();
            InstanceModel::where('id', $id)->delete();
        }
    }
}

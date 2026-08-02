<?php

declare(strict_types=1);

namespace App\Domains\IAM\Exceptions;

use Exception;

class TenantNotFoundException extends Exception
{
    public static function forId(string $id): self
    {
        return new self("Tenant introuvable avec l'identifiant : {$id}");
    }

    public static function forSlug(string $slug): self
    {
        return new self("Tenant introuvable avec le slug : {$slug}");
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\Instance\Exceptions;

use Exception;

class InstanceNotFoundException extends Exception
{
    public static function forId(string $id): self
    {
        return new self("Instance introuvable avec l'identifiant : {$id}");
    }
}

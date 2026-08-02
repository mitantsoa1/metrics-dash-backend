<?php

declare(strict_types=1);

namespace App\Domains\Instance\Exceptions;

use Exception;

class ApiKeyNotFoundException extends Exception
{
    public static function forHash(string $hash): self
    {
        return new self("Clé d'API invalide ou révoquée.");
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\Ingestion\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class InvalidApiKeyException extends HttpException
{
    public static function forType(string $type): self
    {
        return new self(401, "Clé d'API {$type} invalide, expirée ou non autorisée.");
    }
}



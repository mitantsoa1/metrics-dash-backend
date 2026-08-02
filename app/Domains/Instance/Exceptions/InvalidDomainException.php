<?php

declare(strict_types=1);

namespace App\Domains\Instance\Exceptions;

use Exception;

class InvalidDomainException extends Exception
{
    public static function forDomain(string $domain): self
    {
        return new self("Format de domaine FQDN invalide : {$domain}");
    }
}

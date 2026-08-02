<?php

declare(strict_types=1);

namespace App\Domains\IAM\Exceptions;

use Exception;

class UnauthorizedTenantAccessException extends Exception
{
    public function __construct(string $message = 'Accès non autorisé aux ressources de cette organisation.')
    {
        parent::__construct($message);
    }
}

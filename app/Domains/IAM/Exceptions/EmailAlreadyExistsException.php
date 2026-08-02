<?php

declare(strict_types=1);

namespace App\Domains\IAM\Exceptions;

use Exception;

class EmailAlreadyExistsException extends Exception
{
    public static function forEmail(string $email): self
    {
        return new self("Un compte existe déjà avec l'adresse e-mail : {$email}");
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\IAM\Exceptions;

use Exception;

class UserNotFoundException extends Exception
{
    public static function forEmail(string $email): self
    {
        return new self("Utilisateur introuvable avec l'adresse e-mail : {$email}");
    }

    public static function forId(string $id): self
    {
        return new self("Utilisateur introuvable avec l'identifiant : {$id}");
    }
}

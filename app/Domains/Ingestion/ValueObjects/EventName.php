<?php

declare(strict_types=1);

namespace App\Domains\Ingestion\ValueObjects;

use InvalidArgumentException;

final readonly class EventName
{
    public function __construct(private string $value)
    {
        $trimmed = trim($value);
        if (empty($trimmed) || strlen($trimmed) > 64) {
            throw new InvalidArgumentException("Nom d'événement invalide. Doit faire entre 1 et 64 caractères.");
        }
    }

    public static function fromString(string $name): self
    {
        return new self($name);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(EventName $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

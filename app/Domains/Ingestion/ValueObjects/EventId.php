<?php

declare(strict_types=1);

namespace App\Domains\Ingestion\ValueObjects;

use InvalidArgumentException;
use Illuminate\Support\Str;

final readonly class EventId
{
    public function __construct(private string $value)
    {
        if (!Str::isUuid($value)) {
            throw new InvalidArgumentException("Format d'ID d'événement invalide : {$value}");
        }
    }

    public static function generate(): self
    {
        return new self(Str::uuid()->toString());
    }

    public static function fromString(string $id): self
    {
        return new self($id);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(EventId $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\Ingestion\ValueObjects;

final readonly class VisitorId
{
    public function __construct(private string $value) {}

    public static function fromString(string $visitorId): self
    {
        return new self($visitorId);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

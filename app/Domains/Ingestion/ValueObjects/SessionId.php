<?php

declare(strict_types=1);

namespace App\Domains\Ingestion\ValueObjects;

final readonly class SessionId
{
    public function __construct(private string $value) {}

    public static function fromString(string $sessionId): self
    {
        return new self($sessionId);
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

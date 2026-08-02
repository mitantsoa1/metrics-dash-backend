<?php

declare(strict_types=1);

namespace App\Domains\IAM\ValueObjects;

use Illuminate\Support\Str;

final readonly class UserId
{
    public function __construct(private string $value)
    {
        if (! Str::isUuid($this->value)) {
            throw new \InvalidArgumentException("Invalid UserId UUID: {$this->value}");
        }
    }

    public static function generate(): self
    {
        return new self((string) Str::uuid());
    }

    public static function fromString(string $id): self
    {
        return new self($id);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(UserId $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

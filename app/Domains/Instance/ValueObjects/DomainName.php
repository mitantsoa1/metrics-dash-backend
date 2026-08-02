<?php

declare(strict_types=1);

namespace App\Domains\Instance\ValueObjects;

use App\Domains\Instance\Exceptions\InvalidDomainException;

final readonly class DomainName
{
    private string $value;

    public function __construct(string $value)
    {
        $raw = trim($value);
        $host = parse_url($raw, PHP_URL_HOST);
        if (! is_string($host) || empty($host)) {
            $parsed = parse_url('http://'.$raw, PHP_URL_HOST);
            $host = is_string($parsed) ? $parsed : $raw;
        }

        $cleaned = strtolower(trim($host, '/'));

        if (empty($cleaned) || (! filter_var($cleaned, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) && $cleaned !== 'localhost')) {
            throw InvalidDomainException::forDomain($value);
        }

        $this->value = $cleaned;
    }

    public static function fromString(string $domain): self
    {
        return new self($domain);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(DomainName $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

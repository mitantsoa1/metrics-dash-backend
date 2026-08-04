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

        $parsed = parse_url($raw);
        if (! isset($parsed['scheme']) && ! isset($parsed['host'])) {
            $parsed = parse_url('http://'.$raw);
        }

        $scheme = isset($parsed['scheme']) ? strtolower($parsed['scheme']) : '';
        $host = isset($parsed['host']) ? strtolower($parsed['host']) : '';
        $port = isset($parsed['port']) ? ':'.$parsed['port'] : '';

        if (empty($host) || (! filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) && $host !== 'localhost' && ! filter_var($host, FILTER_VALIDATE_IP))) {
            throw InvalidDomainException::forDomain($value);
        }

        if (! empty($port)) {
            $this->value = ($scheme ? $scheme.'://' : '').$host.$port;
        } else {
            $this->value = $host;
        }
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

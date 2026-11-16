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

        if ($raw === '') {
            throw InvalidDomainException::forDomain($value);
        }

        // Check if an explicit scheme is provided (e.g. http:// or https://)
        $hasScheme = (bool) preg_match('/^[a-zA-Z][a-zA-Z0-9+\-.]*:\/\//', $raw);

        if ($hasScheme) {
            $parsed = parse_url($raw);
            $scheme = isset($parsed['scheme']) ? strtolower($parsed['scheme']) : '';
            if (! in_array($scheme, ['http', 'https'], true)) {
                throw InvalidDomainException::forDomain($value);
            }
        } else {
            $scheme = 'https';
            $parsed = parse_url('https://' . $raw);
        }

        $host = isset($parsed['host']) ? strtolower($parsed['host']) : '';
        $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';

        if (empty($host) || (! filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) && $host !== 'localhost' && ! filter_var($host, FILTER_VALIDATE_IP))) {
            throw InvalidDomainException::forDomain($value);
        }

        $this->value = $scheme . '://' . $host . $port;
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

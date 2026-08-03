<?php

declare(strict_types=1);

namespace App\Domains\Ingestion\Services;

class EventAnonymizationService
{
    /**
     * Anonymise une adresse IP conformément aux exigences du RGPD.
     * Masque le dernier octet pour IPv4 (/24) et les 80 derniers bits pour IPv6 (/48).
     */
    public function anonymizeIp(?string $ip): string
    {
        if (! $ip || $ip === '127.0.0.1' || $ip === '::1') {
            return '127.0.0.0';
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            $parts[3] = '0';
            return implode('.', $parts);
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $packed = inet_pton($ip);
            if ($packed !== false) {
                // Masque les 10 derniers octets (/48)
                $masked = substr($packed, 0, 6) . str_repeat("\x00", 10);
                return inet_ntop($masked) ?: '::';
            }
        }

        return '0.0.0.0';
    }

    /**
     * Génère un identifiant visiteur anonyme et salé quotidiennement.
     */
    public function generateAnonymousVisitorId(?string $rawVisitorId, string $ip, string $userAgent, ?string $dateSalt = null): string
    {
        $salt = $dateSalt ?? date('Y-m-d');
        $anonymizedIp = $this->anonymizeIp($ip);
        $fingerprint = ($rawVisitorId ?? 'anon') . '|' . $anonymizedIp . '|' . $userAgent . '|' . $salt;

        return 'vis_' . substr(hash('sha256', $fingerprint), 0, 16);
    }
}

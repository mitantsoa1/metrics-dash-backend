<?php

declare(strict_types=1);

namespace App\Domains\Ingestion\Services;

use App\Domains\Ingestion\Enums\DeviceType;

class UserAgentParserService
{
    /**
     * Analyse un User-Agent HTTP et extrait l'appareil, le navigateur et l'OS.
     *
     * @return array{device_type: string, browser: string, os: string}
     */
    public function parse(?string $userAgent): array
    {
        $ua = $userAgent ?? '';
        
        return [
            'device_type' => $this->detectDeviceType($ua)->value,
            'browser' => $this->detectBrowser($ua),
            'os' => $this->detectOs($ua),
        ];
    }

    public function detectDeviceType(string $ua): DeviceType
    {
        if ($ua === '') {
            return DeviceType::UNKNOWN;
        }

        // Dépistage de bots / crawlers
        if (preg_match('/(bot|crawler|spider|slurp|facebookexternalhit|Googlebot)/i', $ua)) {
            return DeviceType::BOT;
        }

        // Dépistage de tablettes
        if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobile))/i', $ua)) {
            return DeviceType::TABLET;
        }

        // Dépistage de mobiles
        if (preg_match('/(mobile|iphone|ipod|android|blackberry|IEMobile|Opera Mini)/i', $ua)) {
            return DeviceType::MOBILE;
        }

        return DeviceType::DESKTOP;
    }

    public function detectBrowser(string $ua): string
    {
        if (preg_match('/edg/i', $ua)) {
            return 'Edge';
        }
        if (preg_match('/firefox/i', $ua)) {
            return 'Firefox';
        }
        if (preg_match('/chrome/i', $ua)) {
            return 'Chrome';
        }
        if (preg_match('/safari/i', $ua)) {
            return 'Safari';
        }
        if (preg_match('/opera|opr/i', $ua)) {
            return 'Opera';
        }

        return 'Unknown';
    }

    public function detectOs(string $ua): string
    {
        if (preg_match('/iphone|ipad|ipod/i', $ua)) {
            return 'iOS';
        }
        if (preg_match('/android/i', $ua)) {
            return 'Android';
        }
        if (preg_match('/windows/i', $ua)) {
            return 'Windows';
        }
        if (preg_match('/macintosh|mac os x/i', $ua)) {
            return 'macOS';
        }
        if (preg_match('/linux/i', $ua)) {
            return 'Linux';
        }

        return 'Unknown';
    }

}

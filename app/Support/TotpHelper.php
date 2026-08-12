<?php

declare(strict_types=1);

namespace App\Support;

class TotpHelper
{
    private const BASE32_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Generate a random 16-character Base32 secret key.
     */
    public static function generateSecret(int $length = 16): string
    {
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= self::BASE32_CHARS[random_int(0, 31)];
        }
        return $secret;
    }

    /**
     * Verify a 6-digit TOTP code against a Base32 secret.
     */
    public static function verifyCode(string $secret, string $code, int $discrepancy = 1, int $timeStep = 30): bool
    {
        $currentTime = floor(time() / $timeStep);
        $code = trim($code);

        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $calculatedCode = self::calculateCode($secret, (int) ($currentTime + $i));
            if (hash_equals($calculatedCode, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calculate 6-digit TOTP code for a specific secret and time slice.
     */
    public static function calculateCode(string $secret, int $timeSlice): string
    {
        $secretKey = self::base32Decode($secret);
        $pack = pack('N*', 0) . pack('N*', $timeSlice);
        $hash = hash_hmac('sha1', $pack, $secretKey, true);
        $offset = ord($hash[strlen($hash) - 1]) & 0xF;
        $unpack = unpack('N', substr($hash, $offset, 4));
        $value = ($unpack[1] & 0x7FFFFFFF) % 1000000;

        return str_pad((string) $value, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Generate otpauth:// URI for authenticator QR codes.
     */
    public static function getOtpAuthUrl(string $label, string $secret, string $issuer = 'MetricsDash'): string
    {
        $encodedIssuer = rawurlencode($issuer);
        $encodedLabel = rawurlencode($label);
        return "otpauth://totp/{$encodedIssuer}:{$encodedLabel}?secret={$secret}&issuer={$encodedIssuer}";
    }

    /**
     * Generate 8 random recovery codes.
     *
     * @return array<int, string>
     */
    public static function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4))) . '-' . strtoupper(bin2hex(random_bytes(4)));
        }
        return $codes;
    }

    private static function base32Decode(string $secret): string
    {
        $secret = strtoupper($secret);
        $binary = '';

        for ($i = 0; $i < strlen($secret); $i++) {
            $position = strpos(self::BASE32_CHARS, $secret[$i]);
            if ($position === false) {
                continue;
            }
            $binary .= str_pad(decbin($position), 5, '0', STR_PAD_LEFT);
        }

        $bytes = [];
        for ($i = 0; $i < strlen($binary); $i += 8) {
            if ($i + 8 <= strlen($binary)) {
                $bytes[] = chr(bindec(substr($binary, $i, 8)));
            }
        }

        return implode('', $bytes);
    }
}

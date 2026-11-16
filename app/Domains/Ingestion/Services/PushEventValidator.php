<?php

declare(strict_types=1);

namespace App\Domains\Ingestion\Services;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Validation et normalisation du payload de l'API Push (PU-56).
 * Les champs inconnus sont conservés ; les erreurs sont structurées (champ, code, message) en HTTP 400.
 */
class PushEventValidator
{
    private const NAME_PATTERN = '/^[A-Za-z][A-Za-z0-9_.-]{0,63}$/';
    private const MAX_PAYLOAD_BYTES = 65536;
    private const MAX_DEPTH = 8;

    /**
     * @return array{eventName: string, occurredAt: ?DateTimeImmutable, eventData: array}
     */
    public function validate(?string $eventName, mixed $eventData, mixed $timestamp): array
    {
        $eventName = trim((string) $eventName);
        if ($eventName === '') {
            $this->fail('event_type', 'required', "Le champ 'event_type' est obligatoire.");
        }
        if (! preg_match(self::NAME_PATTERN, $eventName)) {
            $this->fail('event_type', 'invalid_format', "'event_type' doit commencer par une lettre et ne contenir que lettres, chiffres, '_', '.' ou '-' (64 caractères max).");
        }

        if ($eventData === null || $eventData === '') {
            $eventData = [];
        }
        if (! is_array($eventData)) {
            $this->fail('payload', 'invalid_type', "'payload' doit être un objet JSON.");
        }
        if (strlen((string) json_encode($eventData)) > self::MAX_PAYLOAD_BYTES) {
            $this->fail('payload', 'too_large', "'payload' dépasse 64 Ko.");
        }
        if ($this->depth($eventData) > self::MAX_DEPTH) {
            $this->fail('payload', 'too_deep', "'payload' dépasse ".self::MAX_DEPTH." niveaux d'imbrication.");
        }

        foreach (['amount', 'total', 'price'] as $field) {
            if (array_key_exists($field, $eventData)) {
                $raw = is_string($eventData[$field]) ? str_replace(',', '.', trim($eventData[$field])) : $eventData[$field];
                if (! is_numeric($raw) || ! is_finite((float) $raw) || (float) $raw < 0) {
                    $this->fail("payload.$field", 'invalid_amount', "'$field' doit être un nombre positif ou nul.");
                }
                $eventData[$field] = (float) $raw;
            }
        }

        return [
            'eventName' => $eventName,
            'occurredAt' => $this->normalizeTimestamp($timestamp),
            'eventData' => $eventData,
        ];
    }

    private function normalizeTimestamp(mixed $timestamp): ?DateTimeImmutable
    {
        if ($timestamp === null || $timestamp === '') {
            return null;
        }
        try {
            if (is_int($timestamp) || (is_string($timestamp) && ctype_digit($timestamp))) {
                $seconds = (int) $timestamp > 9999999999 ? intdiv((int) $timestamp, 1000) : (int) $timestamp;
                $date = new DateTimeImmutable('@'.$seconds);
            } else {
                $date = new DateTimeImmutable((string) $timestamp);
            }
        } catch (\Throwable) {
            $this->fail('timestamp', 'invalid_format', "'timestamp' n'est pas une date reconnue (ISO 8601 attendu).");
        }
        if ($date > new DateTimeImmutable('+5 minutes')) {
            $this->fail('timestamp', 'in_future', "'timestamp' ne peut pas être dans le futur.");
        }

        return $date->setTimezone(new DateTimeZone('UTC'));
    }

    private function depth(array $value): int
    {
        $max = 1;
        foreach ($value as $item) {
            if (is_array($item)) {
                $max = max($max, 1 + $this->depth($item));
            }
        }

        return $max;
    }

    private function fail(string $field, string $code, string $message): never
    {
        throw new HttpResponseException(response()->json([
            'error' => ['field' => $field, 'code' => $code, 'message' => $message],
        ], 400));
    }
}

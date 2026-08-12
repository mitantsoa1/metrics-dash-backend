<?php

declare(strict_types=1);

namespace App\Domains\IAM\Services;

use App\Domains\IAM\Enums\AuditEvent;
use App\Domains\IAM\Infrastructure\Models\AuditLogModel;

class AuditService
{
    public function log(
        AuditEvent|string $event,
        ?string $userId = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?array $payload = null
    ): AuditLogModel {
        $eventString = $event instanceof AuditEvent ? $event->value : $event;

        // Ensure sensitive data is never logged
        if ($payload !== null) {
            unset(
                $payload['password'],
                $payload['password_confirmation'],
                $payload['token'],
                $payload['access_token'],
                $payload['refresh_token'],
                $payload['two_factor_secret'],
                $payload['recovery_code']
            );
        }

        return AuditLogModel::create([
            'user_id' => $userId,
            'event' => $eventString,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'payload' => $payload,
        ]);
    }
}

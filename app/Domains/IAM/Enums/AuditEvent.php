<?php

declare(strict_types=1);

namespace App\Domains\IAM\Enums;

enum AuditEvent: string
{
    case LOGIN_SUCCESS = 'login_success';
    case LOGIN_FAILED = 'login_failed';
    case LOGOUT = 'logout';
    case LOGOUT_ALL = 'logout_all';
    case REGISTER = 'register';
    case PASSWORD_CHANGED = 'password_changed';
    case PASSWORD_RESET_REQUESTED = 'password_reset_requested';
    case PASSWORD_RESET = 'password_reset';
    case EMAIL_VERIFIED = 'email_verified';
    case EMAIL_VERIFICATION_RESENT = 'email_verification_resent';
    case TWO_FACTOR_ENABLED = 'two_factor_enabled';
    case TWO_FACTOR_DISABLED = 'two_factor_disabled';
    case TWO_FACTOR_VERIFIED = 'two_factor_verified';
    case TWO_FACTOR_FAILED = 'two_factor_failed';
    case SESSION_REVOKED = 'session_revoked';
    case REFRESH_TOKEN_ROTATED = 'refresh_token_rotated';
    case REFRESH_TOKEN_REUSE_DETECTED = 'refresh_token_reuse_detected';
    case GOOGLE_LOGIN = 'google_login';
}

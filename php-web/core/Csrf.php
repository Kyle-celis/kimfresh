<?php
// core/Csrf.php — framework-free CSRF protection

class Csrf
{
    const SESSION_KEY = '_csrf_token';

    public static function token(): string
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::SESSION_KEY];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function verify(?string $token): bool
    {
        if (empty($_SESSION[self::SESSION_KEY]) || empty($token)) return false;
        return hash_equals($_SESSION[self::SESSION_KEY], $token);
    }

    public static function rotate(): void
    {
        $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
    }
}

function csrf_token(): string { return Csrf::token(); }
function csrf_field(): string { return Csrf::field(); }
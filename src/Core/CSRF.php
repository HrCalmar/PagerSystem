<?php
// src/Core/CSRF.php
namespace App\Core;

class CSRF {
    public static function generate(): string {
        if (!Session::has('csrf_token')) {
            Session::set('csrf_token', bin2hex(random_bytes(32)));
        }
        return Session::get('csrf_token');
    }
    
    public static function verify(string $token): bool {
        return Session::has('csrf_token') && hash_equals(Session::get('csrf_token'), $token);
    }
    
    public static function field(): string {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(self::generate()) . '">';
    }
}
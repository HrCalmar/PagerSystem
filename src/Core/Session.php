<?php
// src/Core/Session.php - fix config path
namespace App\Core;

class Session {
    public static function start(): void {
        if (session_status() === PHP_SESSION_NONE) {
            $config = require dirname(__DIR__, 2) . '/config.php';
            $session = $config['session'];
            
            session_name($session['name']);
            session_set_cookie_params([
                'lifetime' => $session['lifetime'],
                'path' => $session['path'],
                'secure' => $session['secure'],
                'httponly' => $session['httponly'],
                'samesite' => $session['samesite']
            ]);
            
            session_start();
            
            if (!isset($_SESSION['initiated'])) {
                session_regenerate_id(true);
                $_SESSION['initiated'] = true;
            }
        }
    }
    
    public static function set(string $key, mixed $value): void {
        $_SESSION[$key] = $value;
    }
    
    public static function get(string $key, mixed $default = null): mixed {
        return $_SESSION[$key] ?? $default;
    }
    
    public static function has(string $key): bool {
        return isset($_SESSION[$key]);
    }
    
    public static function remove(string $key): void {
        unset($_SESSION[$key]);
    }
    
    public static function destroy(): void {
        session_unset();
        session_destroy();
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    public static function regenerate(): void {
        session_regenerate_id(true);
    }
}
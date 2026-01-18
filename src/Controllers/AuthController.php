<?php
// src/Controllers/AuthController.php - DEBUG version
namespace App\Controllers;

use App\Core\{Auth, CSRF};

class AuthController {
    public function showLogin(): void {
        if (Auth::check()) {
            header('Location: /dashboard');
            exit;
        }
        require __DIR__ . '/../../views/auth/login.php';
    }
    
    public function login(): void {
        // DEBUG: log hele POST
        error_log("LOGIN ATTEMPT - POST data: " . print_r($_POST, true));
        
        if (!isset($_POST['csrf_token']) || !CSRF::verify($_POST['csrf_token'])) {
            error_log("LOGIN FAILED: Invalid CSRF token");
            die('Invalid CSRF token');
        }
        
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        
        error_log("LOGIN: Attempting login for username: $username");
        
        $auth = new Auth();
        
        if ($auth->attempt($username, $password, $ip)) {
            error_log("LOGIN SUCCESS: $username");
            header('Location: /dashboard');
            exit;
        }
        
        error_log("LOGIN FAILED: $username");
        header('Location: /login?error=1');
        exit;
    }
    
    public function logout(): void {
        $auth = new Auth();
        $auth->logout();
        header('Location: /login');
        exit;
    }
}
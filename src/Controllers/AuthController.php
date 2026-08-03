<?php
namespace App\Controllers;

use App\Core\{Auth, CSRF, BaseController};

class AuthController extends BaseController {
    public function showLogin(): void {
        if (Auth::check()) {
            header('Location: /dashboard');
            exit;
        }
        require __DIR__ . '/../../views/auth/login.php';
    }

    public function login(): void {
        $this->requireCsrf();

        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $ip       = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        $auth = new Auth();

        if ($auth->attempt($username, $password, $ip)) {
            header('Location: /dashboard');
            exit;
        }

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

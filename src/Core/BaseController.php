<?php
namespace App\Core;

class BaseController {
    protected function requireCsrf(): void {
        if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
            http_response_code(400);
            die('Invalid CSRF token');
        }
    }

    protected function abort(int $code, string $message): void {
        http_response_code($code);
        die($message);
    }
}

<?php
// src/Middleware/RoleMiddleware.php
namespace App\Middleware;

use App\Core\Auth;

class RoleMiddleware {
    public function handle(string ...$allowedRoles): void {
        if (!Auth::hasRole(...$allowedRoles)) {
            http_response_code(403);
            die('Adgang nægtet');
        }
    }
}
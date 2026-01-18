<?php
// src/Core/Auth.php - DEBUG version
namespace App\Core;

use App\Config\Database;
use PDO;

class Auth {
    private PDO $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function attempt(string $username, string $password, string $ip): bool {
        error_log("AUTH: Checking rate limit for $username / $ip");
        if ($this->isRateLimited($username, $ip)) {
            error_log("AUTH: Rate limited!");
            return false;
        }
        
        error_log("AUTH: Querying database for username: $username");
        $stmt = $this->db->prepare(
            "SELECT * FROM users WHERE username = ? AND status = 'active'"
        );
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if (!$user) {
            error_log("AUTH: User not found in database");
            $this->logAttempt($username, $ip, false);
            return false;
        }
        
        error_log("AUTH: User found - ID: {$user['id']}, Hash: " . substr($user['password_hash'], 0, 20) . "...");
        
        $success = password_verify($password, $user['password_hash']);
        error_log("AUTH: Password verify result: " . ($success ? 'TRUE' : 'FALSE'));
        
        $this->logAttempt($username, $ip, $success);
        
        if ($success) {
            Session::regenerate();
            Session::set('user_id', $user['id']);
            Session::set('username', $user['username']);
			Session::set('name', $user['name']);
            Session::set('role', $user['role']);
            Session::set('station_id', $user['station_id']);
            
            $stmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            error_log("AUTH: Session created for user {$user['id']}");
        }
        
        return $success;
    }
    
    public function logout(): void {
        Session::destroy();
    }
    
    public static function check(): bool {
        return Session::has('user_id');
    }
    
    public static function user(): ?array {
        if (!self::check()) return null;
        
        return [
            'id' => Session::get('user_id'),
            'username' => Session::get('username'),
			'name' => Session::get('name'),
            'role' => Session::get('role'),
            'station_id' => Session::get('station_id')
        ];
    }
    
    public static function hasRole(string ...$roles): bool {
        return in_array(Session::get('role'), $roles);
    }
    
    public static function isStationUser(): bool {
        return Session::get('role') === 'station_read';
    }
    
    public static function getStationId(): ?int {
        return Session::get('station_id');
    }
    
    private function isRateLimited(string $username, string $ip): bool {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM login_attempts 
             WHERE (username = ? OR ip_address = ?) 
             AND success = 0 
             AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)"
        );
        $stmt->execute([$username, $ip]);
        
        return $stmt->fetchColumn() >= 5;
    }
    
    private function logAttempt(string $username, string $ip, bool $success): void {
        $stmt = $this->db->prepare(
            "INSERT INTO login_attempts (username, ip_address, success) VALUES (?, ?, ?)"
        );
        $stmt->execute([$username, $ip, $success ? 1 : 0]);
    }
}
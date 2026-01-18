<?php
// src/Controllers/UserController.php
namespace App\Controllers;

use App\Config\Database;
use App\Core\{CSRF, Auth};
use PDO;

class UserController {
    private PDO $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function index(): void {
        $stmt = $this->db->query(
            "SELECT u.*, s.name as station_name
             FROM users u
             LEFT JOIN stations s ON s.id = u.station_id
             ORDER BY u.created_at DESC"
        );
        $users = $stmt->fetchAll();
        
        require __DIR__ . '/../../views/users/index.php';
    }
    
    public function create(): void {
        $stations = $this->db->query("SELECT id, name FROM stations ORDER BY name")->fetchAll();
        require __DIR__ . '/../../views/users/create.php';
    }
    
    public function store(): void {
        if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
            die('Invalid CSRF token');
        }
        
        try {
            $username = trim($_POST['username']);
            $password = trim($_POST['password']);
            $name = trim($_POST['name']);
            $role = $_POST['role'];
            $stationId = !empty($_POST['station_id']) ? (int)$_POST['station_id'] : null;
            
            // Validering
            if (empty($username) || empty($password) || empty($name)) {
                throw new \Exception('Brugernavn, password og navn skal udfyldes');
            }
            
            if (!in_array($role, ['admin', 'global_read', 'station_read'])) {
                throw new \Exception('Ugyldig rolle');
            }
            
            if ($role === 'station_read' && !$stationId) {
                throw new \Exception('Station skal vælges for station-brugere');
            }
            
            // Check duplikat username
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                throw new \Exception('Brugernavn eksisterer allerede');
            }
            
            // Opret bruger
            $hash = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $this->db->prepare(
                "INSERT INTO users (username, password_hash, name, role, station_id, status) 
                 VALUES (?, ?, ?, ?, ?, 'active')"
            );
            $stmt->execute([$username, $hash, $name, $role, $stationId]);
            
            header('Location: /users?success=created');
        } catch (\Exception $e) {
            header('Location: /users/create?error=' . urlencode($e->getMessage()));
        }
        exit;
    }
    
    public function edit(string $id): void {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            http_response_code(404);
            die('Bruger ikke fundet');
        }
        
        $stations = $this->db->query("SELECT id, name FROM stations ORDER BY name")->fetchAll();
        require __DIR__ . '/../../views/users/edit.php';
    }
    
    public function update(string $id): void {
        if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
            die('Invalid CSRF token');
        }
        
        try {
            $name = trim($_POST['name']);
            $role = $_POST['role'];
            $stationId = !empty($_POST['station_id']) ? (int)$_POST['station_id'] : null;
            $status = $_POST['status'];
            
            if (!in_array($role, ['admin', 'global_read', 'station_read'])) {
                throw new \Exception('Ugyldig rolle');
            }
            
            if ($role === 'station_read' && !$stationId) {
                throw new \Exception('Station skal vælges for station-brugere');
            }
            
            $stmt = $this->db->prepare(
                "UPDATE users SET name = ?, role = ?, station_id = ?, status = ? WHERE id = ?"
            );
            $stmt->execute([$name, $role, $stationId, $status, $id]);
            
            header('Location: /users?success=updated');
        } catch (\Exception $e) {
            header('Location: /users/' . $id . '/edit?error=' . urlencode($e->getMessage()));
        }
        exit;
    }
    
    public function resetPassword(string $id): void {
        if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
            die('Invalid CSRF token');
        }
        
        try {
            $password = trim($_POST['password']);
            
            if (empty($password)) {
                throw new \Exception('Password skal udfyldes');
            }
            
            $hash = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $this->db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->execute([$hash, $id]);
            
            header('Location: /users?success=password_reset');
        } catch (\Exception $e) {
            header('Location: /users/' . $id . '/edit?error=' . urlencode($e->getMessage()));
        }
        exit;
    }
}
<?php
// src/Controllers/ProfileController.php
namespace App\Controllers;

use App\Config\Database;
use App\Core\{CSRF, Auth, Session};
use PDO;

class ProfileController {
    private PDO $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function show(): void {
        $userId = Auth::user()['id'];
        
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        require __DIR__ . '/../../views/profile/show.php';
    }
    
    public function update(): void {
        if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
            die('Invalid CSRF token');
        }
        
        $userId = Auth::user()['id'];
        
        try {
            $name = trim($_POST['name']);
            
            if (empty($name)) {
                throw new \Exception('Navn skal udfyldes');
            }
            
            $stmt = $this->db->prepare("UPDATE users SET name = ? WHERE id = ?");
            $stmt->execute([$name, $userId]);
            
            header('Location: /profile?success=updated');
        } catch (\Exception $e) {
            header('Location: /profile?error=' . urlencode($e->getMessage()));
        }
        exit;
    }
    
    public function changePassword(): void {
        if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
            die('Invalid CSRF token');
        }
        
        $userId = Auth::user()['id'];
        
        try {
            $currentPassword = $_POST['current_password'];
            $newPassword = $_POST['new_password'];
            $confirmPassword = $_POST['confirm_password'];
            
            // Hent nuværende bruger
            $stmt = $this->db->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            // Verificer nuværende password
            if (!password_verify($currentPassword, $user['password_hash'])) {
                throw new \Exception('Nuværende password er forkert');
            }
            
            // Valider nyt password
            if (strlen($newPassword) < 8) {
                throw new \Exception('Nyt password skal være mindst 8 tegn');
            }
            
            if ($newPassword !== $confirmPassword) {
                throw new \Exception('De to passwords matcher ikke');
            }
            
            // Opdater password
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->execute([$hash, $userId]);
            
            header('Location: /profile?success=password_changed');
        } catch (\Exception $e) {
            header('Location: /profile?error=' . urlencode($e->getMessage()));
        }
        exit;
    }
}

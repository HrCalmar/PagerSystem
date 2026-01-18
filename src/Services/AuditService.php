<?php
// src/Services/AuditService.php
namespace App\Services;

use App\Config\Database;
use PDO;

class AuditService {
    private PDO $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function log(int $userId, string $actionType, string $entityType, int $entityId, ?array $before, ?array $after): void {
        $stmt = $this->db->prepare(
            "INSERT INTO audit_log (user_id, action_type, entity_type, entity_id, before_data, after_data, ip_address) 
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        
        $stmt->execute([
            $userId,
            $actionType,
            $entityType,
            $entityId,
            $before ? json_encode($before) : null,
            $after ? json_encode($after) : null,
            $_SERVER['REMOTE_ADDR'] ?? null
        ]);
    }
}
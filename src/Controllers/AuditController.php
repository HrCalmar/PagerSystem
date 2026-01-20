<?php
namespace App\Controllers;

use App\Config\Database;
use App\Core\Auth;
use PDO;

class AuditController {
    private PDO $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function index(): void {
        $filters = [
            'user_id' => $_GET['user_id'] ?? '',
            'action_type' => $_GET['action_type'] ?? '',
            'entity_type' => $_GET['entity_type'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? ''
        ];
        
        $sql = "SELECT a.*, u.name as user_name, u.username
                FROM audit_log a
                LEFT JOIN users u ON u.id = a.user_id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['user_id'])) {
            $sql .= " AND a.user_id = ?";
            $params[] = $filters['user_id'];
        }
        
        if (!empty($filters['action_type'])) {
            $sql .= " AND a.action_type = ?";
            $params[] = $filters['action_type'];
        }
        
        if (!empty($filters['entity_type'])) {
            $sql .= " AND a.entity_type = ?";
            $params[] = $filters['entity_type'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(a.created_at) >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(a.created_at) <= ?";
            $params[] = $filters['date_to'];
        }
        
        $sql .= " ORDER BY a.created_at DESC LIMIT 500";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll();
        
        $users = $this->db->query("SELECT id, name FROM users ORDER BY name")->fetchAll();
        
        $actionTypes = $this->db->query(
            "SELECT DISTINCT action_type FROM audit_log ORDER BY action_type"
        )->fetchAll(PDO::FETCH_COLUMN);
        
        $entityTypes = $this->db->query(
            "SELECT DISTINCT entity_type FROM audit_log ORDER BY entity_type"
        )->fetchAll(PDO::FETCH_COLUMN);
        
        require __DIR__ . '/../../views/audit/index.php';
    }
    
    public function show(string $id): void {
        $stmt = $this->db->prepare(
            "SELECT a.*, u.name as user_name, u.username
             FROM audit_log a
             LEFT JOIN users u ON u.id = a.user_id
             WHERE a.id = ?"
        );
        $stmt->execute([$id]);
        $log = $stmt->fetch();
        
        if (!$log) {
            http_response_code(404);
            die('Log entry ikke fundet');
        }
        
        require __DIR__ . '/../../views/audit/show.php';
    }
}
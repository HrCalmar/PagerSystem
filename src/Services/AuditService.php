<?php
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
            $after  ? json_encode($after)  : null,
            $_SERVER['REMOTE_ADDR'] ?? null
        ]);
    }

    public function getFiltered(array $filters): array {
        $sql    = "SELECT a.*, u.name as user_name, u.username
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
        return $stmt->fetchAll();
    }

    public function getEntryById(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT a.*, u.name as user_name, u.username
             FROM audit_log a
             LEFT JOIN users u ON u.id = a.user_id
             WHERE a.id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getUsers(): array {
        return $this->db->query("SELECT id, name FROM users ORDER BY name")->fetchAll();
    }

    public function getActionTypes(): array {
        return $this->db->query(
            "SELECT DISTINCT action_type FROM audit_log ORDER BY action_type"
        )->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getEntityTypes(): array {
        return $this->db->query(
            "SELECT DISTINCT entity_type FROM audit_log ORDER BY entity_type"
        )->fetchAll(PDO::FETCH_COLUMN);
    }
}

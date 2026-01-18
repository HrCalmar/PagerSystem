<?php
// src/Services/PagerService.php
namespace App\Services;

use App\Config\Database;
use App\Core\Auth;
use PDO;

class PagerService {
    private PDO $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getAll(array $filters = []): array {
        $sql = "SELECT p.*, 
                       s.sim_number, s.phone_number, s.status as sim_status,
                       pa.staff_id, st.name as staff_name,
                       CASE WHEN pa.returned_at IS NULL AND pa.issued_at IS NOT NULL THEN 1 ELSE 0 END as is_issued
                FROM pagers p
                LEFT JOIN sim_cards s ON s.pager_id = p.id AND s.status = 'active'
                LEFT JOIN pager_assignments pa ON pa.pager_id = p.id AND pa.returned_at IS NULL
                LEFT JOIN staff st ON st.id = pa.staff_id
                WHERE 1=1";
        
        $params = [];
        
        // Vis arkiverede eller ej
        if (!empty($filters['show_archived'])) {
            $sql .= " AND p.status = 'archived'";
        } else {
            $sql .= " AND p.status != 'archived'";
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (p.serial_number LIKE ? OR p.article_number LIKE ? OR s.phone_number LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }
        
        if (!empty($filters['status']) && $filters['status'] !== 'archived') {
            $sql .= " AND p.status = ?";
            $params[] = $filters['status'];
        }
        
        if (Auth::isStationUser()) {
            $sql .= " AND (p.status = 'in_stock' OR pa.staff_id IN (
                SELECT staff_id FROM station_assignments 
                WHERE station_id = ? AND end_date IS NULL
            ))";
            $params[] = Auth::getStationId();
        }
        
        $sql .= " ORDER BY p.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getById(int $id): ?array {
        $sql = "SELECT p.*, 
                       s.sim_number, s.phone_number, s.status as sim_status,
                       pa.staff_id, st.name as staff_name, pa.issued_at, pa.reserved_at
                FROM pagers p
                LEFT JOIN sim_cards s ON s.pager_id = p.id AND s.status = 'active'
                LEFT JOIN pager_assignments pa ON pa.pager_id = p.id AND pa.returned_at IS NULL
                LEFT JOIN staff st ON st.id = pa.staff_id
                WHERE p.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $pager = $stmt->fetch();
        
        if (!$pager) return null;
        
        if (Auth::isStationUser() && !$this->canAccessPager($id)) {
            return null;
        }
        
        return $pager;
    }
    
    public function create(array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO pagers (serial_number, article_number, purchase_date, status) 
             VALUES (?, ?, ?, 'in_stock')"
        );
        $stmt->execute([
            $data['serial_number'],
            $data['article_number'] ?? null,
            $data['purchase_date'] ?? null
        ]);
        
        return (int)$this->db->lastInsertId();
    }
    
    public function update(int $id, array $data): bool {
        $stmt = $this->db->prepare(
            "UPDATE pagers 
             SET serial_number = ?, article_number = ?, purchase_date = ?
             WHERE id = ?"
        );
        return $stmt->execute([
            $data['serial_number'],
            $data['article_number'] ?? null,
            $data['purchase_date'] ?? null,
            $id
        ]);
    }
    
    public function updateStatus(int $id, string $status): bool {
        $stmt = $this->db->prepare("UPDATE pagers SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }
    
    public function getHistory(int $pagerId): array {
        $sql = "SELECT pa.*, 
                       st.name as staff_name, st.employee_number,
                       u1.name as reserved_by_name,
                       u2.name as issued_by_name,
                       u3.name as returned_by_name
                FROM pager_assignments pa
                LEFT JOIN staff st ON st.id = pa.staff_id
                LEFT JOIN audit_log al1 ON al1.entity_type = 'pager_assignment' AND al1.entity_id = pa.id AND al1.action_type = 'reserve'
                LEFT JOIN users u1 ON u1.id = al1.user_id
                LEFT JOIN audit_log al2 ON al2.entity_type = 'pager_assignment' AND al2.entity_id = pa.id AND al2.action_type = 'issue'
                LEFT JOIN users u2 ON u2.id = al2.user_id
                LEFT JOIN audit_log al3 ON al3.entity_type = 'pager_assignment' AND al3.entity_id = pa.id AND al3.action_type = 'return'
                LEFT JOIN users u3 ON u3.id = al3.user_id
                WHERE pa.pager_id = ?
                ORDER BY pa.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$pagerId]);
        return $stmt->fetchAll();
    }
    
    public function getRepairs(int $pagerId): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM repairs WHERE pager_id = ? ORDER BY repair_date DESC"
        );
        $stmt->execute([$pagerId]);
        return $stmt->fetchAll();
    }
    
    public function getActiveStaff(): array {
        $sql = "SELECT id, name, employee_number FROM staff WHERE status = 'active' AND deleted_at IS NULL ORDER BY name";
        
        if (Auth::isStationUser()) {
            $sql = "SELECT s.id, s.name, s.employee_number 
                    FROM staff s
                    INNER JOIN station_assignments sa ON sa.staff_id = s.id
                    WHERE s.status = 'active' 
                    AND s.deleted_at IS NULL
                    AND sa.station_id = ? 
                    AND sa.end_date IS NULL
                    ORDER BY s.name";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([Auth::getStationId()]);
        } else {
            $stmt = $this->db->query($sql);
        }
        
        return $stmt->fetchAll();
    }
    
    private function canAccessPager(int $pagerId): bool {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM pager_assignments pa
             INNER JOIN station_assignments sa ON sa.staff_id = pa.staff_id
             WHERE pa.pager_id = ? 
             AND sa.station_id = ? 
             AND sa.end_date IS NULL
             AND pa.returned_at IS NULL"
        );
        $stmt->execute([$pagerId, Auth::getStationId()]);
        return (bool)$stmt->fetch();
    }
}

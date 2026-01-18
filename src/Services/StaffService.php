<?php
// src/Services/StaffService.php
namespace App\Services;

use App\Config\Database;
use App\Core\Auth;
use PDO;

class StaffService {
    private PDO $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getAll(array $filters = []): array {
        $sql = "SELECT s.*, 
                       GROUP_CONCAT(DISTINCT st.name SEPARATOR ', ') as stations,
                       COUNT(DISTINCT pa.id) as active_pagers
                FROM staff s
                LEFT JOIN station_assignments sa ON sa.staff_id = s.id AND sa.end_date IS NULL
                LEFT JOIN stations st ON st.id = sa.station_id
                LEFT JOIN pager_assignments pa ON pa.staff_id = s.id AND pa.returned_at IS NULL AND pa.issued_at IS NOT NULL
                WHERE 1=1";
        
        $params = [];
        
        // Vis slettede eller ej
        if (!empty($filters['show_deleted'])) {
            $sql .= " AND s.deleted_at IS NOT NULL";
        } else {
            $sql .= " AND s.deleted_at IS NULL";
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (s.name LIKE ? OR s.employee_number LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND s.status = ?";
            $params[] = $filters['status'];
        }
        
        if (Auth::isStationUser()) {
            $sql .= " AND sa.station_id = ?";
            $params[] = Auth::getStationId();
        }
        
        $sql .= " GROUP BY s.id ORDER BY s.name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getById(int $id, bool $includeDeleted = false): ?array {
        $sql = "SELECT * FROM staff WHERE id = ?";
        if (!$includeDeleted) {
            $sql .= " AND deleted_at IS NULL";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $staff = $stmt->fetch();
        
        if (!$staff) return null;
        
        if (Auth::isStationUser() && !$this->canAccessStaff($id)) {
            return null;
        }
        
        return $staff;
    }
    
    public function create(array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO staff (name, employee_number, status) VALUES (?, ?, 'active')"
        );
        $stmt->execute([
            $data['name'],
            $data['employee_number']
        ]);
        
        return (int)$this->db->lastInsertId();
    }
    
    public function update(int $id, array $data): bool {
        $stmt = $this->db->prepare(
            "UPDATE staff SET name = ?, employee_number = ? WHERE id = ?"
        );
        return $stmt->execute([
            $data['name'],
            $data['employee_number'],
            $id
        ]);
    }
    
    public function getStations(int $staffId): array {
        $stmt = $this->db->prepare(
            "SELECT st.*, sa.start_date, sa.end_date, sa.id as assignment_id
             FROM station_assignments sa
             INNER JOIN stations st ON st.id = sa.station_id
             WHERE sa.staff_id = ?
             ORDER BY sa.start_date DESC"
        );
        $stmt->execute([$staffId]);
        return $stmt->fetchAll();
    }
    
    public function getCompetencies(int $staffId): array {
        $stmt = $this->db->prepare(
            "SELECT c.*, sc.obtained_date, sc.expiry_date, sc.id as staff_competency_id
             FROM staff_competencies sc
             INNER JOIN competencies c ON c.id = sc.competency_id
             WHERE sc.staff_id = ?
             ORDER BY c.name"
        );
        $stmt->execute([$staffId]);
        return $stmt->fetchAll();
    }
    
    public function getPagers(int $staffId): array {
        $stmt = $this->db->prepare(
            "SELECT p.*, pa.reserved_at, pa.issued_at, pa.returned_at, pa.reason
             FROM pager_assignments pa
             INNER JOIN pagers p ON p.id = pa.pager_id
             WHERE pa.staff_id = ?
             ORDER BY pa.created_at DESC"
        );
        $stmt->execute([$staffId]);
        return $stmt->fetchAll();
    }
    
    public function getActivePagers(int $staffId): array {
        $stmt = $this->db->prepare(
            "SELECT p.*, pa.reserved_at, pa.issued_at,
                    s.phone_number, s.sim_number
             FROM pager_assignments pa
             INNER JOIN pagers p ON p.id = pa.pager_id
             LEFT JOIN sim_cards s ON s.pager_id = p.id AND s.status = 'active'
             WHERE pa.staff_id = ? AND pa.returned_at IS NULL
             ORDER BY pa.issued_at DESC"
        );
        $stmt->execute([$staffId]);
        return $stmt->fetchAll();
    }
    
    private function canAccessStaff(int $staffId): bool {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM station_assignments 
             WHERE staff_id = ? AND station_id = ? AND end_date IS NULL"
        );
        $stmt->execute([$staffId, Auth::getStationId()]);
        return (bool)$stmt->fetch();
    }
}

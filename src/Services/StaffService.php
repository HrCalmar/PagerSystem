<?php
namespace App\Services;

use App\Config\Database;
use App\Core\Auth;
use PDO;

class StaffService {
    private PDO $db;
    private AuditService $audit;

    public function __construct() {
        $this->db    = Database::getInstance();
        $this->audit = new AuditService();
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

        if (!empty($filters['show_deleted'])) {
            $sql .= " AND s.deleted_at IS NOT NULL";
        } else {
            $sql .= " AND s.deleted_at IS NULL";
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (s.name LIKE ? OR s.employee_number LIKE ? OR s.ric_code LIKE ? OR s.odin_id LIKE ?)";
            $search   = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
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

    public function create(array $data, array $stationIds, int $userId): int {
        if (empty($data['name'])) throw new \Exception('Navn skal udfyldes');
        if (empty($data['employee_number'])) throw new \Exception('Lønnummer skal udfyldes');
        if (empty($stationIds)) throw new \Exception('Du skal vælge mindst én station');

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM staff WHERE employee_number = ? AND deleted_at IS NULL");
            $stmt->execute([$data['employee_number']]);
            if ($stmt->fetchColumn() > 0) throw new \Exception('Lønnummer eksisterer allerede');

            $stmt = $this->db->prepare(
                "INSERT INTO staff (name, employee_number, ric_code, odin_id, status) VALUES (?, ?, ?, ?, 'active')"
            );
            $stmt->execute([
                $data['name'],
                $data['employee_number'],
                $data['ric_code'] ?: null,
                $data['odin_id']  ?: null,
            ]);

            $id   = (int)$this->db->lastInsertId();
            $stmt = $this->db->prepare(
                "INSERT INTO station_assignments (staff_id, station_id, start_date) VALUES (?, ?, ?)"
            );
            foreach ($stationIds as $stationId) {
                $stmt->execute([$id, (int)$stationId, date('Y-m-d')]);
            }

            $this->audit->log($userId, 'create_staff', 'staff', $id, null, [
                'name'            => $data['name'],
                'employee_number' => $data['employee_number'],
            ]);

            $this->db->commit();
            return $id;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function update(int $id, array $data): bool {
        if (empty($data['name'])) throw new \Exception('Navn skal udfyldes');
        if (empty($data['employee_number'])) throw new \Exception('Lønnummer skal udfyldes');

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM staff WHERE employee_number = ? AND id != ? AND deleted_at IS NULL");
        $stmt->execute([$data['employee_number'], $id]);
        if ($stmt->fetchColumn() > 0) throw new \Exception('Lønnummer eksisterer allerede på en anden brandmand');

        $stmt = $this->db->prepare(
            "UPDATE staff SET name = ?, employee_number = ?, ric_code = ?, odin_id = ? WHERE id = ?"
        );
        return $stmt->execute([
            $data['name'],
            $data['employee_number'],
            $data['ric_code'] ?: null,
            $data['odin_id']  ?: null,
            $id,
        ]);
    }

    public function getStations(int $staffId): array {
        $stmt = $this->db->prepare(
            "SELECT sa.*, st.name as station_name
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
            "SELECT sc.*, c.name as competency_name, c.requires_renewal
             FROM staff_competencies sc
             INNER JOIN competencies c ON c.id = sc.competency_id
             WHERE sc.staff_id = ?
             ORDER BY c.name"
        );
        $stmt->execute([$staffId]);
        return $stmt->fetchAll();
    }

    public function getActivePagers(int $staffId): array {
        $stmt = $this->db->prepare(
            "SELECT p.*, pa.issued_at, pa.reserved_at,
                    sc.phone_number
             FROM pager_assignments pa
             INNER JOIN pagers p ON p.id = pa.pager_id
             LEFT JOIN sim_cards sc ON sc.pager_id = p.id AND sc.status = 'active'
             WHERE pa.staff_id = ? AND pa.returned_at IS NULL
             ORDER BY pa.issued_at DESC"
        );
        $stmt->execute([$staffId]);
        return $stmt->fetchAll();
    }

    public function getPagers(int $staffId): array {
        $stmt = $this->db->prepare(
            "SELECT p.*, pa.issued_at, pa.reserved_at, pa.returned_at,
                    sc.phone_number
             FROM pager_assignments pa
             INNER JOIN pagers p ON p.id = pa.pager_id
             LEFT JOIN sim_cards sc ON sc.pager_id = p.id AND sc.status = 'active'
             WHERE pa.staff_id = ?
             ORDER BY pa.created_at DESC"
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

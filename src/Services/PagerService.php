<?php
namespace App\Services;

use App\Config\Database;
use App\Core\Auth;
use PDO;

class PagerService {
    private PDO $db;
    private AuditService $audit;

    public function __construct() {
        $this->db    = Database::getInstance();
        $this->audit = new AuditService();
    }

    public function getAll(array $filters = [], int $page = 1, int $perPage = 50): array {
        [$sql, $params] = $this->buildQuery($filters);
        $sql .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = ($page - 1) * $perPage;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countAll(array $filters = []): int {
        [$sql, $params] = $this->buildQuery($filters);
        $count = $this->db->prepare("SELECT COUNT(*) FROM ($sql) AS t");
        $count->execute($params);
        return (int)$count->fetchColumn();
    }

    private function buildQuery(array $filters): array {
        $sql = "SELECT p.*,
                       s.sim_number, s.phone_number, s.status as sim_status,
                       pa.staff_id, st.name as staff_name, pa.issued_at, pa.reserved_at,
                       CASE WHEN pa.returned_at IS NULL AND pa.issued_at IS NOT NULL THEN 1 ELSE 0 END as is_issued
                FROM pagers p
                LEFT JOIN sim_cards s ON s.pager_id = p.id AND s.status = 'active'
                LEFT JOIN pager_assignments pa ON pa.pager_id = p.id AND pa.returned_at IS NULL
                LEFT JOIN staff st ON st.id = pa.staff_id
                WHERE 1=1";

        $params = [];

        if (!empty($filters['show_archived'])) {
            $sql .= " AND p.status = 'archived'";
        } else {
            $sql .= " AND p.status != 'archived'";
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (p.serial_number LIKE ? OR p.article_number LIKE ? OR s.phone_number LIKE ?)";
            $search   = '%' . $filters['search'] . '%';
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

        return [$sql, $params];
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

    public function getAvailable(): array {
        $stmt = $this->db->query(
            "SELECT id, serial_number, article_number FROM pagers
             WHERE status IN ('in_stock','reserved') AND archived_at IS NULL
             ORDER BY serial_number"
        );
        return $stmt->fetchAll();
    }

    public function create(array $data): int {
        if ($this->serialNumberExists($data['serial_number'])) {
            throw new \Exception('Serienummer eksisterer allerede');
        }

        $stmt = $this->db->prepare(
            "INSERT INTO pagers (serial_number, article_number, purchase_date, programming_file_path, status)
             VALUES (?, ?, ?, ?, 'in_stock')"
        );
        $stmt->execute([
            $data['serial_number'],
            $data['article_number']        ?: null,
            $data['purchase_date']         ?: null,
            $data['programming_file_path'] ?? null,
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        if ($this->serialNumberExists($data['serial_number'], $id)) {
            throw new \Exception('Serienummer eksisterer allerede på en anden pager');
        }

        $stmt = $this->db->prepare(
            "UPDATE pagers SET serial_number = ?, article_number = ?, purchase_date = ?, programming_file_path = ? WHERE id = ?"
        );
        return $stmt->execute([
            $data['serial_number'],
            $data['article_number']        ?: null,
            $data['purchase_date']         ?: null,
            $data['programming_file_path'] ?? null,
            $id
        ]);
    }

    public function updateStatus(int $id, string $status): bool {
        $stmt = $this->db->prepare("UPDATE pagers SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    public function archive(int $id, int $userId): void {
        $pager = $this->getById($id);
        if (!$pager) throw new \Exception('Pager ikke fundet');
        if ($pager['status'] === 'issued') throw new \Exception('Kan ikke arkivere udleverede pagere - returner først');

        $stmt = $this->db->prepare("UPDATE pagers SET status = 'archived', archived_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);
        $this->audit->log($userId, 'archive_pager', 'pager', $id, $pager, ['status' => 'archived']);
    }

    public function restore(int $id, int $userId): void {
        $pager = $this->getById($id);
        if (!$pager) throw new \Exception('Pager ikke fundet');
        if ($pager['status'] !== 'archived') throw new \Exception('Pager er ikke arkiveret');

        $stmt = $this->db->prepare("UPDATE pagers SET status = 'in_stock', archived_at = NULL WHERE id = ?");
        $stmt->execute([$id]);
        $this->audit->log($userId, 'restore_pager', 'pager', $id, $pager, ['status' => 'in_stock']);
    }

    public function hasActiveAssignment(int $pagerId): bool {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM pager_assignments WHERE pager_id = ? AND returned_at IS NULL LIMIT 1"
        );
        $stmt->execute([$pagerId]);
        return (bool)$stmt->fetchColumn();
    }

    public function getHistory(int $pagerId): array {
        $stmt = $this->db->prepare(
            "SELECT pa.*, st.name as staff_name, st.employee_number
             FROM pager_assignments pa
             LEFT JOIN staff st ON st.id = pa.staff_id
             WHERE pa.pager_id = ?
             ORDER BY pa.created_at DESC"
        );
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
            $sql  = "SELECT s.id, s.name, s.employee_number
                     FROM staff s
                     INNER JOIN station_assignments sa ON sa.staff_id = s.id
                     WHERE s.status = 'active' AND s.deleted_at IS NULL
                     AND sa.station_id = ? AND sa.end_date IS NULL
                     ORDER BY s.name";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([Auth::getStationId()]);
        } else {
            $stmt = $this->db->query($sql);
        }

        return $stmt->fetchAll();
    }

    private function serialNumberExists(string $serialNumber, ?int $excludeId = null): bool {
        $sql    = "SELECT COUNT(*) FROM pagers WHERE serial_number = ?";
        $params = [$serialNumber];
        if ($excludeId !== null) {
            $sql    .= " AND id != ?";
            $params[] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }

    private function canAccessPager(int $pagerId): bool {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM pager_assignments pa
             INNER JOIN station_assignments sa ON sa.staff_id = pa.staff_id
             WHERE pa.pager_id = ? AND sa.station_id = ? AND sa.end_date IS NULL AND pa.returned_at IS NULL"
        );
        $stmt->execute([$pagerId, Auth::getStationId()]);
        return (bool)$stmt->fetch();
    }
}

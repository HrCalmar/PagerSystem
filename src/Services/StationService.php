<?php
namespace App\Services;

use App\Config\Database;
use PDO;
use Exception;

class StationService {
    private PDO $db;
    private AuditService $audit;

    public function __construct() {
        $this->db    = Database::getInstance();
        $this->audit = new AuditService();
    }

    public function getAll(): array {
        return $this->db->query(
            "SELECT s.*,
                    COUNT(DISTINCT sa.staff_id) as staff_count,
                    COUNT(DISTINCT pa.pager_id) as pager_count
             FROM stations s
             LEFT JOIN station_assignments sa ON sa.station_id = s.id AND sa.end_date IS NULL
             LEFT JOIN pager_assignments pa ON pa.staff_id = sa.staff_id AND pa.returned_at IS NULL
             WHERE s.deleted_at IS NULL
             GROUP BY s.id
             ORDER BY s.name"
        )->fetchAll();
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM stations WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getStaff(int $stationId): array {
        $stmt = $this->db->prepare(
            "SELECT s.*,
                    COUNT(DISTINCT pa.id) as pager_count,
                    GROUP_CONCAT(DISTINCT c.name SEPARATOR ', ') as competencies
             FROM staff s
             INNER JOIN station_assignments sa ON sa.staff_id = s.id
             LEFT JOIN pager_assignments pa ON pa.staff_id = s.id AND pa.returned_at IS NULL
             LEFT JOIN staff_competencies sc ON sc.staff_id = s.id
             LEFT JOIN competencies c ON c.id = sc.competency_id
             WHERE sa.station_id = ? AND sa.end_date IS NULL AND s.deleted_at IS NULL
             GROUP BY s.id
             ORDER BY s.name"
        );
        $stmt->execute([$stationId]);
        return $stmt->fetchAll();
    }

    public function getPagers(int $stationId): array {
        $stmt = $this->db->prepare(
            "SELECT p.id, p.serial_number, p.article_number, p.status,
                    sc.phone_number,
                    st.id as staff_id, st.name as staff_name
             FROM pagers p
             INNER JOIN pager_assignments pa ON pa.pager_id = p.id AND pa.returned_at IS NULL
             INNER JOIN staff st ON st.id = pa.staff_id
             INNER JOIN station_assignments sa ON sa.staff_id = st.id AND sa.end_date IS NULL
             LEFT JOIN sim_cards sc ON sc.pager_id = p.id AND sc.status = 'active'
             WHERE sa.station_id = ?
             ORDER BY st.name, p.serial_number"
        );
        $stmt->execute([$stationId]);
        return $stmt->fetchAll();
    }

    public function nameExists(string $name, ?int $excludeId = null): bool {
        if ($excludeId !== null) {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM stations WHERE name = ? AND id != ? AND deleted_at IS NULL");
            $stmt->execute([$name, $excludeId]);
        } else {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM stations WHERE name = ? AND deleted_at IS NULL");
            $stmt->execute([$name]);
        }
        return (bool)$stmt->fetchColumn();
    }

    public function hasActiveStaff(int $id): bool {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM station_assignments WHERE station_id = ? AND end_date IS NULL"
        );
        $stmt->execute([$id]);
        return (bool)$stmt->fetchColumn();
    }

    public function create(array $data, int $userId): int {
        $name = trim($data['name'] ?? '');
        if (empty($name)) throw new Exception('Navn skal udfyldes');
        if ($this->nameExists($name)) throw new Exception('En station med dette navn eksisterer allerede');

        $stmt = $this->db->prepare(
            "INSERT INTO stations (name, code, address) VALUES (?, ?, ?)"
        );
        $stmt->execute([
            $name,
            ($data['code']    ?? '') ?: null,
            ($data['address'] ?? '') ?: null,
        ]);

        $id = (int)$this->db->lastInsertId();
        $this->audit->log($userId, 'create_station', 'station', $id, null, ['name' => $name]);
        return $id;
    }

    public function update(int $id, array $data, int $userId): void {
        $name = trim($data['name'] ?? '');
        if (empty($name)) throw new Exception('Navn skal udfyldes');
        if ($this->nameExists($name, $id)) throw new Exception('En station med dette navn eksisterer allerede');

        $stmt = $this->db->prepare(
            "UPDATE stations SET name = ?, code = ?, address = ? WHERE id = ?"
        );
        $stmt->execute([
            $name,
            ($data['code']    ?? '') ?: null,
            ($data['address'] ?? '') ?: null,
            $id,
        ]);

        $this->audit->log($userId, 'update_station', 'station', $id, null, ['name' => $name]);
    }

    public function delete(int $id, int $userId): void {
        if ($this->hasActiveStaff($id)) throw new Exception('Kan ikke slette station med aktive brandfolk');

        $stmt = $this->db->prepare("UPDATE stations SET deleted_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);

        $this->audit->log($userId, 'delete_station', 'station', $id, null, null);
    }
}

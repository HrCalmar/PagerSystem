<?php
namespace App\Services;

use App\Config\Database;
use PDO;
use Exception;

class CompetencyService {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getAll(): array {
        return $this->db->query(
            "SELECT c.*,
                    COUNT(sc.id) as staff_count,
                    SUM(CASE WHEN sc.expiry_date < CURDATE() THEN 1 ELSE 0 END) as expired_count,
                    SUM(CASE WHEN sc.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as expiring_soon_count
             FROM competencies c
             LEFT JOIN staff_competencies sc ON sc.competency_id = c.id
             LEFT JOIN staff s ON s.id = sc.staff_id AND s.deleted_at IS NULL
             GROUP BY c.id
             ORDER BY c.name"
        )->fetchAll();
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM competencies WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getStaffWithCompetency(int $competencyId): array {
        $stmt = $this->db->prepare(
            "SELECT s.*, sc.obtained_date, sc.expiry_date, sc.id as staff_competency_id,
                    GROUP_CONCAT(DISTINCT st.name SEPARATOR ', ') as stations
             FROM staff_competencies sc
             INNER JOIN staff s ON s.id = sc.staff_id
             LEFT JOIN station_assignments sa ON sa.staff_id = s.id AND sa.end_date IS NULL
             LEFT JOIN stations st ON st.id = sa.station_id
             WHERE sc.competency_id = ? AND s.deleted_at IS NULL
             GROUP BY s.id, sc.id
             ORDER BY s.name"
        );
        $stmt->execute([$competencyId]);
        return $stmt->fetchAll();
    }

    public function getExpiring(int $days): array {
        $stmt = $this->db->prepare(
            "SELECT s.id as staff_id, s.name as staff_name, s.employee_number,
                    c.id as competency_id, c.name as competency_name, c.requires_renewal,
                    sc.expiry_date, sc.id as staff_competency_id,
                    GROUP_CONCAT(DISTINCT st.name SEPARATOR ', ') as stations,
                    DATEDIFF(sc.expiry_date, CURDATE()) as days_until_expiry
             FROM staff_competencies sc
             INNER JOIN staff s ON s.id = sc.staff_id AND s.deleted_at IS NULL AND s.status = 'active'
             INNER JOIN competencies c ON c.id = sc.competency_id
             LEFT JOIN station_assignments sa ON sa.staff_id = s.id AND sa.end_date IS NULL
             LEFT JOIN stations st ON st.id = sa.station_id
             WHERE sc.expiry_date IS NOT NULL
               AND sc.expiry_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
             GROUP BY sc.id
             ORDER BY sc.expiry_date ASC"
        );
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }

    public function nameExists(string $name, ?int $excludeId = null): bool {
        if ($excludeId !== null) {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM competencies WHERE name = ? AND id != ?");
            $stmt->execute([$name, $excludeId]);
        } else {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM competencies WHERE name = ?");
            $stmt->execute([$name]);
        }
        return (bool) $stmt->fetchColumn();
    }

    public function create(string $name, ?string $description, bool $requiresRenewal): int {
        if (empty($name)) throw new Exception('Navn skal udfyldes');
        if ($this->nameExists($name)) throw new Exception('Kompetence med dette navn eksisterer allerede');

        $stmt = $this->db->prepare(
            "INSERT INTO competencies (name, description, requires_renewal) VALUES (?, ?, ?)"
        );
        $stmt->execute([$name, $description ?: null, $requiresRenewal ? 1 : 0]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, string $name, ?string $description, bool $requiresRenewal): void {
        if (empty($name)) throw new Exception('Navn skal udfyldes');
        if ($this->nameExists($name, $id)) throw new Exception('Kompetence med dette navn eksisterer allerede');

        $stmt = $this->db->prepare(
            "UPDATE competencies SET name = ?, description = ?, requires_renewal = ? WHERE id = ?"
        );
        $stmt->execute([$name, $description ?: null, $requiresRenewal ? 1 : 0, $id]);
    }

    public function hasStaffAssigned(int $id): bool {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM staff_competencies WHERE competency_id = ?");
        $stmt->execute([$id]);
        return (bool) $stmt->fetchColumn();
    }

    public function delete(int $id): void {
        if ($this->hasStaffAssigned($id)) throw new Exception('Kan ikke slette - kompetencen er tildelt til brandfolk');

        $stmt = $this->db->prepare("DELETE FROM competencies WHERE id = ?");
        $stmt->execute([$id]);
    }
}

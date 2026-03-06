<?php
// src/Services/DefectService.php
namespace App\Services;

use App\Config\Database;
use PDO;

class DefectService {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getSymptoms(bool $enabledOnly = true): array {
        $sql = "SELECT * FROM defect_symptoms";
        if ($enabledOnly) $sql .= " WHERE is_enabled = 1";
        $sql .= " ORDER BY sort_order";
        return $this->db->query($sql)->fetchAll();
    }

    public function createReport(int $pagerId, int $userId, array $symptomIds, ?string $description): int {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO defect_reports (pager_id, reported_by_user_id, description)
                 VALUES (?, ?, ?)"
            );
            $stmt->execute([$pagerId, $userId, $description ?: null]);
            $reportId = (int)$this->db->lastInsertId();

            if (!empty($symptomIds)) {
                $stmt = $this->db->prepare(
                    "INSERT INTO defect_report_symptoms (report_id, symptom_id) VALUES (?, ?)"
                );
                foreach ($symptomIds as $symptomId) {
                    $stmt->execute([$reportId, (int)$symptomId]);
                }
            }

            $this->db->commit();
            return $reportId;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getForPager(int $pagerId): array {
        $stmt = $this->db->prepare(
            "SELECT dr.*,
                    u.name as reported_by_name,
                    GROUP_CONCAT(ds.label ORDER BY ds.sort_order SEPARATOR ', ') as symptoms
             FROM defect_reports dr
             LEFT JOIN users u ON u.id = dr.reported_by_user_id
             LEFT JOIN defect_report_symptoms drs ON drs.report_id = dr.id
             LEFT JOIN defect_symptoms ds ON ds.id = drs.symptom_id
             WHERE dr.pager_id = ?
             GROUP BY dr.id
             ORDER BY dr.reported_at DESC"
        );
        $stmt->execute([$pagerId]);
        return $stmt->fetchAll();
    }

    public function getLatestForPager(int $pagerId): ?array {
        $reports = $this->getForPager($pagerId);
        return $reports[0] ?? null;
    }

    public function getReportSymptomIds(int $reportId): array {
        $stmt = $this->db->prepare(
            "SELECT symptom_id FROM defect_report_symptoms WHERE report_id = ?"
        );
        $stmt->execute([$reportId]);
        return array_column($stmt->fetchAll(), 'symptom_id');
    }

    // Tværgående rapport
    public function getReportOverview(array $filters = []): array {
        $sql = "SELECT dr.id, dr.pager_id, dr.reported_at, dr.description,
                       p.serial_number, p.article_number,
                       u.name as reported_by_name,
                       GROUP_CONCAT(DISTINCT ds.label ORDER BY ds.sort_order SEPARATOR ', ') as symptoms,
                       r.cost, r.currency, r.vendor
                FROM defect_reports dr
                INNER JOIN pagers p ON p.id = dr.pager_id
                LEFT JOIN users u ON u.id = dr.reported_by_user_id
                LEFT JOIN defect_report_symptoms drs ON drs.report_id = dr.id
                LEFT JOIN defect_symptoms ds ON ds.id = drs.symptom_id
                LEFT JOIN repairs r ON r.pager_id = dr.pager_id
                    AND r.repair_date >= dr.reported_at
                WHERE 1=1";

        $params = [];

        if (!empty($filters['symptom_id'])) {
            $sql .= " AND drs.symptom_id = ?";
            $params[] = $filters['symptom_id'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND dr.reported_at >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND dr.reported_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        $sql .= " GROUP BY dr.id ORDER BY dr.reported_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getTotalCostBySymptom(): array {
        $stmt = $this->db->query(
            "SELECT ds.label, ds.id as symptom_id,
                    COUNT(DISTINCT dr.id) as report_count,
                    SUM(r.cost) as total_cost,
                    MAX(r.currency) as currency
             FROM defect_symptoms ds
             LEFT JOIN defect_report_symptoms drs ON drs.symptom_id = ds.id
             LEFT JOIN defect_reports dr ON dr.id = drs.report_id
             LEFT JOIN repairs r ON r.pager_id = dr.pager_id
                 AND r.repair_date >= dr.reported_at
             WHERE ds.is_enabled = 1
             GROUP BY ds.id
             ORDER BY total_cost DESC, report_count DESC"
        );
        return $stmt->fetchAll();
    }

    // Settings: symptomer
    public function getAllSymptoms(): array {
        return $this->getSymptoms(false);
    }

    public function saveSymptom(string $label, int $sortOrder): int {
        $stmt = $this->db->prepare(
            "INSERT INTO defect_symptoms (label, sort_order) VALUES (?, ?)"
        );
        $stmt->execute([$label, $sortOrder]);
        return (int)$this->db->lastInsertId();
    }

    public function updateSymptom(int $id, string $label, int $sortOrder, bool $enabled): void {
        $stmt = $this->db->prepare(
            "UPDATE defect_symptoms SET label = ?, sort_order = ?, is_enabled = ? WHERE id = ?"
        );
        $stmt->execute([$label, $sortOrder, (int)$enabled, $id]);
    }

    public function deleteSymptom(int $id): void {
        $stmt = $this->db->prepare("DELETE FROM defect_symptoms WHERE id = ?");
        $stmt->execute([$id]);
    }
}
<?php
// src/Services/PreparationService.php
namespace App\Services;

use App\Config\Database;
use App\Core\Auth;
use PDO;

class PreparationService {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getChecks(bool $enabledOnly = true): array {
        $sql = "SELECT * FROM preparation_checks";
        if ($enabledOnly) $sql .= " WHERE is_enabled = 1";
        $sql .= " ORDER BY sort_order";
        return $this->db->query($sql)->fetchAll();
    }

    public function getLatestDefectSymptomIds(int $pagerId): array {
        $stmt = $this->db->prepare(
            "SELECT drs.symptom_id
             FROM defect_reports dr
             INNER JOIN defect_report_symptoms drs ON drs.report_id = dr.id
             WHERE dr.pager_id = ?
             ORDER BY dr.reported_at DESC
             LIMIT 50"
        );
        $stmt->execute([$pagerId]);
        return array_column($stmt->fetchAll(), 'symptom_id');
    }

    public function canForce(): bool {
        $settings = new SettingsService();
        $minRole = $settings->get('preparation_force_min_role', 'admin');
        return Auth::hasRole($minRole);
    }

    public function complete(
        int $pagerId,
        int $userId,
        array $items,
        bool $allOk,
        ?string $note,
        bool $forced = false,
        ?string $forcedReason = null
    ): int {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO preparation_results
                    (pager_id, checked_by_user_id, all_ok, forced, forced_reason, note)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $pagerId, $userId,
                (int)$allOk,
                (int)$forced,
                $forced ? $forcedReason : null,
                $note ?: null,
            ]);
            $resultId = (int)$this->db->lastInsertId();

            $stmt = $this->db->prepare(
                "INSERT INTO preparation_result_items (result_id, check_id, passed, note)
                 VALUES (?, ?, ?, ?)"
            );
            foreach ($items as $checkId => $item) {
                $stmt->execute([
                    $resultId,
                    (int)$checkId,
                    (int)($item['passed'] ?? false),
                    $item['note'] ?: null,
                ]);
            }

            $this->db->commit();
            return $resultId;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function hasFailed(array $items): bool {
        foreach ($items as $item) {
            if (empty($item['passed'])) return true;
        }
        return false;
    }

    public function getForPager(int $pagerId): array {
        $stmt = $this->db->prepare(
            "SELECT pr.*,
                    u.name as checked_by_name
             FROM preparation_results pr
             LEFT JOIN users u ON u.id = pr.checked_by_user_id
             WHERE pr.pager_id = ?
             ORDER BY pr.completed_at DESC"
        );
        $stmt->execute([$pagerId]);
        $results = $stmt->fetchAll();

        foreach ($results as &$result) {
            $stmt2 = $this->db->prepare(
                "SELECT pri.*, pc.label
                 FROM preparation_result_items pri
                 INNER JOIN preparation_checks pc ON pc.id = pri.check_id
                 WHERE pri.result_id = ?
                 ORDER BY pc.sort_order"
            );
            $stmt2->execute([$result['id']]);
            $result['items'] = $stmt2->fetchAll();
        }

        return $results;
    }

    // Settings
    public function getAllChecks(): array {
        return $this->getChecks(false);
    }

    public function saveCheck(string $label, int $sortOrder): int {
        $stmt = $this->db->prepare(
            "INSERT INTO preparation_checks (label, sort_order) VALUES (?, ?)"
        );
        $stmt->execute([$label, $sortOrder]);
        return (int)$this->db->lastInsertId();
    }

    public function updateCheck(int $id, string $label, int $sortOrder, bool $enabled): void {
        $stmt = $this->db->prepare(
            "UPDATE preparation_checks SET label = ?, sort_order = ?, is_enabled = ? WHERE id = ?"
        );
        $stmt->execute([$label, $sortOrder, (int)$enabled, $id]);
    }

    public function deleteCheck(int $id): void {
        $stmt = $this->db->prepare("DELETE FROM preparation_checks WHERE id = ?");
        $stmt->execute([$id]);
    }
}
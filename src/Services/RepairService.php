<?php
namespace App\Services;

use App\Config\Database;
use PDO;

class RepairService {
    private PDO $db;
    private AuditService $audit;

    public function __construct() {
        $this->db    = Database::getInstance();
        $this->audit = new AuditService();
    }

    public function create(int $pagerId, array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO repairs (pager_id, repair_date, vendor, description, cost, receipt_path)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $pagerId,
            $data['repair_date'],
            $data['vendor'] ?: null,
            $data['description'] ?: null,
            $data['cost'] !== '' ? $data['cost'] : null,
            $data['receipt_path'] ?? null
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function createWithStatusUpdate(int $pagerId, array $data, int $userId): int {
        $this->db->beginTransaction();
        try {
            $repairId = $this->create($pagerId, $data);

            $stmt = $this->db->prepare("SELECT status FROM pagers WHERE id = ?");
            $stmt->execute([$pagerId]);
            $status = $stmt->fetchColumn();

            if (!in_array($status, ['in_repair', 'issued'])) {
                $this->db->prepare("UPDATE pagers SET status = 'in_repair' WHERE id = ?")->execute([$pagerId]);
            }

            $this->audit->log($userId, 'create_repair', 'repair', $repairId, null, [
                'pager_id'    => $pagerId,
                'repair_date' => $data['repair_date'],
                'vendor'      => $data['vendor'] ?? null,
            ]);

            $this->db->commit();
            return $repairId;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getWithPager(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT r.*, p.serial_number, p.status as pager_status
             FROM repairs r
             INNER JOIN pagers p ON p.id = r.pager_id
             WHERE r.id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function updateCost(int $id, float $cost): void {
        $stmt = $this->db->prepare("UPDATE repairs SET cost = ? WHERE id = ?");
        $stmt->execute([$cost, $id]);
    }

    public function getPagerId(int $id): ?int {
        $stmt = $this->db->prepare("SELECT pager_id FROM repairs WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetchColumn();
        return $result !== false ? (int)$result : null;
    }

    public function complete(int $repairId): void {
        $stmt = $this->db->prepare("UPDATE repairs SET completed_at = NOW() WHERE id = ?");
        $stmt->execute([$repairId]);
    }
}

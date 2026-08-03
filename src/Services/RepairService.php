<?php
namespace App\Services;

use App\Config\Database;
use PDO;

class RepairService {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
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

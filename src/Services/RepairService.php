<?php
// src/Services/RepairService.php
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
    
    public function complete(int $repairId): void {
        $stmt = $this->db->prepare("UPDATE repairs SET completed_at = NOW() WHERE id = ?");
        $stmt->execute([$repairId]);
    }
}
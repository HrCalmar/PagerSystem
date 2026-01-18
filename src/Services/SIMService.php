<?php
// src/Services/SIMService.php
namespace App\Services;

use App\Config\Database;
use App\Services\AuditService;
use PDO;
use Exception;

class SIMService {
    private PDO $db;
    private AuditService $audit;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->audit = new AuditService();
    }
    
    public function add(int $pagerId, array $data, int $userId): int {
        $this->db->beginTransaction();
        try {
            // Check for duplikater
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM sim_cards 
                 WHERE (sim_number = ? OR phone_number = ?) AND status = 'active'"
            );
            $stmt->execute([$data['sim_number'], $data['phone_number']]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('SIM-nummer eller telefonnummer er allerede aktivt');
            }
            
            $stmt = $this->db->prepare(
                "INSERT INTO sim_cards (pager_id, sim_number, phone_number, status, notes) 
                 VALUES (?, ?, ?, 'active', ?)"
            );
            $stmt->execute([
                $pagerId,
                $data['sim_number'],
                $data['phone_number'],
                $data['notes'] ?? null
            ]);
            
            $simId = (int)$this->db->lastInsertId();
            
            $this->audit->log($userId, 'add_sim', 'sim_card', $simId, null, [
                'pager_id' => $pagerId,
                'sim_number' => $data['sim_number'],
                'phone_number' => $data['phone_number']
            ]);
            
            $this->db->commit();
            return $simId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    public function deactivate(int $simId, int $userId): void {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT * FROM sim_cards WHERE id = ?");
            $stmt->execute([$simId]);
            $sim = $stmt->fetch();
            
            if (!$sim) {
                throw new Exception("SIM-kort ikke fundet");
            }
            
            if ($sim['status'] === 'deactivated') {
                throw new Exception("SIM-kort er allerede deaktiveret");
            }
            
            $stmt = $this->db->prepare(
                "UPDATE sim_cards SET status = 'deactivated', deactivated_at = NOW() WHERE id = ?"
            );
            $stmt->execute([$simId]);
            
            $this->audit->log($userId, 'deactivate_sim', 'sim_card', $simId, $sim, ['status' => 'deactivated']);
            
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    public function getHistory(int $pagerId): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM sim_cards WHERE pager_id = ? ORDER BY activated_at DESC"
        );
        $stmt->execute([$pagerId]);
        return $stmt->fetchAll();
    }
}
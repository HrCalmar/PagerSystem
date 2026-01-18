<?php
// src/Services/PagerWorkflowService.php
namespace App\Services;

use App\Config\Database;
use App\Services\AuditService;
use PDO;
use Exception;

class PagerWorkflowService {
    private PDO $db;
    private AuditService $audit;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->audit = new AuditService();
    }
    
    public function reserve(int $pagerId, int $staffId, int $userId): void {
        $this->db->beginTransaction();
        try {
            $pager = $this->getPager($pagerId);
            
            if ($pager['status'] !== 'in_stock') {
                throw new Exception("Pager skal være 'På lager' for at reservere");
            }
            
            $staff = $this->getStaff($staffId);
            if ($staff['status'] !== 'active') {
                throw new Exception("Brandmand er ikke aktiv");
            }
            
            $stmt = $this->db->prepare("UPDATE pagers SET status = 'reserved' WHERE id = ?");
            $stmt->execute([$pagerId]);
            
            $stmt = $this->db->prepare(
                "INSERT INTO pager_assignments (pager_id, staff_id, reserved_at) VALUES (?, ?, NOW())"
            );
            $stmt->execute([$pagerId, $staffId]);
            
            $this->audit->log($userId, 'reserve_pager', 'pager', $pagerId, $pager, ['status' => 'reserved', 'staff_id' => $staffId]);
            
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    public function issue(int $pagerId, int $staffId, int $userId): void {
        $this->db->beginTransaction();
        try {
            $pager = $this->getPager($pagerId);
            
            if (!in_array($pager['status'], ['in_stock', 'reserved'])) {
                throw new Exception("Pager skal være 'På lager' eller 'Reserveret' for at udlevere");
            }
            
            $staff = $this->getStaff($staffId);
            if ($staff['status'] !== 'active') {
                throw new Exception("Brandmand er ikke aktiv");
            }
            
            $assignment = $this->getActiveAssignment($pagerId);
            
            if ($pager['status'] === 'reserved' && (!$assignment || $assignment['staff_id'] != $staffId)) {
                throw new Exception("Reserveret pager kan kun udleveres til den reserverede person");
            }
            
            $stmt = $this->db->prepare("UPDATE pagers SET status = 'issued' WHERE id = ?");
            $stmt->execute([$pagerId]);
            
            if ($assignment && !$assignment['issued_at']) {
                $stmt = $this->db->prepare("UPDATE pager_assignments SET issued_at = NOW() WHERE id = ?");
                $stmt->execute([$assignment['id']]);
            } else {
                $stmt = $this->db->prepare(
                    "INSERT INTO pager_assignments (pager_id, staff_id, issued_at) VALUES (?, ?, NOW())"
                );
                $stmt->execute([$pagerId, $staffId]);
            }
            
            $this->audit->log($userId, 'issue_pager', 'pager', $pagerId, $pager, ['status' => 'issued', 'staff_id' => $staffId]);
            
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    public function returnPager(int $pagerId, int $userId, ?string $reason = null): void {
        $this->db->beginTransaction();
        try {
            $pager = $this->getPager($pagerId);
            
            if ($pager['status'] !== 'issued') {
                throw new Exception("Kun udleverede pagere kan returneres");
            }
            
            $assignment = $this->getActiveAssignment($pagerId);
            if (!$assignment) {
                throw new Exception("Ingen aktiv udlevering fundet");
            }
            
            $stmt = $this->db->prepare("UPDATE pagers SET status = 'for_preparation' WHERE id = ?");
            $stmt->execute([$pagerId]);
            
            $stmt = $this->db->prepare(
                "UPDATE pager_assignments SET returned_at = NOW(), reason = ? WHERE id = ?"
            );
            $stmt->execute([$reason, $assignment['id']]);
            
            $this->audit->log($userId, 'return_pager', 'pager', $pagerId, $pager, ['status' => 'for_preparation', 'reason' => $reason]);
            
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    public function setToStock(int $pagerId, int $userId): void {
        $this->db->beginTransaction();
        try {
            $pager = $this->getPager($pagerId);
            
            if ($pager['status'] !== 'for_preparation') {
                throw new Exception("Pager skal være 'Til klargøring' før den kan sættes på lager");
            }
            
            $stmt = $this->db->prepare("UPDATE pagers SET status = 'in_stock' WHERE id = ?");
            $stmt->execute([$pagerId]);
            
            $this->audit->log($userId, 'stock_pager', 'pager', $pagerId, $pager, ['status' => 'in_stock']);
            
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    public function setToRepair(int $pagerId, int $userId): void {
        $this->db->beginTransaction();
        try {
            $pager = $this->getPager($pagerId);
            
            if ($pager['status'] === 'issued') {
                $assignment = $this->getActiveAssignment($pagerId);
                if ($assignment) {
                    $stmt = $this->db->prepare(
                        "UPDATE pager_assignments SET returned_at = NOW(), reason = 'Sendt til reparation' WHERE id = ?"
                    );
                    $stmt->execute([$assignment['id']]);
                }
            }
            
            $stmt = $this->db->prepare("UPDATE pagers SET status = 'in_repair' WHERE id = ?");
            $stmt->execute([$pagerId]);
            
            $this->audit->log($userId, 'repair_pager', 'pager', $pagerId, $pager, ['status' => 'in_repair']);
            
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    public function setDefect(int $pagerId, int $userId): void {
        $this->db->beginTransaction();
        try {
            $pager = $this->getPager($pagerId);
            
            if ($pager['status'] === 'issued') {
                $assignment = $this->getActiveAssignment($pagerId);
                if ($assignment) {
                    $stmt = $this->db->prepare(
                        "UPDATE pager_assignments SET returned_at = NOW(), reason = 'Defekt' WHERE id = ?"
                    );
                    $stmt->execute([$assignment['id']]);
                }
            }
            
            $stmt = $this->db->prepare("UPDATE pagers SET status = 'defect' WHERE id = ?");
            $stmt->execute([$pagerId]);
            
            $this->audit->log($userId, 'defect_pager', 'pager', $pagerId, $pager, ['status' => 'defect']);
            
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    private function getPager(int $id): array {
        $stmt = $this->db->prepare("SELECT * FROM pagers WHERE id = ?");
        $stmt->execute([$id]);
        $pager = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$pager) throw new Exception("Pager ikke fundet");
        return $pager;
    }
    
    private function getStaff(int $id): array {
        $stmt = $this->db->prepare("SELECT * FROM staff WHERE id = ?");
        $stmt->execute([$id]);
        $staff = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$staff) throw new Exception("Brandmand ikke fundet");
        return $staff;
    }
    
    private function getActiveAssignment(int $pagerId): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM pager_assignments WHERE pager_id = ? AND returned_at IS NULL ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute([$pagerId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
	
	public function setToPreparation(int $pagerId, int $userId): void {
    $this->db->beginTransaction();
    try {
        $pager = $this->getPager($pagerId);
        
        if (!in_array($pager['status'], ['defect', 'in_repair'])) {
            throw new Exception("Kun defekte eller pagere til reparation kan sættes til klargøring");
        }
        
        $stmt = $this->db->prepare("UPDATE pagers SET status = 'for_preparation' WHERE id = ?");
        $stmt->execute([$pagerId]);
        
        $this->audit->log($userId, 'preparation_pager', 'pager', $pagerId, $pager, ['status' => 'for_preparation']);
        
        $this->db->commit();
    } catch (Exception $e) {
        $this->db->rollBack();
        throw $e;
    }
}

		public function completeRepair(int $repairId, int $userId): void {
			$this->db->beginTransaction();
			try {
				$stmt = $this->db->prepare("SELECT * FROM repairs WHERE id = ?");
				$stmt->execute([$repairId]);
				$repair = $stmt->fetch();
				
				if (!$repair) {
					throw new Exception("Reparation ikke fundet");
				}
				
				if ($repair['completed_at']) {
					throw new Exception("Reparation er allerede afsluttet");
				}
				
				$stmt = $this->db->prepare("UPDATE repairs SET completed_at = NOW() WHERE id = ?");
				$stmt->execute([$repairId]);
				
				$pager = $this->getPager($repair['pager_id']);
				if ($pager['status'] === 'in_repair') {
					$stmt = $this->db->prepare("UPDATE pagers SET status = 'for_preparation' WHERE id = ?");
					$stmt->execute([$repair['pager_id']]);
				}
				
				$this->audit->log($userId, 'complete_repair', 'repair', $repairId, $repair, ['completed_at' => date('Y-m-d H:i:s')]);
				
				$this->db->commit();
			} catch (Exception $e) {
				$this->db->rollBack();
				throw $e;
			}
		}
	
	
}
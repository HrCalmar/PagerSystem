<?php
// src/Services/StaffWorkflowService.php
namespace App\Services;

use App\Config\Database;
use App\Services\AuditService;
use PDO;
use Exception;

class StaffWorkflowService {
    private PDO $db;
    private AuditService $audit;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->audit = new AuditService();
    }
    
    public function deactivate(int $staffId, int $userId): void {
        $this->db->beginTransaction();
        try {
            $staff = $this->getStaff($staffId);
            
            if ($staff['status'] === 'inactive') {
                throw new Exception("Brandmand er allerede inaktiv");
            }
            
            $activeAssignments = $this->getActivePagerAssignments($staffId);
            if (!empty($activeAssignments)) {
                throw new Exception(
                    "Brandmand har " . count($activeAssignments) . " aktive pagere - returner disse først"
                );
            }
            
            $stmt = $this->db->prepare("UPDATE staff SET status = 'inactive' WHERE id = ?");
            $stmt->execute([$staffId]);
            
            $stmt = $this->db->prepare(
                "UPDATE station_assignments SET end_date = CURDATE() WHERE staff_id = ? AND end_date IS NULL"
            );
            $stmt->execute([$staffId]);
            
            $this->audit->log($userId, 'deactivate_staff', 'staff', $staffId, $staff, ['status' => 'inactive']);
            
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    public function reactivate(int $staffId, int $userId): void {
        $this->db->beginTransaction();
        try {
            $staff = $this->getStaff($staffId);
            
            if ($staff['status'] === 'active') {
                throw new Exception("Brandmand er allerede aktiv");
            }
            
            $stmt = $this->db->prepare("UPDATE staff SET status = 'active' WHERE id = ?");
            $stmt->execute([$staffId]);
            
            $this->audit->log($userId, 'reactivate_staff', 'staff', $staffId, $staff, ['status' => 'active']);
            
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    public function addStation(int $staffId, int $stationId, string $startDate, int $userId): void {
        $this->db->beginTransaction();
        try {
            $existing = $this->db->prepare(
                "SELECT 1 FROM station_assignments WHERE staff_id = ? AND station_id = ? AND end_date IS NULL"
            );
            $existing->execute([$staffId, $stationId]);
            if ($existing->fetch()) {
                throw new Exception("Brandmand er allerede tilknyttet denne station");
            }
            
            $stmt = $this->db->prepare(
                "INSERT INTO station_assignments (staff_id, station_id, start_date) VALUES (?, ?, ?)"
            );
            $stmt->execute([$staffId, $stationId, $startDate]);
            
            $this->audit->log($userId, 'add_station', 'station_assignment', (int)$this->db->lastInsertId(), null, [
                'staff_id' => $staffId,
                'station_id' => $stationId,
                'start_date' => $startDate
            ]);
            
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    public function removeStation(int $assignmentId, string $endDate, int $userId): void {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT * FROM station_assignments WHERE id = ?");
            $stmt->execute([$assignmentId]);
            $assignment = $stmt->fetch();
            
            if (!$assignment) {
                throw new Exception("Tilknytning ikke fundet");
            }
            
            $stmt = $this->db->prepare("UPDATE station_assignments SET end_date = ? WHERE id = ?");
            $stmt->execute([$endDate, $assignmentId]);
            
            $this->audit->log($userId, 'remove_station', 'station_assignment', $assignmentId, $assignment, [
                'end_date' => $endDate
            ]);
            
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    public function addCompetency(int $staffId, int $competencyId, ?string $obtainedDate, ?string $expiryDate, int $userId): void {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO staff_competencies (staff_id, competency_id, obtained_date, expiry_date) VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$staffId, $competencyId, $obtainedDate, $expiryDate]);
            
            $this->audit->log($userId, 'add_competency', 'staff_competency', (int)$this->db->lastInsertId(), null, [
                'staff_id' => $staffId,
                'competency_id' => $competencyId,
                'obtained_date' => $obtainedDate,
                'expiry_date' => $expiryDate
            ]);
            
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    public function removeCompetency(int $staffCompetencyId, int $userId): void {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT * FROM staff_competencies WHERE id = ?");
            $stmt->execute([$staffCompetencyId]);
            $competency = $stmt->fetch();
            
            if (!$competency) {
                throw new Exception("Kompetence ikke fundet");
            }
            
            $stmt = $this->db->prepare("DELETE FROM staff_competencies WHERE id = ?");
            $stmt->execute([$staffCompetencyId]);
            
            $this->audit->log($userId, 'remove_competency', 'staff_competency', $staffCompetencyId, $competency, null);
            
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    private function getStaff(int $id): array {
        $stmt = $this->db->prepare("SELECT * FROM staff WHERE id = ?");
        $stmt->execute([$id]);
        $staff = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$staff) throw new Exception("Brandmand ikke fundet");
        return $staff;
    }
    
    private function getActivePagerAssignments(int $staffId): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM pager_assignments WHERE staff_id = ? AND returned_at IS NULL"
        );
        $stmt->execute([$staffId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
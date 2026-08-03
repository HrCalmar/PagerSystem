<?php
namespace App\Services;

use App\Config\Database;
use PDO;
use Exception;

class PagerWorkflowService {
    private PDO $db;
    private AuditService $audit;
    private SettingsService $settings;
    private WorkflowTransitionService $transitions;

    public function __construct() {
        $this->db          = Database::getInstance();
        $this->audit       = new AuditService();
        $this->settings    = new SettingsService();
        $this->transitions = new WorkflowTransitionService();
    }

    public function reserve(int $pagerId, int $staffId, int $userId): void {
        $this->db->beginTransaction();
        try {
            $pager = $this->getPager($pagerId);
            if ($pager['status'] !== 'in_stock') throw new Exception("Pager skal være 'På lager' for at reservere");
            $staff = $this->getStaff($staffId);
            if ($staff['status'] !== 'active') throw new Exception("Brandmand er ikke aktiv");

            $this->db->prepare("UPDATE pagers SET status = 'reserved' WHERE id = ?")->execute([$pagerId]);
            $this->db->prepare("INSERT INTO pager_assignments (pager_id, staff_id, reserved_at) VALUES (?, ?, NOW())")->execute([$pagerId, $staffId]);
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
            if (!in_array($pager['status'], ['in_stock', 'reserved'])) throw new Exception("Pager skal være 'På lager' eller 'Reserveret' for at udlevere");
            $staff = $this->getStaff($staffId);
            if ($staff['status'] !== 'active') throw new Exception("Brandmand er ikke aktiv");

            $assignment = $this->getActiveAssignment($pagerId);
            if ($pager['status'] === 'reserved' && (!$assignment || $assignment['staff_id'] != $staffId)) {
                throw new Exception("Reserveret pager kan kun udleveres til den reserverede person");
            }

            $this->db->prepare("UPDATE pagers SET status = 'issued' WHERE id = ?")->execute([$pagerId]);
            if ($assignment && !$assignment['issued_at']) {
                $this->db->prepare("UPDATE pager_assignments SET issued_at = NOW() WHERE id = ?")->execute([$assignment['id']]);
            } else {
                $this->db->prepare("INSERT INTO pager_assignments (pager_id, staff_id, issued_at) VALUES (?, ?, NOW())")->execute([$pagerId, $staffId]);
            }
            $this->audit->log($userId, 'issue_pager', 'pager', $pagerId, $pager, ['status' => 'issued', 'staff_id' => $staffId]);
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function returnPager(int $pagerId, int $userId, string $toStatus, ?string $reason = null): void {
        $this->db->beginTransaction();
        try {
            $pager = $this->getPager($pagerId);
            if ($pager['status'] !== 'issued') throw new Exception("Kun udleverede pagere kan returneres");

            $allowed = array_column($this->transitions->getForEvent('return', 'issued'), 'to_status');
            if (!in_array($toStatus, $allowed)) throw new Exception("Ugyldig målstatus for returnering");

            if ($toStatus === 'for_preparation' && !$this->settings->get('preparation_explicit', false)) {
                $toStatus = 'in_stock';
            }

            $assignment = $this->getActiveAssignment($pagerId);
            if (!$assignment) throw new Exception("Ingen aktiv udlevering fundet");

            $this->db->prepare("UPDATE pagers SET status = ? WHERE id = ?")->execute([$toStatus, $pagerId]);
            $this->db->prepare("UPDATE pager_assignments SET returned_at = NOW(), reason = ? WHERE id = ?")->execute([$reason, $assignment['id']]);
            $this->audit->log($userId, 'return_pager', 'pager', $pagerId, $pager, ['status' => $toStatus, 'reason' => $reason]);
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function markBroken(int $pagerId, int $userId, string $toStatus, array $symptomIds, ?string $description): void {
        $this->db->beginTransaction();
        try {
            $pager   = $this->getPager($pagerId);
            $allowed = array_column($this->transitions->getForEvent('broken', $pager['status']), 'to_status');
            if (!in_array($toStatus, $allowed)) throw new Exception("Ugyldig målstatus for defekt");

            $defect = new DefectService();
            $defect->createReport($pagerId, $userId, $symptomIds, $description);

            if ($toStatus === 'defect') {
                $assignment = $this->getActiveAssignment($pagerId);
                if ($assignment) {
                    $this->db->prepare("UPDATE pager_assignments SET returned_at = NOW(), reason = 'Defekt' WHERE id = ?")->execute([$assignment['id']]);
                }
            }

            $this->db->prepare("UPDATE pagers SET status = ? WHERE id = ?")->execute([$toStatus, $pagerId]);
            $this->audit->log($userId, 'broken_pager', 'pager', $pagerId, $pager, ['status' => $toStatus, 'symptoms' => $symptomIds]);
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function repaired(int $repairId, int $userId, string $toStatus = 'for_preparation'): void {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT * FROM repairs WHERE id = ?");
            $stmt->execute([$repairId]);
            $repair = $stmt->fetch();
            if (!$repair) throw new Exception("Reparation ikke fundet");
            if ($repair['completed_at']) throw new Exception("Reparation er allerede afsluttet");

            $this->db->prepare("UPDATE repairs SET completed_at = NOW() WHERE id = ?")->execute([$repairId]);

            $pager      = $this->getPager($repair['pager_id']);
            $assignment = $this->getActiveAssignment($repair['pager_id']);

            if ($assignment) {
                $this->audit->log($userId, 'complete_repair', 'repair', $repairId, $repair, ['note' => 'Afventer returnering fra brandmand']);
                $this->db->commit();
                return;
            }

            $usePrep   = $this->settings->get('preparation_explicit', false);
            $newStatus = ($toStatus === 'for_preparation' && !$usePrep) ? 'in_stock' : $toStatus;
            if (!in_array($newStatus, ['for_preparation', 'in_stock'])) $newStatus = $usePrep ? 'for_preparation' : 'in_stock';

            $this->db->prepare("UPDATE pagers SET status = ? WHERE id = ?")->execute([$newStatus, $repair['pager_id']]);
            $this->audit->log($userId, 'repaired_pager', 'pager', $repair['pager_id'], $pager, ['status' => $newStatus]);
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function completePreparation(int $pagerId, int $userId, array $items, bool $allOk, ?string $note, bool $forced = false, ?string $forcedReason = null): void {
        $this->db->beginTransaction();
        try {
            $pager = $this->getPager($pagerId);
            if ($pager['status'] !== 'for_preparation') throw new Exception("Pager er ikke til klargøring");

            $prep = new PreparationService();
            if (!$allOk && $prep->hasFailed($items) && $forced) {
                if (!$prep->canForce()) throw new Exception("Du har ikke rettigheder til at gennemtvinge klargøring");
                if (empty($forcedReason)) throw new Exception("Begrundelse er påkrævet ved gennemtvingning");
            }

            $prep->complete($pagerId, $userId, $items, $allOk, $note, $forced, $forcedReason);
            $this->db->prepare("UPDATE pagers SET status = 'in_stock' WHERE id = ?")->execute([$pagerId]);
            $this->audit->log($userId, 'prepared_pager', 'pager', $pagerId, $pager, [
                'status' => 'in_stock', 'forced' => $forced, 'forced_reason' => $forcedReason,
            ]);
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
}

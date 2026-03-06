<?php
// src/Controllers/WorkflowController.php
namespace App\Controllers;

use App\Services\{PagerWorkflowService, PagerService, WorkflowTransitionService, DefectService, PreparationService, SettingsService};
use App\Core\{CSRF, Auth};

class WorkflowController {
    private PagerWorkflowService $workflow;
    private PagerService $pagerService;
    private WorkflowTransitionService $transitions;

    public function __construct() {
        $this->workflow    = new PagerWorkflowService();
        $this->pagerService = new PagerService();
        $this->transitions = new WorkflowTransitionService();
    }

    public function showReserve(string $pagerId): void {
        $pager = $this->pagerService->getById((int)$pagerId);
        if (!$pager || $pager['status'] !== 'in_stock') die('Ugyldig pager');
        $staff = $this->pagerService->getActiveStaff();
        require __DIR__ . '/../../views/workflows/reserve.php';
    }

    public function reserve(string $pagerId): void {
        if (!CSRF::verify($_POST['csrf_token'] ?? '')) die('Invalid CSRF token');
        try {
            $this->workflow->reserve((int)$pagerId, (int)$_POST['staff_id'], Auth::user()['id']);
            header('Location: /pagers/' . $pagerId . '?success=reserved');
        } catch (\Exception $e) {
            header('Location: /pagers/' . $pagerId . '?error=' . urlencode($e->getMessage()));
        }
        exit;
    }

    public function showIssue(string $pagerId): void {
        $pager = $this->pagerService->getById((int)$pagerId);
        if (!$pager || !in_array($pager['status'], ['in_stock', 'reserved'])) die('Ugyldig pager');
        $staff = $this->pagerService->getActiveStaff();
        $preselected = $pager['staff_id'] ?? null;
        require __DIR__ . '/../../views/workflows/issue.php';
    }

    public function issue(string $pagerId): void {
        if (!CSRF::verify($_POST['csrf_token'] ?? '')) die('Invalid CSRF token');
        try {
            $this->workflow->issue((int)$pagerId, (int)$_POST['staff_id'], Auth::user()['id']);
            header('Location: /pagers/' . $pagerId . '?success=issued');
        } catch (\Exception $e) {
            header('Location: /pagers/' . $pagerId . '?error=' . urlencode($e->getMessage()));
        }
        exit;
    }

    public function showReturn(string $pagerId): void {
        $pager = $this->pagerService->getById((int)$pagerId);
        if (!$pager || $pager['status'] !== 'issued') die('Ugyldig pager');
        $transitions = $this->transitions->getForEvent('return', 'issued');
        require __DIR__ . '/../../views/workflows/return.php';
    }

    public function return(string $pagerId): void {
        if (!CSRF::verify($_POST['csrf_token'] ?? '')) die('Invalid CSRF token');
        try {
            $this->workflow->returnPager(
                (int)$pagerId,
                Auth::user()['id'],
                $_POST['to_status'],
                $_POST['reason'] ?? null
            );
            header('Location: /pagers/' . $pagerId . '?success=returned');
        } catch (\Exception $e) {
            header('Location: /pagers/' . $pagerId . '?error=' . urlencode($e->getMessage()));
        }
        exit;
    }

    public function showBroken(string $pagerId): void {
        $pager = $this->pagerService->getById((int)$pagerId);
        if (!$pager) die('Ugyldig pager');
        $transitions = $this->transitions->getForEvent('broken', $pager['status']);
        if (empty($transitions)) die('Ingen tilgængelige transitions for denne status');
        $symptoms = (new DefectService())->getSymptoms();
        require __DIR__ . '/../../views/workflows/broken.php';
    }

    public function markBroken(string $pagerId): void {
        if (!CSRF::verify($_POST['csrf_token'] ?? '')) die('Invalid CSRF token');
        try {
            $symptomIds = array_map('intval', $_POST['symptom_ids'] ?? []);
            $this->workflow->markBroken(
                (int)$pagerId,
                Auth::user()['id'],
                $_POST['to_status'],
                $symptomIds,
                $_POST['description'] ?? null
            );
            header('Location: /pagers/' . $pagerId . '?success=broken');
        } catch (\Exception $e) {
            header('Location: /pagers/' . $pagerId . '?error=' . urlencode($e->getMessage()));
        }
        exit;
    }

    public function showRepaired(string $repairId): void {
        $db = \App\Config\Database::getInstance();
        $stmt = $db->prepare("SELECT r.*, p.serial_number, p.status as pager_status FROM repairs r INNER JOIN pagers p ON p.id = r.pager_id WHERE r.id = ?");
        $stmt->execute([$repairId]);
        $repair = $stmt->fetch();
        if (!$repair) die('Reparation ikke fundet');
        $transitions = $this->transitions->getForEvent('repaired', 'in_repair');
        $stmt2 = $db->prepare("SELECT 1 FROM pager_assignments WHERE pager_id = ? AND returned_at IS NULL LIMIT 1");
        $stmt2->execute([$repair['pager_id']]);
        $hasAssignment = (bool)$stmt2->fetchColumn();
        require __DIR__ . '/../../views/workflows/repaired.php';
    }

    public function repaired(string $repairId): void {
        if (!CSRF::verify($_POST['csrf_token'] ?? '')) die('Invalid CSRF token');
        try {
            $db = \App\Config\Database::getInstance();
            if (!empty($_POST['cost'])) {
                $stmt = $db->prepare("UPDATE repairs SET cost = ? WHERE id = ?");
                $stmt->execute([(float)$_POST['cost'], (int)$repairId]);
            }
            $this->workflow->repaired((int)$repairId, Auth::user()['id'], $_POST['to_status'] ?? 'for_preparation');
            $stmt = $db->prepare("SELECT pager_id FROM repairs WHERE id = ?");
            $stmt->execute([$repairId]);
            $pagerId = $stmt->fetchColumn();
            header('Location: /pagers/' . $pagerId . '?success=repaired');
        } catch (\Exception $e) {
            header('Location: /repairs/' . $repairId . '/complete?error=' . urlencode($e->getMessage()));
        }
        exit;
    }

    public function showPreparation(string $pagerId): void {
        $pager = $this->pagerService->getById((int)$pagerId);
        if (!$pager || $pager['status'] !== 'for_preparation') die('Ugyldig pager');
        $prep     = new PreparationService();
        $checks   = $prep->getChecks();
        $defect   = new DefectService();
        $latest   = $defect->getLatestForPager((int)$pagerId);
        $canForce = $prep->canForce();
        require __DIR__ . '/../../views/workflows/preparation.php';
    }

    public function completePreparation(string $pagerId): void {
        if (!CSRF::verify($_POST['csrf_token'] ?? '')) die('Invalid CSRF token');
        try {
            $items = [];
            foreach ($_POST['checks'] ?? [] as $checkId => $val) {
                $items[(int)$checkId] = [
                    'passed' => isset($val['passed']),
                    'note'   => $val['note'] ?? null,
                ];
            }
            $allOk   = isset($_POST['all_ok']);
            $forced  = isset($_POST['forced']);
            if ($allOk) {
                foreach ($items as $id => $_) {
                    $items[$id]['passed'] = true;
                }
            }
            $this->workflow->completePreparation(
                (int)$pagerId,
                Auth::user()['id'],
                $items,
                $allOk,
                $_POST['note'] ?? null,
                $forced,
                $_POST['forced_reason'] ?? null
            );
            header('Location: /pagers/' . $pagerId . '?success=prepared');
        } catch (\Exception $e) {
            header('Location: /pagers/' . $pagerId . '/preparation?error=' . urlencode($e->getMessage()));
        }
        exit;
    }

    public function quickAssign(): void {
        if (!CSRF::verify($_POST['csrf_token'] ?? '')) die('Invalid CSRF token');
        $staffId = (int)$_POST['staff_id'];
        try {
            $this->workflow->issue((int)$_POST['pager_id'], $staffId, Auth::user()['id']);
            header('Location: /staff/' . $staffId . '?success=pager_assigned');
        } catch (\Exception $e) {
            header('Location: /staff/' . $staffId . '?error=' . urlencode($e->getMessage()));
        }
        exit;
    }
}
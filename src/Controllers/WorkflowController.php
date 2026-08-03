<?php
namespace App\Controllers;

use App\Services\{PagerWorkflowService, PagerService, RepairService, WorkflowTransitionService, DefectService, PreparationService};
use App\Core\{Auth, BaseController};

class WorkflowController extends BaseController {
    private PagerWorkflowService $workflow;
    private PagerService $pagerService;
    private RepairService $repairService;
    private WorkflowTransitionService $transitions;

    public function __construct() {
        $this->workflow      = new PagerWorkflowService();
        $this->pagerService  = new PagerService();
        $this->repairService = new RepairService();
        $this->transitions   = new WorkflowTransitionService();
    }

    public function showReserve(string $pagerId): void {
        $pager = $this->pagerService->getById((int)$pagerId);
        if (!$pager || $pager['status'] !== 'in_stock') $this->abort(400, 'Ugyldig pager');
        $staff = $this->pagerService->getActiveStaff();
        require __DIR__ . '/../../views/workflows/reserve.php';
    }

    public function reserve(string $pagerId): void {
        $this->requireCsrf();
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
        if (!$pager || !in_array($pager['status'], ['in_stock', 'reserved'])) $this->abort(400, 'Ugyldig pager');
        $staff       = $this->pagerService->getActiveStaff();
        $preselected = $pager['staff_id'] ?? null;
        require __DIR__ . '/../../views/workflows/issue.php';
    }

    public function issue(string $pagerId): void {
        $this->requireCsrf();
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
        if (!$pager || $pager['status'] !== 'issued') $this->abort(400, 'Ugyldig pager');
        $transitions = $this->transitions->getForEvent('return', 'issued');
        require __DIR__ . '/../../views/workflows/return.php';
    }

    public function return(string $pagerId): void {
        $this->requireCsrf();
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
        if (!$pager) $this->abort(404, 'Pager ikke fundet');
        $transitions = $this->transitions->getForEvent('broken', $pager['status']);
        if (empty($transitions)) $this->abort(400, 'Ingen tilgængelige transitions for denne status');
        $symptoms = (new DefectService())->getSymptoms();
        require __DIR__ . '/../../views/workflows/broken.php';
    }

    public function markBroken(string $pagerId): void {
        $this->requireCsrf();
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
        $repair = $this->repairService->getWithPager((int)$repairId);
        if (!$repair) $this->abort(404, 'Reparation ikke fundet');
        $transitions   = $this->transitions->getForEvent('repaired', 'in_repair');
        $hasAssignment = $this->pagerService->hasActiveAssignment((int)$repair['pager_id']);
        require __DIR__ . '/../../views/workflows/repaired.php';
    }

    public function repaired(string $repairId): void {
        $this->requireCsrf();
        try {
            if (!empty($_POST['cost'])) {
                $this->repairService->updateCost((int)$repairId, (float)$_POST['cost']);
            }
            $this->workflow->repaired((int)$repairId, Auth::user()['id'], $_POST['to_status'] ?? 'for_preparation');
            $pagerId = $this->repairService->getPagerId((int)$repairId);
            header('Location: /pagers/' . $pagerId . '?success=repaired');
        } catch (\Exception $e) {
            header('Location: /repairs/' . $repairId . '/complete?error=' . urlencode($e->getMessage()));
        }
        exit;
    }

    public function showPreparation(string $pagerId): void {
        $pager = $this->pagerService->getById((int)$pagerId);
        if (!$pager || $pager['status'] !== 'for_preparation') $this->abort(400, 'Ugyldig pager');
        $prep     = new PreparationService();
        $checks   = $prep->getChecks();
        $defect   = new DefectService();
        $latest   = $defect->getLatestForPager((int)$pagerId);
        $canForce = $prep->canForce();
        require __DIR__ . '/../../views/workflows/preparation.php';
    }

    public function completePreparation(string $pagerId): void {
        $this->requireCsrf();
        try {
            $items = [];
            foreach ($_POST['checks'] ?? [] as $checkId => $val) {
                $items[(int)$checkId] = [
                    'passed' => isset($val['passed']),
                    'note'   => $val['note'] ?? null,
                ];
            }
            $allOk  = isset($_POST['all_ok']);
            $forced = isset($_POST['forced']);
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
        $this->requireCsrf();
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

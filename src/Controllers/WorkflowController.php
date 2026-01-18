<?php
// src/Controllers/WorkflowController.php
namespace App\Controllers;

use App\Services\{PagerWorkflowService, PagerService};
use App\Core\{CSRF, Auth};

class WorkflowController {
    private PagerWorkflowService $workflow;
    private PagerService $pagerService;
    
    public function __construct() {
        $this->workflow = new PagerWorkflowService();
        $this->pagerService = new PagerService();
    }
    
    public function showReserve(string $pagerId): void {
        $pager = $this->pagerService->getById((int)$pagerId);
        if (!$pager || $pager['status'] !== 'in_stock') {
            die('Ugyldig pager');
        }
        
        $staff = $this->pagerService->getActiveStaff();
        require __DIR__ . '/../../views/workflows/reserve.php';
    }
    
    public function reserve(string $pagerId): void {
        if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
            die('Invalid CSRF token');
        }
        
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
        if (!$pager || !in_array($pager['status'], ['in_stock', 'reserved'])) {
            die('Ugyldig pager');
        }
        
        $staff = $this->pagerService->getActiveStaff();
        $preselected = $pager['staff_id'] ?? null;
        require __DIR__ . '/../../views/workflows/issue.php';
    }
    
    public function issue(string $pagerId): void {
        if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
            die('Invalid CSRF token');
        }
        
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
        if (!$pager || $pager['status'] !== 'issued') {
            die('Ugyldig pager');
        }
        
        require __DIR__ . '/../../views/workflows/return.php';
    }
    
    public function return(string $pagerId): void {
        if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
            die('Invalid CSRF token');
        }
        
        try {
            $this->workflow->returnPager((int)$pagerId, Auth::user()['id'], $_POST['reason'] ?? null);
            header('Location: /pagers/' . $pagerId . '?success=returned');
        } catch (\Exception $e) {
            header('Location: /pagers/' . $pagerId . '?error=' . urlencode($e->getMessage()));
        }
        exit;
    }
    
    public function setToStock(string $pagerId): void {
        if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
            die('Invalid CSRF token');
        }
        
        try {
            $this->workflow->setToStock((int)$pagerId, Auth::user()['id']);
            header('Location: /pagers/' . $pagerId . '?success=stocked');
        } catch (\Exception $e) {
            header('Location: /pagers/' . $pagerId . '?error=' . urlencode($e->getMessage()));
        }
        exit;
    }
    
    public function setToRepair(string $pagerId): void {
        if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
            die('Invalid CSRF token');
        }
        
        try {
            $this->workflow->setToRepair((int)$pagerId, Auth::user()['id']);
            header('Location: /pagers/' . $pagerId . '?success=repair');
        } catch (\Exception $e) {
            header('Location: /pagers/' . $pagerId . '?error=' . urlencode($e->getMessage()));
        }
        exit;
    }
    
    public function setDefect(string $pagerId): void {
        if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
            die('Invalid CSRF token');
        }
        
        try {
            $this->workflow->setDefect((int)$pagerId, Auth::user()['id']);
            header('Location: /pagers/' . $pagerId . '?success=defect');
        } catch (\Exception $e) {
            header('Location: /pagers/' . $pagerId . '?error=' . urlencode($e->getMessage()));
        }
        exit;
    }
	
	public function quickAssign(): void {
    if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token');
    }
    
    try {
        $pagerId = (int)$_POST['pager_id'];
        $staffId = (int)$_POST['staff_id'];
        
        $this->workflow->issue($pagerId, $staffId, Auth::user()['id']);
        header('Location: /staff/' . $staffId . '?success=pager_assigned');
    } catch (\Exception $e) {
        header('Location: /staff/' . $staffId . '?error=' . urlencode($e->getMessage()));
    }
    exit;
}
	public function setToPreparation(string $pagerId): void {
    if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token');
    }
    
    try {
        $this->workflow->setToPreparation((int)$pagerId, Auth::user()['id']);
        header('Location: /pagers/' . $pagerId . '?success=preparation');
    } catch (\Exception $e) {
        header('Location: /pagers/' . $pagerId . '?error=' . urlencode($e->getMessage()));
    }
    exit;
}
	
}
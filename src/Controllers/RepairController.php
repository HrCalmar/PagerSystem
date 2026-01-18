<?php
// src/Controllers/RepairController.php - ny controller
namespace App\Controllers;

use App\Services\{RepairService, PagerWorkflowService};
use App\Core\{CSRF, Auth};

class RepairController {
    private RepairService $service;
    private PagerWorkflowService $workflow;
    
    public function __construct() {
        $this->service = new RepairService();
        $this->workflow = new PagerWorkflowService();
    }
    
    public function create(string $pagerId): void {
        require __DIR__ . '/../../views/repairs/create.php';
    }
    
    public function store(string $pagerId): void {
        if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
            die('Invalid CSRF token');
        }
        
        try {
            $data = [
                'repair_date' => $_POST['repair_date'],
                'vendor' => $_POST['vendor'] ?? null,
                'description' => $_POST['description'] ?? null,
                'cost' => $_POST['cost'] ?? null
            ];
            
            $repairId = $this->service->create((int)$pagerId, $data);
            
            $this->workflow->setToRepair((int)$pagerId, Auth::user()['id']);
            
            header('Location: /pagers/' . $pagerId . '?success=repair_created');
        } catch (\Exception $e) {
            header('Location: /pagers/' . $pagerId . '?error=' . urlencode($e->getMessage()));
        }
        exit;
    }
    
    public function complete(string $repairId): void {
        if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
            die('Invalid CSRF token');
        }
        
        try {
            $this->workflow->completeRepair((int)$repairId, Auth::user()['id']);
            
            // Find pager ID for redirect
            $db = \App\Config\Database::getInstance();
            $stmt = $db->prepare("SELECT pager_id FROM repairs WHERE id = ?");
            $stmt->execute([$repairId]);
            $pagerId = $stmt->fetchColumn();
            
            header('Location: /pagers/' . $pagerId . '?success=repair_completed');
        } catch (\Exception $e) {
            header('Location: /pagers/' . $pagerId . '?error=' . urlencode($e->getMessage()));
        }
        exit;
    }
}
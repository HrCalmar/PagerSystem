<?php
// src/Controllers/StaffController.php
namespace App\Controllers;

use App\Services\{StaffService, StaffWorkflowService};
use App\Config\Database;
use App\Core\{CSRF, Auth};
use PDO;

class StaffController {
    private StaffService $service;
    private StaffWorkflowService $workflow;
    private PDO $db;
    
    public function __construct() {
        $this->service = new StaffService();
        $this->workflow = new StaffWorkflowService();
        $this->db = Database::getInstance();
    }
    
    public function index(): void {
        $filters = [
            'search' => $_GET['search'] ?? '',
            'status' => $_GET['status'] ?? '',
            'show_deleted' => isset($_GET['deleted']) && $_GET['deleted'] === '1'
        ];
        
        $staff = $this->service->getAll($filters);
        require __DIR__ . '/../../views/staff/index.php';
    }
    
    public function show(string $id): void {
        $staff = $this->service->getById((int)$id, true);
        
        if (!$staff) {
            http_response_code(404);
            die('Brandmand ikke fundet');
        }
        
        $stations = $this->service->getStations((int)$id);
        $competencies = $this->service->getCompetencies((int)$id);
        $activePagers = $this->service->getActivePagers((int)$id);
        $pagerHistory = $this->service->getPagers((int)$id);
        
        $allStations = $this->db->query("SELECT id, name FROM stations ORDER BY name")->fetchAll();
        $allCompetencies = $this->db->query("SELECT id, name FROM competencies ORDER BY name")->fetchAll();
        
        require __DIR__ . '/../../views/staff/show.php';
    }
    
    public function create(): void {
        $stations = $this->db->query("SELECT id, name FROM stations ORDER BY name")->fetchAll();
        require __DIR__ . '/../../views/staff/create.php';
    }
    
    public function store(): void {
        if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
            die('Invalid CSRF token');
        }
        
        $this->db->beginTransaction();
        try {
            $data = [
                'name' => trim($_POST['name']),
                'employee_number' => trim($_POST['employee_number'])
            ];
            
            if (empty($data['name'])) {
                throw new \Exception('Navn skal udfyldes');
            }
            
            if (empty($data['employee_number'])) {
                throw new \Exception('Lønnummer skal udfyldes');
            }
            
            if (empty($_POST['station_ids']) || !is_array($_POST['station_ids'])) {
                throw new \Exception('Du skal vælge mindst én station');
            }
            
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM staff WHERE employee_number = ? AND deleted_at IS NULL");
            $stmt->execute([$data['employee_number']]);
            if ($stmt->fetchColumn() > 0) {
                throw new \Exception('Lønnummer eksisterer allerede');
            }
            
            $id = $this->service->create($data);
            
            $stmt = $this->db->prepare(
                "INSERT INTO station_assignments (staff_id, station_id, start_date) VALUES (?, ?, ?)"
            );
            foreach ($_POST['station_ids'] as $stationId) {
                $stmt->execute([$id, (int)$stationId, date('Y-m-d')]);
            }
            
            $this->db->commit();
            header('Location: /staff/' . $id . '?success=created');
        } catch (\Exception $e) {
            $this->db->rollBack();
            header('Location: /staff/create?error=' . urlencode($e->getMessage()) . 
                   '&name=' . urlencode($_POST['name'] ?? '') . 
                   '&employee_number=' . urlencode($_POST['employee_number'] ?? ''));
        }
        exit;
    }
    
    public function edit(string $id): void {
        $staff = $this->service->getById((int)$id);
        
        if (!$staff) {
            http_response_code(404);
            die('Brandmand ikke fundet');
        }
        
        require __DIR__ . '/../../views/staff/edit.php';
    }
    
    public function update(string $id): void {
        if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
            die('Invalid CSRF token');
        }
        
        try {
            $data = [
                'name' => trim($_POST['name']),
                'employee_number' => trim($_POST['employee_number'])
            ];
            
            if (empty($data['employee_number'])) {
                throw new \Exception('Lønnummer skal udfyldes');
            }
            
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM staff WHERE employee_number = ? AND id != ? AND deleted_at IS NULL");
            $stmt->execute([$data['employee_number'], $id]);
            if ($stmt->fetchColumn() > 0) {
                throw new \Exception('Lønnummer eksisterer allerede på en anden brandmand');
            }
            
            $this->service->update((int)$id, $data);
            
            header('Location: /staff/' . $id . '?success=updated');
        } catch (\Exception $e) {
            header('Location: /staff/' . $id . '/edit?error=' . urlencode($e->getMessage()));
        }
        exit;
    }
    
    public function deactivate(string $id): void {
        if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
            die('Invalid CSRF token');
        }
        
        try {
            $this->workflow->deactivate((int)$id, Auth::user()['id']);
            header('Location: /staff/' . $id . '?success=deactivated');
        } catch (\Exception $e) {
            header('Location: /staff/' . $id . '?error=' . urlencode($e->getMessage()));
        }
        exit;
    }
    
    public function reactivate(string $id): void {
        if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
            die('Invalid CSRF token');
        }
        
        try {
            $this->workflow->reactivate((int)$id, Auth::user()['id']);
            header('Location: /staff/' . $id . '?success=reactivated');
        } catch (\Exception $e) {
            header('Location: /staff/' . $id . '?error=' . urlencode($e->getMessage()));
        }
        exit;
    }
    
    public function delete(string $id): void {
        if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
            die('Invalid CSRF token');
        }
        
        try {
            $staff = $this->service->getById((int)$id);
            
            if (!$staff) {
                throw new \Exception('Brandmand ikke fundet');
            }
            
            $activePagers = $this->service->getActivePagers((int)$id);
            if (!empty($activePagers)) {
                throw new \Exception('Kan ikke slette brandmand med aktive pagere - returner disse først');
            }
            
            $stmt = $this->db->prepare("UPDATE staff SET deleted_at = NOW(), status = 'inactive' WHERE id = ?");
            $stmt->execute([$id]);
            
            $stmt = $this->db->prepare("UPDATE station_assignments SET end_date = CURDATE() WHERE staff_id = ? AND end_date IS NULL");
            $stmt->execute([$id]);
            
            $audit = new \App\Services\AuditService();
            $audit->log(Auth::user()['id'], 'delete_staff', 'staff', (int)$id, $staff, ['deleted_at' => date('Y-m-d H:i:s')]);
            
            header('Location: /staff?success=deleted');
        } catch (\Exception $e) {
            header('Location: /staff/' . $id . '?error=' . urlencode($e->getMessage()));
        }
        exit;
    }
    
    public function addStation(string $id): void {
        if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
            die('Invalid CSRF token');
        }
        
        try {
            $this->workflow->addStation(
                (int)$id,
                (int)$_POST['station_id'],
                $_POST['start_date'],
                Auth::user()['id']
            );
            header('Location: /staff/' . $id . '?success=station_added');
        } catch (\Exception $e) {
            header('Location: /staff/' . $id . '?error=' . urlencode($e->getMessage()));
        }
        exit;
    }
    
    public function removeStation(string $id): void {
        if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
            die('Invalid CSRF token');
        }
        
        try {
            $this->workflow->removeStation(
                (int)$_POST['assignment_id'],
                $_POST['end_date'],
                Auth::user()['id']
            );
            header('Location: /staff/' . $id . '?success=station_removed');
        } catch (\Exception $e) {
            header('Location: /staff/' . $id . '?error=' . urlencode($e->getMessage()));
        }
        exit;
    }
    
    public function addCompetency(string $id): void {
        if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
            die('Invalid CSRF token');
        }
        
        try {
            $this->workflow->addCompetency(
                (int)$id,
                (int)$_POST['competency_id'],
                $_POST['obtained_date'] ?: null,
                $_POST['expiry_date'] ?: null,
                Auth::user()['id']
            );
            header('Location: /staff/' . $id . '?success=competency_added');
        } catch (\Exception $e) {
            header('Location: /staff/' . $id . '?error=' . urlencode($e->getMessage()));
        }
        exit;
    }
    
    public function removeCompetency(string $id): void {
        if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
            die('Invalid CSRF token');
        }
        
        try {
            $this->workflow->removeCompetency(
                (int)$_POST['staff_competency_id'],
                Auth::user()['id']
            );
            header('Location: /staff/' . $id . '?success=competency_removed');
        } catch (\Exception $e) {
            header('Location: /staff/' . $id . '?error=' . urlencode($e->getMessage()));
        }
        exit;
    }
}
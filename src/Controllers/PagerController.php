<?php
// src/Controllers/PagerController.php
namespace App\Controllers;

use App\Services\PagerService;
use App\Core\{CSRF, Auth};

class PagerController {
    private PagerService $service;
    
    public function __construct() {
        $this->service = new PagerService();
    }
    
    public function index(): void {
        $filters = [
            'search' => $_GET['search'] ?? '',
            'status' => $_GET['status'] ?? '',
            'show_archived' => isset($_GET['archived']) && $_GET['archived'] === '1'
        ];
        
        $pagers = $this->service->getAll($filters);
        require __DIR__ . '/../../views/pagers/index.php';
    }
    
    public function show(string $id): void {
        $pager = $this->service->getById((int)$id);
        
        if (!$pager) {
            http_response_code(404);
            die('Pager ikke fundet');
        }
        
        $history = $this->service->getHistory((int)$id);
        $repairs = $this->service->getRepairs((int)$id);
        
        require __DIR__ . '/../../views/pagers/show.php';
    }
    
    public function create(): void {
        $staff = $this->service->getActiveStaff();
        require __DIR__ . '/../../views/pagers/create.php';
    }
    
    public function store(): void {
        if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
            die('Invalid CSRF token');
        }
        
        try {
            $data = [
                'serial_number' => trim($_POST['serial_number']),
                'article_number' => trim($_POST['article_number'] ?? ''),
                'purchase_date' => trim($_POST['purchase_date'] ?? '')
            ];
            
            if (empty($data['serial_number'])) {
                throw new \Exception('Serienummer skal udfyldes');
            }
            
            // Check om serienummer allerede eksisterer
            $db = \App\Config\Database::getInstance();
            $stmt = $db->prepare("SELECT COUNT(*) FROM pagers WHERE serial_number = ?");
            $stmt->execute([$data['serial_number']]);
            if ($stmt->fetchColumn() > 0) {
                throw new \Exception('Serienummer eksisterer allerede');
            }
            
            $id = $this->service->create($data);
            
            header('Location: /pagers/' . $id . '?success=created');
        } catch (\Exception $e) {
            header('Location: /pagers/create?error=' . urlencode($e->getMessage()));
        }
        exit;
    }
    
    public function edit(string $id): void {
        $pager = $this->service->getById((int)$id);
        
        if (!$pager) {
            http_response_code(404);
            die('Pager ikke fundet');
        }
        
        require __DIR__ . '/../../views/pagers/edit.php';
    }
    
    public function update(string $id): void {
        if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
            die('Invalid CSRF token');
        }
        
        try {
            $data = [
                'serial_number' => trim($_POST['serial_number']),
                'article_number' => trim($_POST['article_number'] ?? ''),
                'purchase_date' => trim($_POST['purchase_date'] ?? '')
            ];
            
            if (empty($data['serial_number'])) {
                throw new \Exception('Serienummer skal udfyldes');
            }
            
            // Check om serienummer eksisterer på anden pager
            $db = \App\Config\Database::getInstance();
            $stmt = $db->prepare("SELECT COUNT(*) FROM pagers WHERE serial_number = ? AND id != ?");
            $stmt->execute([$data['serial_number'], $id]);
            if ($stmt->fetchColumn() > 0) {
                throw new \Exception('Serienummer eksisterer allerede på en anden pager');
            }
            
            $this->service->update((int)$id, $data);
            
            header('Location: /pagers/' . $id . '?success=updated');
        } catch (\Exception $e) {
            header('Location: /pagers/' . $id . '/edit?error=' . urlencode($e->getMessage()));
        }
        exit;
    }
    
    public function archive(string $id): void {
        if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
            die('Invalid CSRF token');
        }
        
        try {
            $pager = $this->service->getById((int)$id);
            
            if (!$pager) {
                throw new \Exception('Pager ikke fundet');
            }
            
            // Kan kun arkivere pagere der ikke er udleveret
            if ($pager['status'] === 'issued') {
                throw new \Exception('Kan ikke arkivere udleverede pagere - returner først');
            }
            
            $db = \App\Config\Database::getInstance();
            $stmt = $db->prepare("UPDATE pagers SET status = 'archived', archived_at = NOW() WHERE id = ?");
            $stmt->execute([$id]);
            
            // Log i audit
            $audit = new \App\Services\AuditService();
            $audit->log(Auth::user()['id'], 'archive_pager', 'pager', (int)$id, $pager, ['status' => 'archived']);
            
            header('Location: /pagers?success=archived');
        } catch (\Exception $e) {
            header('Location: /pagers/' . $id . '?error=' . urlencode($e->getMessage()));
        }
        exit;
    }
    
    public function restore(string $id): void {
        if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
            die('Invalid CSRF token');
        }
        
        try {
            $db = \App\Config\Database::getInstance();
            
            $stmt = $db->prepare("SELECT * FROM pagers WHERE id = ?");
            $stmt->execute([$id]);
            $pager = $stmt->fetch();
            
            if (!$pager) {
                throw new \Exception('Pager ikke fundet');
            }
            
            if ($pager['status'] !== 'archived') {
                throw new \Exception('Pager er ikke arkiveret');
            }
            
            $stmt = $db->prepare("UPDATE pagers SET status = 'in_stock', archived_at = NULL WHERE id = ?");
            $stmt->execute([$id]);
            
            // Log i audit
            $audit = new \App\Services\AuditService();
            $audit->log(Auth::user()['id'], 'restore_pager', 'pager', (int)$id, $pager, ['status' => 'in_stock']);
            
            header('Location: /pagers/' . $id . '?success=restored');
        } catch (\Exception $e) {
            header('Location: /pagers?archived=1&error=' . urlencode($e->getMessage()));
        }
        exit;
    }
}

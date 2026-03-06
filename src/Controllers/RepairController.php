<?php
// src/Controllers/RepairController.php
namespace App\Controllers;

use App\Services\{RepairService, PagerWorkflowService};
use App\Config\Database;
use App\Core\{CSRF, Auth};
use PDO;

class RepairController {
    private RepairService $service;

    public function __construct() {
        $this->service = new RepairService();
    }

    public function create(string $pagerId): void {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM pagers WHERE id = ?");
        $stmt->execute([$pagerId]);
        $pager = $stmt->fetch();
        if (!$pager) die('Pager ikke fundet');
        $pagerId = $pager['id'];
        require __DIR__ . '/../../views/repairs/create.php';
    }

    public function store(string $pagerId): void {
        if (!CSRF::verify($_POST['csrf_token'] ?? '')) die('Invalid CSRF token');

        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            $data = [
                'repair_date' => $_POST['repair_date'],
                'vendor'      => $_POST['vendor'] ?? null,
                'description' => $_POST['description'] ?? null,
                'cost'        => !empty($_POST['cost']) ? (float)$_POST['cost'] : null,
            ];

            $repairId = $this->service->create((int)$pagerId, $data);

            // Sæt pager til in_repair hvis den ikke allerede er det
            $stmt = $db->prepare("SELECT status FROM pagers WHERE id = ?");
            $stmt->execute([$pagerId]);
            $status = $stmt->fetchColumn();

            if (!in_array($status, ['in_repair', 'issued'])) {
                $db->prepare("UPDATE pagers SET status = 'in_repair' WHERE id = ?")->execute([$pagerId]);
            }

            $db->commit();
            header('Location: /pagers/' . $pagerId . '?success=repair_created');
        } catch (\Exception $e) {
            $db->rollBack();
            header('Location: /pagers/' . $pagerId . '?error=' . urlencode($e->getMessage()));
        }
        exit;
    }
}
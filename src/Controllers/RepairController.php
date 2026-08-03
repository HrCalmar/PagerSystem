<?php
namespace App\Controllers;

use App\Services\RepairService;
use App\Config\Database;
use App\Core\BaseController;

class RepairController extends BaseController {
    private RepairService $service;

    public function __construct() {
        $this->service = new RepairService();
    }

    public function create(string $pagerId): void {
        $db   = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM pagers WHERE id = ?");
        $stmt->execute([$pagerId]);
        $pager = $stmt->fetch();
        if (!$pager) $this->abort(404, 'Pager ikke fundet');

        require __DIR__ . '/../../views/repairs/create.php';
    }

    public function store(string $pagerId): void {
        $this->requireCsrf();

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

            $stmt   = $db->prepare("SELECT status FROM pagers WHERE id = ?");
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

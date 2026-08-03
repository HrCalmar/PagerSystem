<?php
namespace App\Controllers;

use App\Services\{PagerService, RepairService};
use App\Core\{Auth, BaseController};

class RepairController extends BaseController {
    private PagerService  $pagerService;
    private RepairService $repairService;

    public function __construct() {
        $this->pagerService  = new PagerService();
        $this->repairService = new RepairService();
    }

    public function create(string $pagerId): void {
        $pager = $this->pagerService->getById((int)$pagerId);
        if (!$pager) $this->abort(404, 'Pager ikke fundet');

        require __DIR__ . '/../../views/repairs/create.php';
    }

    public function store(string $pagerId): void {
        $this->requireCsrf();

        try {
            $data = [
                'repair_date' => $_POST['repair_date'],
                'vendor'      => $_POST['vendor']      ?? null,
                'description' => $_POST['description'] ?? null,
                'cost'        => !empty($_POST['cost']) ? (float)$_POST['cost'] : null,
            ];

            $this->repairService->createWithStatusUpdate((int)$pagerId, $data, Auth::user()['id']);
            header('Location: /pagers/' . $pagerId . '?success=repair_created');
        } catch (\Exception $e) {
            header('Location: /pagers/' . $pagerId . '?error=' . urlencode($e->getMessage()));
        }
        exit;
    }
}

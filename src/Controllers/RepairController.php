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
                'repair_date'  => $_POST['repair_date'],
                'vendor'       => $_POST['vendor']      ?? null,
                'description'  => $_POST['description'] ?? null,
                'cost'         => !empty($_POST['cost']) ? (float)$_POST['cost'] : null,
                'receipt_path' => $this->handleReceiptUpload(),
            ];

            $this->repairService->createWithStatusUpdate((int)$pagerId, $data, Auth::user()['id']);
            header('Location: /pagers/' . $pagerId . '?success=repair_created');
        } catch (\Exception $e) {
            header('Location: /pagers/' . $pagerId . '?error=' . urlencode($e->getMessage()));
        }
        exit;
    }

    private function handleReceiptUpload(): ?string {
        if (empty($_FILES['receipt']['name'])) return null;

        $file = $_FILES['receipt'];
        if ($file['error'] !== UPLOAD_ERR_OK) throw new \Exception('Fejl ved upload af kvittering');

        $allowed = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
        $mime    = mime_content_type($file['tmp_name']);
        if (!in_array($mime, $allowed)) throw new \Exception('Kvittering skal være PDF, JPG eller PNG');

        if ($file['size'] > 5 * 1024 * 1024) throw new \Exception('Kvittering må maksimalt være 5 MB');

        $ext     = pathinfo($file['name'], PATHINFO_EXTENSION);
        $name    = bin2hex(random_bytes(16)) . '.' . strtolower($ext);
        $dir     = __DIR__ . '/../../public/uploads/receipts/';

        if (!is_dir($dir)) mkdir($dir, 0755, true);
        if (!move_uploaded_file($file['tmp_name'], $dir . $name)) throw new \Exception('Kunne ikke gemme kvittering');

        return $name;
    }
}

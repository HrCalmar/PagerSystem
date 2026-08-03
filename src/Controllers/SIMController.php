<?php
namespace App\Controllers;

use App\Services\SIMService;
use App\Config\Database;
use App\Core\{Auth, BaseController};

class SIMController extends BaseController {
    private SIMService $service;

    public function __construct() {
        $this->service = new SIMService();
    }

    public function store(string $pagerId): void {
        $this->requireCsrf();

        try {
            $data = [
                'sim_number'   => trim($_POST['sim_number']),
                'phone_number' => trim($_POST['phone_number']),
                'notes'        => trim($_POST['notes'] ?? '')
            ];

            if (empty($data['sim_number']) || empty($data['phone_number'])) {
                throw new \Exception('SIM-nummer og telefonnummer skal udfyldes');
            }

            $this->service->add((int)$pagerId, $data, Auth::user()['id']);
            header('Location: /pagers/' . $pagerId . '?success=sim_added');
        } catch (\Exception $e) {
            header('Location: /pagers/' . $pagerId . '?error=' . urlencode($e->getMessage()));
        }
        exit;
    }

    public function deactivate(string $simId): void {
        $this->requireCsrf();

        try {
            $db   = Database::getInstance();
            $stmt = $db->prepare("SELECT pager_id FROM sim_cards WHERE id = ?");
            $stmt->execute([$simId]);
            $pagerId = $stmt->fetchColumn();

            $this->service->deactivate((int)$simId, Auth::user()['id']);
            header('Location: /pagers/' . $pagerId . '?success=sim_deactivated');
        } catch (\Exception $e) {
            header('Location: /pagers/' . ($pagerId ?? '') . '?error=' . urlencode($e->getMessage()));
        }
        exit;
    }
}

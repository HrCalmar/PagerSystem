<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Services\AuditService;

class AuditController extends BaseController {
    private AuditService $service;

    public function __construct() {
        $this->service = new AuditService();
    }

    public function index(): void {
        $filters = [
            'user_id'     => $_GET['user_id']     ?? '',
            'action_type' => $_GET['action_type'] ?? '',
            'entity_type' => $_GET['entity_type'] ?? '',
            'date_from'   => $_GET['date_from']   ?? '',
            'date_to'     => $_GET['date_to']     ?? ''
        ];

        $perPage = 100;
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $total   = $this->service->countFiltered($filters);
        $logs    = $this->service->getFiltered($filters, $page, $perPage);

        $users       = $this->service->getUsers();
        $actionTypes = $this->service->getActionTypes();
        $entityTypes = $this->service->getEntityTypes();

        require __DIR__ . '/../../views/audit/index.php';
    }

    public function show(string $id): void {
        $log = $this->service->getEntryById((int)$id);
        if (!$log) $this->abort(404, 'Log entry ikke fundet');

        require __DIR__ . '/../../views/audit/show.php';
    }
}

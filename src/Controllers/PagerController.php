<?php
namespace App\Controllers;

use App\Services\PagerService;
use App\Core\{Auth, BaseController};

class PagerController extends BaseController {
    private PagerService $service;

    public function __construct() {
        $this->service = new PagerService();
    }

    public function index(): void {
        $filters = [
            'search'        => $_GET['search'] ?? '',
            'status'        => $_GET['status'] ?? '',
            'show_archived' => isset($_GET['archived']) && $_GET['archived'] === '1'
        ];

        $perPage = 50;
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $total   = $this->service->countAll($filters);
        $pagers  = $this->service->getAll($filters, $page, $perPage);

        require __DIR__ . '/../../views/pagers/index.php';
    }

    public function show(string $id): void {
        $pager = $this->service->getById((int)$id);
        if (!$pager) $this->abort(404, 'Pager ikke fundet');

        $history = $this->service->getHistory((int)$id);
        $repairs = $this->service->getRepairs((int)$id);

        require __DIR__ . '/../../views/pagers/show.php';
    }

    public function create(): void {
        $staff = $this->service->getActiveStaff();
        require __DIR__ . '/../../views/pagers/create.php';
    }

    public function store(): void {
        $this->requireCsrf();

        try {
            $data = [
                'serial_number'  => trim($_POST['serial_number']),
                'article_number' => trim($_POST['article_number'] ?? ''),
                'purchase_date'  => trim($_POST['purchase_date']  ?? '')
            ];

            if (empty($data['serial_number'])) {
                throw new \Exception('Serienummer skal udfyldes');
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
        if (!$pager) $this->abort(404, 'Pager ikke fundet');

        require __DIR__ . '/../../views/pagers/edit.php';
    }

    public function update(string $id): void {
        $this->requireCsrf();

        try {
            $data = [
                'serial_number'  => trim($_POST['serial_number']),
                'article_number' => trim($_POST['article_number'] ?? ''),
                'purchase_date'  => trim($_POST['purchase_date']  ?? '')
            ];

            if (empty($data['serial_number'])) {
                throw new \Exception('Serienummer skal udfyldes');
            }

            $this->service->update((int)$id, $data);
            header('Location: /pagers/' . $id . '?success=updated');
        } catch (\Exception $e) {
            header('Location: /pagers/' . $id . '/edit?error=' . urlencode($e->getMessage()));
        }
        exit;
    }

    public function archive(string $id): void {
        $this->requireCsrf();

        try {
            $this->service->archive((int)$id, Auth::user()['id']);
            header('Location: /pagers?success=archived');
        } catch (\Exception $e) {
            header('Location: /pagers/' . $id . '?error=' . urlencode($e->getMessage()));
        }
        exit;
    }

    public function restore(string $id): void {
        $this->requireCsrf();

        try {
            $this->service->restore((int)$id, Auth::user()['id']);
            header('Location: /pagers/' . $id . '?success=restored');
        } catch (\Exception $e) {
            header('Location: /pagers?archived=1&error=' . urlencode($e->getMessage()));
        }
        exit;
    }
}

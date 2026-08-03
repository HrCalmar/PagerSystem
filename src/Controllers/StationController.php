<?php
namespace App\Controllers;

use App\Core\{Auth, BaseController};
use App\Services\StationService;

class StationController extends BaseController {
    private StationService $service;

    public function __construct() {
        $this->service = new StationService();
    }

    public function index(): void {
        $stations = $this->service->getAll();
        require __DIR__ . '/../../views/stations/index.php';
    }

    public function show(string $id): void {
        $station = $this->service->getById((int)$id);
        if (!$station) $this->abort(404, 'Station ikke fundet');

        $staff = $this->service->getStaff((int)$id);
        require __DIR__ . '/../../views/stations/show.php';
    }

    public function create(): void {
        require __DIR__ . '/../../views/stations/create.php';
    }

    public function store(): void {
        $this->requireCsrf();

        try {
            $this->service->create($_POST, Auth::user()['id']);
            header('Location: /stations?success=created');
        } catch (\Exception $e) {
            header('Location: /stations/create?error=' . urlencode($e->getMessage()));
        }
        exit;
    }

    public function edit(string $id): void {
        $station = $this->service->getById((int)$id);
        if (!$station) $this->abort(404, 'Station ikke fundet');

        require __DIR__ . '/../../views/stations/edit.php';
    }

    public function update(string $id): void {
        $this->requireCsrf();

        try {
            $this->service->update((int)$id, $_POST, Auth::user()['id']);
            header('Location: /stations/' . $id . '?success=updated');
        } catch (\Exception $e) {
            header('Location: /stations/' . $id . '/edit?error=' . urlencode($e->getMessage()));
        }
        exit;
    }

    public function delete(string $id): void {
        $this->requireCsrf();

        try {
            $this->service->delete((int)$id, Auth::user()['id']);
            header('Location: /stations?success=deleted');
        } catch (\Exception $e) {
            header('Location: /stations/' . $id . '?error=' . urlencode($e->getMessage()));
        }
        exit;
    }
}

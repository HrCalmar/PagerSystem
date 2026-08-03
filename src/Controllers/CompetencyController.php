<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Services\CompetencyService;

class CompetencyController extends BaseController {
    private CompetencyService $service;

    public function __construct() {
        $this->service = new CompetencyService();
    }

    public function index(): void {
        $competencies = $this->service->getAll();
        require __DIR__ . '/../../views/competencies/index.php';
    }

    public function create(): void {
        require __DIR__ . '/../../views/competencies/create.php';
    }

    public function store(): void {
        $this->requireCsrf();

        try {
            $this->service->create(
                trim($_POST['name']),
                trim($_POST['description'] ?? '') ?: null,
                isset($_POST['requires_renewal'])
            );
            header('Location: /competencies?success=created');
        } catch (\Exception $e) {
            header('Location: /competencies/create?error=' . urlencode($e->getMessage()));
        }
        exit;
    }

    public function show(string $id): void {
        $competency = $this->service->getById((int)$id);
        if (!$competency) $this->abort(404, 'Kompetence ikke fundet');

        $staffWithCompetency = $this->service->getStaffWithCompetency((int)$id);
        require __DIR__ . '/../../views/competencies/show.php';
    }

    public function edit(string $id): void {
        $competency = $this->service->getById((int)$id);
        if (!$competency) $this->abort(404, 'Kompetence ikke fundet');

        require __DIR__ . '/../../views/competencies/edit.php';
    }

    public function update(string $id): void {
        $this->requireCsrf();

        try {
            $this->service->update(
                (int)$id,
                trim($_POST['name']),
                trim($_POST['description'] ?? '') ?: null,
                isset($_POST['requires_renewal'])
            );
            header('Location: /competencies/' . $id . '?success=updated');
        } catch (\Exception $e) {
            header('Location: /competencies/' . $id . '/edit?error=' . urlencode($e->getMessage()));
        }
        exit;
    }

    public function delete(string $id): void {
        $this->requireCsrf();

        try {
            $this->service->delete((int)$id);
            header('Location: /competencies?success=deleted');
        } catch (\Exception $e) {
            header('Location: /competencies/' . $id . '?error=' . urlencode($e->getMessage()));
        }
        exit;
    }

    public function expiring(): void {
        $days                 = (int)($_GET['days'] ?? 30);
        $expiringCompetencies = $this->service->getExpiring($days);
        require __DIR__ . '/../../views/competencies/expiring.php';
    }
}

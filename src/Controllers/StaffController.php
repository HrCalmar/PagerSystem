<?php
namespace App\Controllers;

use App\Services\{StaffService, StaffWorkflowService, PagerService};
use App\Config\Database;
use App\Core\{Auth, BaseController};
use PDO;

class StaffController extends BaseController {
    private StaffService $service;
    private StaffWorkflowService $workflow;
    private PagerService $pagerService;
    private PDO $db;

    public function __construct() {
        $this->service      = new StaffService();
        $this->workflow     = new StaffWorkflowService();
        $this->pagerService = new PagerService();
        $this->db           = Database::getInstance();
    }

    public function index(): void {
        $filters = [
            'search'       => $_GET['search'] ?? '',
            'status'       => $_GET['status'] ?? '',
            'show_deleted' => isset($_GET['deleted']) && $_GET['deleted'] === '1'
        ];

        $staff = $this->service->getAll($filters);
        require __DIR__ . '/../../views/staff/index.php';
    }

    public function show(string $id): void {
        $staff = $this->service->getById((int)$id, true);
        if (!$staff) $this->abort(404, 'Brandmand ikke fundet');

        $stations        = $this->service->getStations((int)$id);
        $competencies    = $this->service->getCompetencies((int)$id);
        $activePagers    = $this->service->getActivePagers((int)$id);
        $pagerHistory    = $this->service->getPagers((int)$id);
        $allStations     = $this->db->query("SELECT id, name FROM stations WHERE deleted_at IS NULL ORDER BY name")->fetchAll();
        $allCompetencies = $this->db->query("SELECT id, name FROM competencies ORDER BY name")->fetchAll();
        $availablePagers = $this->pagerService->getAvailable();
        $isDeleted       = !empty($staff['deleted_at']);

        require __DIR__ . '/../../views/staff/show.php';
    }

    public function create(): void {
        $stations = $this->db->query("SELECT id, name FROM stations WHERE deleted_at IS NULL ORDER BY name")->fetchAll();
        require __DIR__ . '/../../views/staff/create.php';
    }

    public function store(): void {
        $this->requireCsrf();

        $this->db->beginTransaction();
        try {
            $data = [
                'name'            => trim($_POST['name']),
                'employee_number' => trim($_POST['employee_number']),
                'ric_code'        => preg_replace('/\D/', '', $_POST['ric_code'] ?? ''),
                'odin_id'         => preg_replace('/\D/', '', $_POST['odin_id'] ?? ''),
            ];

            if (empty($data['name'])) throw new \Exception('Navn skal udfyldes');
            if (empty($data['employee_number'])) throw new \Exception('Lønnummer skal udfyldes');
            if (empty($_POST['station_ids']) || !is_array($_POST['station_ids'])) {
                throw new \Exception('Du skal vælge mindst én station');
            }

            $stmt = $this->db->prepare("SELECT COUNT(*) FROM staff WHERE employee_number = ? AND deleted_at IS NULL");
            $stmt->execute([$data['employee_number']]);
            if ($stmt->fetchColumn() > 0) throw new \Exception('Lønnummer eksisterer allerede');

            $id = $this->service->create($data);

            $stmt = $this->db->prepare(
                "INSERT INTO station_assignments (staff_id, station_id, start_date) VALUES (?, ?, ?)"
            );
            foreach ($_POST['station_ids'] as $stationId) {
                $stmt->execute([$id, (int)$stationId, date('Y-m-d')]);
            }

            $this->db->commit();
            header('Location: /staff/' . $id . '?success=created');
        } catch (\Exception $e) {
            $this->db->rollBack();
            header('Location: /staff/create?error=' . urlencode($e->getMessage()) .
                   '&name=' . urlencode($_POST['name'] ?? '') .
                   '&employee_number=' . urlencode($_POST['employee_number'] ?? ''));
        }
        exit;
    }

    public function edit(string $id): void {
        $staff = $this->service->getById((int)$id);
        if (!$staff) $this->abort(404, 'Brandmand ikke fundet');

        require __DIR__ . '/../../views/staff/edit.php';
    }

    public function update(string $id): void {
        $this->requireCsrf();

        try {
            $data = [
                'name'            => trim($_POST['name']),
                'employee_number' => trim($_POST['employee_number']),
                'ric_code'        => preg_replace('/\D/', '', $_POST['ric_code'] ?? ''),
                'odin_id'         => preg_replace('/\D/', '', $_POST['odin_id'] ?? ''),
            ];

            if (empty($data['name'])) throw new \Exception('Navn skal udfyldes');
            if (empty($data['employee_number'])) throw new \Exception('Lønnummer skal udfyldes');

            $stmt = $this->db->prepare("SELECT COUNT(*) FROM staff WHERE employee_number = ? AND id != ? AND deleted_at IS NULL");
            $stmt->execute([$data['employee_number'], $id]);
            if ($stmt->fetchColumn() > 0) throw new \Exception('Lønnummer eksisterer allerede på en anden brandmand');

            $this->service->update((int)$id, $data);
            header('Location: /staff/' . $id . '?success=updated');
        } catch (\Exception $e) {
            header('Location: /staff/' . $id . '/edit?error=' . urlencode($e->getMessage()));
        }
        exit;
    }

    public function deactivate(string $id): void {
        $this->requireCsrf();
        try {
            $this->workflow->deactivate((int)$id, Auth::user()['id']);
            header('Location: /staff/' . $id . '?success=deactivated');
        } catch (\Exception $e) {
            header('Location: /staff/' . $id . '?error=' . urlencode($e->getMessage()));
        }
        exit;
    }

    public function reactivate(string $id): void {
        $this->requireCsrf();
        try {
            $this->workflow->reactivate((int)$id, Auth::user()['id']);
            header('Location: /staff/' . $id . '?success=reactivated');
        } catch (\Exception $e) {
            header('Location: /staff/' . $id . '?error=' . urlencode($e->getMessage()));
        }
        exit;
    }

    public function delete(string $id): void {
        $this->requireCsrf();
        try {
            $this->workflow->delete((int)$id, Auth::user()['id']);
            header('Location: /staff?success=deleted');
        } catch (\Exception $e) {
            header('Location: /staff/' . $id . '?error=' . urlencode($e->getMessage()));
        }
        exit;
    }

    public function addStation(string $id): void {
        $this->requireCsrf();
        try {
            $this->workflow->addStation(
                (int)$id,
                (int)$_POST['station_id'],
                $_POST['start_date'] ?? date('Y-m-d'),
                Auth::user()['id']
            );
            header('Location: /staff/' . $id . '?success=station_added');
        } catch (\Exception $e) {
            header('Location: /staff/' . $id . '?error=' . urlencode($e->getMessage()));
        }
        exit;
    }

    public function removeStation(string $id): void {
        $this->requireCsrf();
        try {
            $this->workflow->removeStation(
                (int)$_POST['assignment_id'],
                date('Y-m-d'),
                Auth::user()['id']
            );
            header('Location: /staff/' . $id . '?success=station_removed');
        } catch (\Exception $e) {
            header('Location: /staff/' . $id . '?error=' . urlencode($e->getMessage()));
        }
        exit;
    }

    public function addCompetency(string $id): void {
        $this->requireCsrf();
        try {
            $this->workflow->addCompetency(
                (int)$id,
                (int)$_POST['competency_id'],
                $_POST['obtained_date'] ?: null,
                $_POST['expiry_date']   ?: null,
                Auth::user()['id']
            );
            header('Location: /staff/' . $id . '?success=competency_added');
        } catch (\Exception $e) {
            header('Location: /staff/' . $id . '?error=' . urlencode($e->getMessage()));
        }
        exit;
    }

    public function removeCompetency(string $id): void {
        $this->requireCsrf();
        try {
            $this->workflow->removeCompetency(
                (int)$_POST['staff_competency_id'],
                Auth::user()['id']
            );
            header('Location: /staff/' . $id . '?success=competency_removed');
        } catch (\Exception $e) {
            header('Location: /staff/' . $id . '?error=' . urlencode($e->getMessage()));
        }
        exit;
    }
}

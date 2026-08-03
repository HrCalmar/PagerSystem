<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Services\UserService;

class UserController extends BaseController {
    private UserService $service;

    public function __construct() {
        $this->service = new UserService();
    }

    public function index(): void {
        $users = $this->service->getAll();
        require __DIR__ . '/../../views/users/index.php';
    }

    public function create(): void {
        $stations = $this->service->getStations();
        require __DIR__ . '/../../views/users/create.php';
    }

    public function store(): void {
        $this->requireCsrf();

        try {
            $this->service->create(
                trim($_POST['username']),
                trim($_POST['password']),
                trim($_POST['name']),
                $_POST['role'],
                !empty($_POST['station_id']) ? (int)$_POST['station_id'] : null
            );
            header('Location: /users?success=created');
        } catch (\Exception $e) {
            header('Location: /users/create?error=' . urlencode($e->getMessage()));
        }
        exit;
    }

    public function edit(string $id): void {
        $user = $this->service->getById((int)$id);
        if (!$user) $this->abort(404, 'Bruger ikke fundet');

        $stations = $this->service->getStations();
        require __DIR__ . '/../../views/users/edit.php';
    }

    public function update(string $id): void {
        $this->requireCsrf();

        try {
            $this->service->update(
                (int)$id,
                trim($_POST['name']),
                $_POST['role'],
                !empty($_POST['station_id']) ? (int)$_POST['station_id'] : null,
                $_POST['status']
            );
            header('Location: /users?success=updated');
        } catch (\Exception $e) {
            header('Location: /users/' . $id . '/edit?error=' . urlencode($e->getMessage()));
        }
        exit;
    }

    public function resetPassword(string $id): void {
        $this->requireCsrf();

        try {
            $this->service->resetPassword((int)$id, trim($_POST['password']));
            header('Location: /users?success=password_reset');
        } catch (\Exception $e) {
            header('Location: /users/' . $id . '/edit?error=' . urlencode($e->getMessage()));
        }
        exit;
    }

    public function loginAttempts(): void {
        $db      = \App\Config\Database::getInstance();
        $perPage = 100;
        $page    = max(1, (int)($_GET['page'] ?? 1));

        $filter = $_GET['filter'] ?? '';
        $where  = $filter === 'failed' ? 'WHERE success = 0' : ($filter === 'success' ? 'WHERE success = 1' : '');

        $total = (int)$db->query("SELECT COUNT(*) FROM login_attempts $where")->fetchColumn();

        $stmt = $db->prepare(
            "SELECT * FROM login_attempts $where ORDER BY attempted_at DESC LIMIT ? OFFSET ?"
        );
        $stmt->execute([$perPage, ($page - 1) * $perPage]);
        $attempts = $stmt->fetchAll();

        require __DIR__ . '/../../views/admin/login-attempts.php';
    }
}

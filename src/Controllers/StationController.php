<?php
// src/Controllers/StationController.php
namespace App\Controllers;

use App\Config\Database;
use App\Core\{CSRF, Auth};

class StationController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function index(): void {
        $stmt = $this->db->query(
            "SELECT s.*,
                    COUNT(DISTINCT sa.staff_id) as staff_count,
                    COUNT(DISTINCT pa.pager_id) as pager_count
             FROM stations s
             LEFT JOIN station_assignments sa ON sa.station_id = s.id AND sa.end_date IS NULL
             LEFT JOIN pager_assignments pa ON pa.staff_id = sa.staff_id AND pa.returned_at IS NULL
             WHERE s.deleted_at IS NULL
             GROUP BY s.id
             ORDER BY s.name"
        );
        $stations = $stmt->fetchAll();

        require __DIR__ . '/../../views/stations/index.php';
    }

    public function show(string $id): void {
        $stmt = $this->db->prepare("SELECT * FROM stations WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$id]);
        $station = $stmt->fetch();

        if (!$station) {
            http_response_code(404);
            die('Station ikke fundet');
        }

        $stmt = $this->db->prepare(
            "SELECT s.*,
                    COUNT(DISTINCT pa.id) as pager_count,
                    GROUP_CONCAT(DISTINCT c.name SEPARATOR ', ') as competencies
             FROM staff s
             INNER JOIN station_assignments sa ON sa.staff_id = s.id
             LEFT JOIN pager_assignments pa ON pa.staff_id = s.id AND pa.returned_at IS NULL
             LEFT JOIN staff_competencies sc ON sc.staff_id = s.id
             LEFT JOIN competencies c ON c.id = sc.competency_id
             WHERE sa.station_id = ? AND sa.end_date IS NULL AND s.deleted_at IS NULL
             GROUP BY s.id
             ORDER BY s.name"
        );
        $stmt->execute([$id]);
        $staff = $stmt->fetchAll();

        require __DIR__ . '/../../views/stations/show.php';
    }

    public function create(): void {
        require __DIR__ . '/../../views/stations/create.php';
    }

    public function store(): void {
        if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
            die('Invalid CSRF token');
        }

        try {
            $name = trim($_POST['name'] ?? '');
            if (empty($name)) {
                throw new \Exception('Navn skal udfyldes');
            }

            $stmt = $this->db->prepare(
                "INSERT INTO stations (name, code, address, phone, email) VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $name,
                $_POST['code'] ?: null,
                $_POST['address'] ?: null,
                $_POST['phone'] ?: null,
                $_POST['email'] ?: null,
            ]);

            header('Location: /stations?success=created');
        } catch (\Exception $e) {
            header('Location: /stations/create?error=' . urlencode($e->getMessage()));
        }
        exit;
    }

    public function edit(string $id): void {
        $stmt = $this->db->prepare("SELECT * FROM stations WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$id]);
        $station = $stmt->fetch();

        if (!$station) {
            http_response_code(404);
            die('Station ikke fundet');
        }

        require __DIR__ . '/../../views/stations/edit.php';
    }

    public function update(string $id): void {
        if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
            die('Invalid CSRF token');
        }

        try {
            $name = trim($_POST['name'] ?? '');
            if (empty($name)) {
                throw new \Exception('Navn skal udfyldes');
            }

            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM stations WHERE name = ? AND id != ? AND deleted_at IS NULL"
            );
            $stmt->execute([$name, $id]);
            if ($stmt->fetchColumn() > 0) {
                throw new \Exception('En station med dette navn eksisterer allerede');
            }

            $stmt = $this->db->prepare(
                "UPDATE stations SET name = ?, code = ?, address = ?, phone = ?, email = ? WHERE id = ?"
            );
            $stmt->execute([
                $name,
                $_POST['code'] ?: null,
                $_POST['address'] ?: null,
                $_POST['phone'] ?: null,
                $_POST['email'] ?: null,
                $id,
            ]);

            $audit = new \App\Services\AuditService();
            $audit->log(Auth::user()['id'], 'update_station', 'station', (int)$id, [], ['name' => $name]);

            header('Location: /stations/' . $id . '?success=updated');
        } catch (\Exception $e) {
            header('Location: /stations/' . $id . '/edit?error=' . urlencode($e->getMessage()));
        }
        exit;
    }

    public function delete(string $id): void {
        if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
            die('Invalid CSRF token');
        }

        try {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM station_assignments WHERE station_id = ? AND end_date IS NULL"
            );
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                throw new \Exception('Kan ikke slette station med aktive brandfolk');
            }

            $stmt = $this->db->prepare("UPDATE stations SET deleted_at = NOW() WHERE id = ?");
            $stmt->execute([$id]);

            header('Location: /stations?success=deleted');
        } catch (\Exception $e) {
            header('Location: /stations/' . $id . '?error=' . urlencode($e->getMessage()));
        }
        exit;
    }
}
<?php
namespace App\Controllers;

use App\Config\Database;
use App\Core\{Auth, BaseController};
use PDO;

class SearchController extends BaseController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function index(): void {
        $query           = trim($_GET['q'] ?? '');
        $includeArchived = isset($_GET['archived']) && $_GET['archived'] === '1';

        if (empty($query)) {
            header('Location: /dashboard');
            exit;
        }

        $results = [
            'pagers'   => $this->searchPagers($query, $includeArchived),
            'staff'    => $this->searchStaff($query),
            'stations' => $this->searchStations($query)
        ];

        $title = 'Søgeresultater';
        require __DIR__ . '/../../views/search/results.php';
    }

    public function ajax(): void {
        header('Content-Type: application/json; charset=utf-8');

        $query = trim($_GET['q'] ?? '');
        if (strlen($query) < 2) {
            echo json_encode([]);
            exit;
        }

        $search  = '%' . $query . '%';
        $results = [];

        $pagerSql = "SELECT DISTINCT p.id, p.serial_number, p.status, s.phone_number, st.name as staff_name
                     FROM pagers p
                     LEFT JOIN sim_cards s ON s.pager_id = p.id AND s.status = 'active'
                     LEFT JOIN pager_assignments pa ON pa.pager_id = p.id AND pa.returned_at IS NULL
                     LEFT JOIN staff st ON st.id = pa.staff_id
                     WHERE p.status != 'archived'
                     AND (p.serial_number LIKE ? OR s.phone_number LIKE ? OR s.sim_number LIKE ?)";

        if (Auth::isStationUser()) {
            $pagerSql .= " AND (p.status = 'in_stock' OR pa.staff_id IN (
                SELECT staff_id FROM station_assignments WHERE station_id = ? AND end_date IS NULL
            ))";
            $stmt = $this->db->prepare($pagerSql . " ORDER BY p.serial_number LIMIT 5");
            $stmt->execute([$search, $search, $search, Auth::getStationId()]);
        } else {
            $stmt = $this->db->prepare($pagerSql . " ORDER BY p.serial_number LIMIT 5");
            $stmt->execute([$search, $search, $search]);
        }

        foreach ($stmt->fetchAll() as $row) {
            $label = $row['serial_number'];
            if ($row['phone_number']) $label .= ' (' . $row['phone_number'] . ')';
            if ($row['staff_name'])   $label .= ' — ' . $row['staff_name'];
            $results[] = ['type' => 'pager', 'label' => $label, 'url' => '/pagers/' . $row['id']];
        }

        $staffSql = "SELECT s.id, s.name, s.employee_number
                     FROM staff s
                     LEFT JOIN station_assignments sa ON sa.staff_id = s.id AND sa.end_date IS NULL
                     WHERE s.deleted_at IS NULL
                     AND (s.name LIKE ? OR s.employee_number LIKE ?)";

        if (Auth::isStationUser()) {
            $staffSql .= " AND sa.station_id = ?";
            $stmt = $this->db->prepare($staffSql . " GROUP BY s.id ORDER BY s.name LIMIT 5");
            $stmt->execute([$search, $search, Auth::getStationId()]);
        } else {
            $stmt = $this->db->prepare($staffSql . " GROUP BY s.id ORDER BY s.name LIMIT 5");
            $stmt->execute([$search, $search]);
        }

        foreach ($stmt->fetchAll() as $row) {
            $label     = $row['name'];
            if ($row['employee_number']) $label .= ' (' . $row['employee_number'] . ')';
            $results[] = ['type' => 'staff', 'label' => $label, 'url' => '/staff/' . $row['id']];
        }

        echo json_encode($results);
        exit;
    }

    private function searchPagers(string $query, bool $includeArchived): array {
        $search = '%' . $query . '%';
        $sql    = "SELECT DISTINCT p.id, p.serial_number, p.article_number, p.status,
                          s.phone_number, st.name as staff_name, st.id as staff_id
                   FROM pagers p
                   LEFT JOIN sim_cards s ON s.pager_id = p.id AND s.status = 'active'
                   LEFT JOIN pager_assignments pa ON pa.pager_id = p.id AND pa.returned_at IS NULL
                   LEFT JOIN staff st ON st.id = pa.staff_id
                   WHERE (p.serial_number LIKE ?
                        OR p.article_number LIKE ?
                        OR s.phone_number LIKE ?
                        OR s.sim_number LIKE ?)";

        if (!$includeArchived) {
            $sql .= " AND p.status != 'archived'";
        }

        if (Auth::isStationUser()) {
            $sql .= " AND (p.status = 'in_stock' OR pa.staff_id IN (
                SELECT staff_id FROM station_assignments
                WHERE station_id = ? AND end_date IS NULL
            ))";
            $stmt = $this->db->prepare($sql . " ORDER BY p.serial_number");
            $stmt->execute([$search, $search, $search, $search, Auth::getStationId()]);
        } else {
            $stmt = $this->db->prepare($sql . " ORDER BY p.serial_number");
            $stmt->execute([$search, $search, $search, $search]);
        }

        return $stmt->fetchAll();
    }

    private function searchStaff(string $query): array {
        $search = '%' . $query . '%';
        $sql    = "SELECT s.id, s.name, s.employee_number, s.status,
                          GROUP_CONCAT(DISTINCT st.name ORDER BY st.name SEPARATOR ', ') as stations
                   FROM staff s
                   LEFT JOIN station_assignments sa ON sa.staff_id = s.id AND sa.end_date IS NULL
                   LEFT JOIN stations st ON st.id = sa.station_id
                   WHERE s.deleted_at IS NULL
                   AND (s.name LIKE ? OR s.employee_number LIKE ?)";

        if (Auth::isStationUser()) {
            $sql .= " AND sa.station_id = ?";
            $stmt = $this->db->prepare($sql . " GROUP BY s.id ORDER BY s.name");
            $stmt->execute([$search, $search, Auth::getStationId()]);
        } else {
            $stmt = $this->db->prepare($sql . " GROUP BY s.id ORDER BY s.name");
            $stmt->execute([$search, $search]);
        }

        return $stmt->fetchAll();
    }

    private function searchStations(string $query): array {
        if (Auth::isStationUser()) {
            return [];
        }

        $search = '%' . $query . '%';
        $stmt   = $this->db->prepare(
            "SELECT s.id, s.name, s.code,
                    COUNT(DISTINCT sa.staff_id) as staff_count
             FROM stations s
             LEFT JOIN station_assignments sa ON sa.station_id = s.id AND sa.end_date IS NULL
             WHERE s.name LIKE ? OR s.code LIKE ?
             GROUP BY s.id
             ORDER BY s.name"
        );
        $stmt->execute([$search, $search]);

        return $stmt->fetchAll();
    }
}

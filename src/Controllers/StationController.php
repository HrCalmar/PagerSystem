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
             GROUP BY s.id
             ORDER BY s.name"
        );
        $stations = $stmt->fetchAll();
        
        require __DIR__ . '/../../views/stations/index.php';
    }
    
    public function show(string $id): void {
        $stmt = $this->db->prepare("SELECT * FROM stations WHERE id = ?");
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
             WHERE sa.station_id = ? AND sa.end_date IS NULL
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
        
        $stmt = $this->db->prepare("INSERT INTO stations (name, code) VALUES (?, ?)");
        $stmt->execute([$_POST['name'], $_POST['code'] ?? null]);
        
        header('Location: /stations');
        exit;
    }
}
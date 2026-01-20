<?php
// src/Controllers/SearchController.php
namespace App\Controllers;

use App\Config\Database;
use App\Core\Auth;
use PDO;

class SearchController {
    private PDO $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function search(): void {
        $query = trim($_GET['q'] ?? '');
        
        if (strlen($query) < 2) {
            header('Location: /dashboard');
            exit;
        }
        
        $results = [
            'pagers' => $this->searchPagers($query),
            'staff' => $this->searchStaff($query),
            'stations' => $this->searchStations($query)
        ];
        
        $title = 'Søgeresultater';
        require __DIR__ . '/../../views/search/results.php';
    }
    
    private function searchPagers(string $query): array {
        $search = "%{$query}%";
        
        $sql = "SELECT p.id, p.serial_number, p.article_number, p.status,
                       s.phone_number, st.name as staff_name
                FROM pagers p
                LEFT JOIN sim_cards s ON s.pager_id = p.id AND s.status = 'active'
                LEFT JOIN pager_assignments pa ON pa.pager_id = p.id AND pa.returned_at IS NULL
                LEFT JOIN staff st ON st.id = pa.staff_id
                WHERE p.status != 'archived'
                  AND (p.serial_number LIKE ? OR p.article_number LIKE ? OR s.phone_number LIKE ?)
                ORDER BY p.serial_number
                LIMIT 20";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$search, $search, $search]);
        return $stmt->fetchAll();
    }
    
    private function searchStaff(string $query): array {
        $search = "%{$query}%";
        
        $sql = "SELECT s.id, s.name, s.employee_number, s.status,
                       GROUP_CONCAT(DISTINCT st.name SEPARATOR ', ') as stations
                FROM staff s
                LEFT JOIN station_assignments sa ON sa.staff_id = s.id AND sa.end_date IS NULL
                LEFT JOIN stations st ON st.id = sa.station_id
                WHERE s.deleted_at IS NULL
                  AND (s.name LIKE ? OR s.employee_number LIKE ?)
                GROUP BY s.id
                ORDER BY s.name
                LIMIT 20";
        
        if (Auth::isStationUser()) {
            $sql = "SELECT s.id, s.name, s.employee_number, s.status,
                           GROUP_CONCAT(DISTINCT st.name SEPARATOR ', ') as stations
                    FROM staff s
                    INNER JOIN station_assignments sa ON sa.staff_id = s.id AND sa.end_date IS NULL
                    LEFT JOIN stations st ON st.id = sa.station_id
                    WHERE s.deleted_at IS NULL
                      AND sa.station_id = ?
                      AND (s.name LIKE ? OR s.employee_number LIKE ?)
                    GROUP BY s.id
                    ORDER BY s.name
                    LIMIT 20";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([Auth::getStationId(), $search, $search]);
            return $stmt->fetchAll();
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$search, $search]);
        return $stmt->fetchAll();
    }
    
    private function searchStations(string $query): array {
        $search = "%{$query}%";
        
        $sql = "SELECT s.id, s.name, s.code,
                       COUNT(DISTINCT sa.staff_id) as staff_count
                FROM stations s
                LEFT JOIN station_assignments sa ON sa.station_id = s.id AND sa.end_date IS NULL
                WHERE s.name LIKE ? OR s.code LIKE ?
                GROUP BY s.id
                ORDER BY s.name
                LIMIT 20";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$search, $search]);
        return $stmt->fetchAll();
    }
}
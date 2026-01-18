<?php
namespace App\Controllers;

use App\Config\Database;
use App\Core\{CSRF, Auth};
use PDO;

class CompetencyController {
    private PDO $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function index(): void {
        $stmt = $this->db->query(
            "SELECT c.*, 
                    COUNT(sc.id) as staff_count,
                    SUM(CASE WHEN sc.expiry_date < CURDATE() THEN 1 ELSE 0 END) as expired_count,
                    SUM(CASE WHEN sc.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as expiring_soon_count
             FROM competencies c
             LEFT JOIN staff_competencies sc ON sc.competency_id = c.id
             LEFT JOIN staff s ON s.id = sc.staff_id AND s.deleted_at IS NULL
             GROUP BY c.id
             ORDER BY c.name"
        );
        $competencies = $stmt->fetchAll();
        
        require __DIR__ . '/../../views/competencies/index.php';
    }
    
    public function create(): void {
        require __DIR__ . '/../../views/competencies/create.php';
    }
    
    public function store(): void {
        if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
            die('Invalid CSRF token');
        }
        
        try {
            $name = trim($_POST['name']);
            $description = trim($_POST['description'] ?? '');
            $requiresRenewal = isset($_POST['requires_renewal']) ? 1 : 0;
            
            if (empty($name)) {
                throw new \Exception('Navn skal udfyldes');
            }
            
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM competencies WHERE name = ?");
            $stmt->execute([$name]);
            if ($stmt->fetchColumn() > 0) {
                throw new \Exception('Kompetence med dette navn eksisterer allerede');
            }
            
            $stmt = $this->db->prepare(
                "INSERT INTO competencies (name, description, requires_renewal) VALUES (?, ?, ?)"
            );
            $stmt->execute([$name, $description ?: null, $requiresRenewal]);
            
            header('Location: /competencies?success=created');
        } catch (\Exception $e) {
            header('Location: /competencies/create?error=' . urlencode($e->getMessage()));
        }
        exit;
    }
    
    public function show(string $id): void {
        $stmt = $this->db->prepare("SELECT * FROM competencies WHERE id = ?");
        $stmt->execute([$id]);
        $competency = $stmt->fetch();
        
        if (!$competency) {
            http_response_code(404);
            die('Kompetence ikke fundet');
        }
        
        $stmt = $this->db->prepare(
            "SELECT s.*, sc.obtained_date, sc.expiry_date, sc.id as staff_competency_id,
                    GROUP_CONCAT(DISTINCT st.name SEPARATOR ', ') as stations
             FROM staff_competencies sc
             INNER JOIN staff s ON s.id = sc.staff_id
             LEFT JOIN station_assignments sa ON sa.staff_id = s.id AND sa.end_date IS NULL
             LEFT JOIN stations st ON st.id = sa.station_id
             WHERE sc.competency_id = ? AND s.deleted_at IS NULL
             GROUP BY s.id, sc.id
             ORDER BY s.name"
        );
        $stmt->execute([$id]);
        $staffWithCompetency = $stmt->fetchAll();
        
        require __DIR__ . '/../../views/competencies/show.php';
    }
    
    public function edit(string $id): void {
        $stmt = $this->db->prepare("SELECT * FROM competencies WHERE id = ?");
        $stmt->execute([$id]);
        $competency = $stmt->fetch();
        
        if (!$competency) {
            http_response_code(404);
            die('Kompetence ikke fundet');
        }
        
        require __DIR__ . '/../../views/competencies/edit.php';
    }
    
    public function update(string $id): void {
        if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
            die('Invalid CSRF token');
        }
        
        try {
            $name = trim($_POST['name']);
            $description = trim($_POST['description'] ?? '');
            $requiresRenewal = isset($_POST['requires_renewal']) ? 1 : 0;
            
            if (empty($name)) {
                throw new \Exception('Navn skal udfyldes');
            }
            
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM competencies WHERE name = ? AND id != ?");
            $stmt->execute([$name, $id]);
            if ($stmt->fetchColumn() > 0) {
                throw new \Exception('Kompetence med dette navn eksisterer allerede');
            }
            
            $stmt = $this->db->prepare(
                "UPDATE competencies SET name = ?, description = ?, requires_renewal = ? WHERE id = ?"
            );
            $stmt->execute([$name, $description ?: null, $requiresRenewal, $id]);
            
            header('Location: /competencies/' . $id . '?success=updated');
        } catch (\Exception $e) {
            header('Location: /competencies/' . $id . '/edit?error=' . urlencode($e->getMessage()));
        }
        exit;
    }
    
    public function delete(string $id): void {
        if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
            die('Invalid CSRF token');
        }
        
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM staff_competencies WHERE competency_id = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                throw new \Exception('Kan ikke slette - kompetencen er tildelt til brandfolk');
            }
            
            $stmt = $this->db->prepare("DELETE FROM competencies WHERE id = ?");
            $stmt->execute([$id]);
            
            header('Location: /competencies?success=deleted');
        } catch (\Exception $e) {
            header('Location: /competencies/' . $id . '?error=' . urlencode($e->getMessage()));
        }
        exit;
    }
    
    public function expiring(): void {
        $days = (int)($_GET['days'] ?? 30);
        
        $stmt = $this->db->prepare(
            "SELECT s.id as staff_id, s.name as staff_name, s.employee_number,
                    c.id as competency_id, c.name as competency_name, c.requires_renewal,
                    sc.expiry_date, sc.id as staff_competency_id,
                    GROUP_CONCAT(DISTINCT st.name SEPARATOR ', ') as stations,
                    DATEDIFF(sc.expiry_date, CURDATE()) as days_until_expiry
             FROM staff_competencies sc
             INNER JOIN staff s ON s.id = sc.staff_id AND s.deleted_at IS NULL AND s.status = 'active'
             INNER JOIN competencies c ON c.id = sc.competency_id
             LEFT JOIN station_assignments sa ON sa.staff_id = s.id AND sa.end_date IS NULL
             LEFT JOIN stations st ON st.id = sa.station_id
             WHERE sc.expiry_date IS NOT NULL 
               AND sc.expiry_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
             GROUP BY sc.id
             ORDER BY sc.expiry_date ASC"
        );
        $stmt->execute([$days]);
        $expiringCompetencies = $stmt->fetchAll();
        
        require __DIR__ . '/../../views/competencies/expiring.php';
    }
}

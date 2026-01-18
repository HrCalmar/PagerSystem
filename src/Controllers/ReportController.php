<?php
// src/Controllers/ReportController.php
namespace App\Controllers;

use App\Config\Database;
use App\Core\Auth;
use PDO;

class ReportController {
    private PDO $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function index(): void {
        $stats = [];
        
        // Total pagere
        $stmt = $this->db->query("SELECT COUNT(*) FROM pagers");
        $stats['total_pagers'] = $stmt->fetchColumn();
        
        // Udleverede pagere
        $stmt = $this->db->query("SELECT COUNT(*) FROM pagers WHERE status = 'issued'");
        $stats['issued_pagers'] = $stmt->fetchColumn();
        
        // På lager
        $stmt = $this->db->query("SELECT COUNT(*) FROM pagers WHERE status = 'in_stock'");
        $stats['in_stock'] = $stmt->fetchColumn();
        
        // Til reparation
        $stmt = $this->db->query("SELECT COUNT(*) FROM pagers WHERE status = 'in_repair'");
        $stats['in_repair'] = $stmt->fetchColumn();
        
        // Aktive SIM-kort
        $stmt = $this->db->query("SELECT COUNT(*) FROM sim_cards WHERE status = 'active'");
        $stats['active_sims'] = $stmt->fetchColumn();
        
        // Aktive brandfolk
        $stmt = $this->db->query("SELECT COUNT(*) FROM staff WHERE status = 'active'");
        $stats['active_staff'] = $stmt->fetchColumn();
        
        // Brandfolk uden pager
        $stmt = $this->db->query(
            "SELECT COUNT(DISTINCT s.id) FROM staff s
             LEFT JOIN pager_assignments pa ON pa.staff_id = s.id AND pa.returned_at IS NULL
             WHERE s.status = 'active' AND pa.id IS NULL"
        );
        $stats['staff_without_pager'] = $stmt->fetchColumn();
        
        // Pagere til klargøring
        $stmt = $this->db->query("SELECT COUNT(*) FROM pagers WHERE status = 'for_preparation'");
        $stats['for_preparation'] = $stmt->fetchColumn();
        
        // Defekte pagere
        $stmt = $this->db->query("SELECT COUNT(*) FROM pagers WHERE status = 'defect'");
        $stats['defect'] = $stmt->fetchColumn();
        
        require __DIR__ . '/../../views/reports/index.php';
    }
    
    public function phoneNumbers(): void {
        $stmt = $this->db->query(
            "SELECT p.id as pager_id, p.serial_number, p.article_number, p.status,
                    s.phone_number, s.sim_number,
                    st.id as staff_id, st.name as staff_name, st.employee_number,
                    sta.name as station_name
             FROM pagers p
             LEFT JOIN sim_cards s ON s.pager_id = p.id AND s.status = 'active'
             LEFT JOIN pager_assignments pa ON pa.pager_id = p.id AND pa.returned_at IS NULL
             LEFT JOIN staff st ON st.id = pa.staff_id
             LEFT JOIN station_assignments sas ON sas.staff_id = st.id AND sas.end_date IS NULL
             LEFT JOIN stations sta ON sta.id = sas.station_id
             WHERE s.phone_number IS NOT NULL
             ORDER BY s.phone_number"
        );
        $phones = $stmt->fetchAll();
        
        require __DIR__ . '/../../views/reports/phone_numbers.php';
    }
    
    public function missingPagers(): void {
        $stmt = $this->db->query(
            "SELECT s.id, s.name, s.employee_number,
                    sta.name as station_name,
                    COUNT(pa.id) as pager_count
             FROM staff s
             INNER JOIN station_assignments sas ON sas.staff_id = s.id AND sas.end_date IS NULL
             INNER JOIN stations sta ON sta.id = sas.station_id
             LEFT JOIN pager_assignments pa ON pa.staff_id = s.id AND pa.returned_at IS NULL
             WHERE s.status = 'active'
             GROUP BY s.id
             HAVING pager_count = 0
             ORDER BY sta.name, s.name"
        );
        $staff = $stmt->fetchAll();
        
        require __DIR__ . '/../../views/reports/missing_pagers.php';
    }
    
    public function statusOverview(): void {
        $stats = [];
        
        // Pager status
        $stmt = $this->db->query(
            "SELECT status, COUNT(*) as count FROM pagers GROUP BY status"
        );
        $stats['pager_status'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Pagere til klargøring > 7 dage
        $stmt = $this->db->query(
            "SELECT COUNT(*) FROM pagers 
             WHERE status = 'for_preparation' 
             AND updated_at < DATE_SUB(NOW(), INTERVAL 7 DAY)"
        );
        $stats['preparation_overdue'] = $stmt->fetchColumn();
        
        // Pagere til reparation > 30 dage
        $stmt = $this->db->query(
            "SELECT COUNT(*) FROM pagers p
             INNER JOIN repairs r ON r.pager_id = p.id
             WHERE p.status = 'in_repair' 
             AND r.completed_at IS NULL
             AND r.repair_date < DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );
        $stats['repair_overdue'] = $stmt->fetchColumn();
        
        // Pagere uden SIM
        $stmt = $this->db->query(
            "SELECT COUNT(*) FROM pagers p
             LEFT JOIN sim_cards s ON s.pager_id = p.id AND s.status = 'active'
             WHERE p.status IN ('in_stock', 'issued') AND s.id IS NULL"
        );
        $stats['without_sim'] = $stmt->fetchColumn();
        
        // Aktive brandfolk uden pager
        $stmt = $this->db->query(
            "SELECT COUNT(DISTINCT s.id) FROM staff s
             LEFT JOIN pager_assignments pa ON pa.staff_id = s.id AND pa.returned_at IS NULL
             WHERE s.status = 'active' AND pa.id IS NULL"
        );
        $stats['staff_without_pager'] = $stmt->fetchColumn();
        
        require __DIR__ . '/../../views/reports/status_overview.php';
    }
    
    public function exportPhones(): void {
        $stmt = $this->db->query(
            "SELECT s.phone_number, s.sim_number,
                    p.serial_number, p.article_number,
                    st.name as staff_name, st.employee_number,
                    sta.name as station_name
             FROM sim_cards s
             INNER JOIN pagers p ON p.id = s.pager_id
             LEFT JOIN pager_assignments pa ON pa.pager_id = p.id AND pa.returned_at IS NULL
             LEFT JOIN staff st ON st.id = pa.staff_id
             LEFT JOIN station_assignments sas ON sas.staff_id = st.id AND sas.end_date IS NULL
             LEFT JOIN stations sta ON sta.id = sas.station_id
             WHERE s.status = 'active'
             ORDER BY s.phone_number"
        );
        $phones = $stmt->fetchAll();
        
        $filename = 'telefonnumre_' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        
        // UTF-8 BOM for Excel-kompatibilitet
        fwrite($output, "\xEF\xBB\xBF");
        
        // Header med escape parameter
        fputcsv($output, [
            'Telefonnummer',
            'SIM-nummer',
            'Serienummer',
            'Artikelnummer',
            'Brandmand',
            'Lønnummer',
            'Station'
        ], ',', '"', '\\');
        
        foreach ($phones as $phone) {
            fputcsv($output, [
                $phone['phone_number'],
                $phone['sim_number'],
                $phone['serial_number'],
                $phone['article_number'],
                $phone['staff_name'] ?? '-',
                $phone['employee_number'] ?? '-',
                $phone['station_name'] ?? '-'
            ], ',', '"', '\\');
        }
        
        fclose($output);
        exit;
    }
}
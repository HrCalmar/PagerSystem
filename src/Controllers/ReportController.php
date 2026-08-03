<?php
namespace App\Controllers;

use App\Config\Database;
use App\Core\BaseController;
use PDO;

class ReportController extends BaseController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function index(): void {
        $stats = [];

        $stats['total_pagers']       = $this->db->query("SELECT COUNT(*) FROM pagers")->fetchColumn();
        $stats['issued_pagers']      = $this->db->query("SELECT COUNT(*) FROM pagers WHERE status = 'issued'")->fetchColumn();
        $stats['in_stock']           = $this->db->query("SELECT COUNT(*) FROM pagers WHERE status = 'in_stock'")->fetchColumn();
        $stats['in_repair']          = $this->db->query("SELECT COUNT(*) FROM pagers WHERE status = 'in_repair'")->fetchColumn();
        $stats['active_sims']        = $this->db->query("SELECT COUNT(*) FROM sim_cards WHERE status = 'active'")->fetchColumn();
        $stats['active_staff']       = $this->db->query("SELECT COUNT(*) FROM staff WHERE status = 'active'")->fetchColumn();
        $stats['for_preparation']    = $this->db->query("SELECT COUNT(*) FROM pagers WHERE status = 'for_preparation'")->fetchColumn();
        $stats['defect']             = $this->db->query("SELECT COUNT(*) FROM pagers WHERE status = 'defect'")->fetchColumn();

        $stats['staff_without_pager'] = $this->db->query(
            "SELECT COUNT(DISTINCT s.id) FROM staff s
             LEFT JOIN pager_assignments pa ON pa.staff_id = s.id AND pa.returned_at IS NULL
             WHERE s.status = 'active' AND pa.id IS NULL"
        )->fetchColumn();

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

        $stats['pager_status'] = $this->db->query(
            "SELECT status, COUNT(*) as count FROM pagers GROUP BY status"
        )->fetchAll(PDO::FETCH_KEY_PAIR);

        $stats['preparation_overdue'] = $this->db->query(
            "SELECT COUNT(*) FROM pagers
             WHERE status = 'for_preparation'
             AND updated_at < DATE_SUB(NOW(), INTERVAL 7 DAY)"
        )->fetchColumn();

        $stats['repair_overdue'] = $this->db->query(
            "SELECT COUNT(*) FROM pagers p
             INNER JOIN repairs r ON r.pager_id = p.id
             WHERE p.status = 'in_repair'
             AND r.completed_at IS NULL
             AND r.repair_date < DATE_SUB(NOW(), INTERVAL 30 DAY)"
        )->fetchColumn();

        $stats['without_sim'] = $this->db->query(
            "SELECT COUNT(*) FROM pagers p
             LEFT JOIN sim_cards s ON s.pager_id = p.id AND s.status = 'active'
             WHERE p.status IN ('in_stock', 'issued') AND s.id IS NULL"
        )->fetchColumn();

        $stats['staff_without_pager'] = $this->db->query(
            "SELECT COUNT(DISTINCT s.id) FROM staff s
             LEFT JOIN pager_assignments pa ON pa.staff_id = s.id AND pa.returned_at IS NULL
             WHERE s.status = 'active' AND pa.id IS NULL"
        )->fetchColumn();

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
        fwrite($output, "\xEF\xBB\xBF");

        fputcsv($output, [
            'Telefonnummer', 'SIM-nummer', 'Serienummer',
            'Artikelnummer', 'Brandmand', 'Lønnummer', 'Station'
        ], ',', '"', '\\');

        foreach ($phones as $phone) {
            fputcsv($output, [
                $phone['phone_number'],
                $phone['sim_number'],
                $phone['serial_number'],
                $phone['article_number'],
                $phone['staff_name']      ?? '-',
                $phone['employee_number'] ?? '-',
                $phone['station_name']    ?? '-'
            ], ',', '"', '\\');
        }

        fclose($output);
        exit;
    }
}

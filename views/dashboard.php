<?php
use App\Core\Auth;
use App\Config\Database;

$user = Auth::user();
$db   = Database::getInstance();

$stats = [];
$stats['in_stock']       = $db->query("SELECT COUNT(*) FROM pagers WHERE status = 'in_stock'")->fetchColumn();
$stats['issued']         = $db->query("SELECT COUNT(*) FROM pagers WHERE status = 'issued'")->fetchColumn();
$stats['in_repair']      = $db->query("SELECT COUNT(*) FROM pagers WHERE status = 'in_repair'")->fetchColumn();
$stats['for_preparation']= $db->query("SELECT COUNT(*) FROM pagers WHERE status = 'for_preparation'")->fetchColumn();
$stats['active_staff']   = $db->query("SELECT COUNT(*) FROM staff WHERE status = 'active' AND deleted_at IS NULL")->fetchColumn();

$alerts = [];

$stmt = $db->query(
    "SELECT COUNT(DISTINCT sc.id) FROM staff_competencies sc
     INNER JOIN staff s ON s.id = sc.staff_id AND s.deleted_at IS NULL AND s.status = 'active'
     WHERE sc.expiry_date IS NOT NULL
       AND sc.expiry_date >= CURDATE()
       AND sc.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)"
);
$alerts['expiring_competencies'] = (int)$stmt->fetchColumn();

$stmt = $db->query(
    "SELECT p.id, p.serial_number, r.repair_date, DATEDIFF(CURDATE(), r.repair_date) as days_open
     FROM repairs r
     INNER JOIN pagers p ON p.id = r.pager_id
     WHERE r.completed_at IS NULL AND r.repair_date < DATE_SUB(CURDATE(), INTERVAL 30 DAY)
     ORDER BY r.repair_date ASC"
);
$alerts['overdue_repairs'] = $stmt->fetchAll();

$stmt = $db->query(
    "SELECT id, serial_number, DATEDIFF(NOW(), updated_at) as days_waiting
     FROM pagers
     WHERE status = 'for_preparation' AND updated_at < DATE_SUB(NOW(), INTERVAL 7 DAY)
     ORDER BY updated_at ASC"
);
$alerts['overdue_preparation'] = $stmt->fetchAll();

$stmt = $db->query(
    "SELECT COUNT(*) FROM pagers p
     LEFT JOIN sim_cards s ON s.pager_id = p.id AND s.status = 'active'
     WHERE p.status IN ('in_stock','issued') AND s.id IS NULL"
);
$alerts['without_sim'] = (int)$stmt->fetchColumn();

$hasAlerts = $alerts['expiring_competencies'] > 0
          || !empty($alerts['overdue_repairs'])
          || !empty($alerts['overdue_preparation'])
          || $alerts['without_sim'] > 0;

$title = 'Dashboard';
ob_start();
?>

<div class="page-header">
    <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
</div>

<div class="welcome">
    <h2>Velkommen, <?= htmlspecialchars($user['name']) ?></h2>
    <p>Rolle: <strong><?= htmlspecialchars($user['role']) ?></strong></p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <h3>På lager</h3>
        <div class="stat-number"><?= $stats['in_stock'] ?></div>
        <a href="/pagers?status=in_stock">Se alle</a>
    </div>
    <div class="stat-card">
        <h3>Udleverede</h3>
        <div class="stat-number"><?= $stats['issued'] ?></div>
        <a href="/pagers?status=issued">Se alle</a>
    </div>
    <div class="stat-card">
        <h3>Til reparation</h3>
        <div class="stat-number"><?= $stats['in_repair'] ?></div>
        <a href="/pagers?status=in_repair">Se alle</a>
    </div>
    <div class="stat-card">
        <h3>Til klargøring</h3>
        <div class="stat-number"><?= $stats['for_preparation'] ?></div>
        <a href="/pagers?status=for_preparation">Se alle</a>
    </div>
    <div class="stat-card">
        <h3>Aktive brandfolk</h3>
        <div class="stat-number"><?= $stats['active_staff'] ?></div>
        <a href="/staff?status=active">Se alle</a>
    </div>
</div>

<?php if ($hasAlerts): ?>
<div class="card alerts-card">
    <h2><i class="fas fa-exclamation-triangle"></i> Kræver opmærksomhed</h2>

    <?php if ($alerts['expiring_competencies'] > 0): ?>
    <div class="alert-item alert-item-warning">
        <div class="alert-item-icon"><i class="fas fa-certificate"></i></div>
        <div class="alert-item-body">
            <strong><?= $alerts['expiring_competencies'] ?> kompetence<?= $alerts['expiring_competencies'] > 1 ? 'r' : '' ?> udløber inden for 30 dage</strong>
        </div>
        <a href="/competencies/expiring?days=30" class="btn btn-small">Se liste</a>
    </div>
    <?php endif; ?>

    <?php foreach ($alerts['overdue_preparation'] as $p): ?>
    <div class="alert-item alert-item-warning">
        <div class="alert-item-icon"><i class="fas fa-tools"></i></div>
        <div class="alert-item-body">
            <strong><?= htmlspecialchars($p['serial_number']) ?></strong>
            venter på klargøring i <?= $p['days_waiting'] ?> dage
        </div>
        <a href="/pagers/<?= $p['id'] ?>/preparation" class="btn btn-small btn-primary">Klargør</a>
    </div>
    <?php endforeach; ?>

    <?php foreach ($alerts['overdue_repairs'] as $r): ?>
    <div class="alert-item alert-item-danger">
        <div class="alert-item-icon"><i class="fas fa-wrench"></i></div>
        <div class="alert-item-body">
            <strong><?= htmlspecialchars($r['serial_number']) ?></strong>
            har været til reparation i <?= $r['days_open'] ?> dage (siden <?= date('d/m/Y', strtotime($r['repair_date'])) ?>)
        </div>
        <a href="/pagers/<?= $r['id'] ?>" class="btn btn-small">Se pager</a>
    </div>
    <?php endforeach; ?>

    <?php if ($alerts['without_sim'] > 0): ?>
    <div class="alert-item alert-item-info">
        <div class="alert-item-icon"><i class="fas fa-sim-card"></i></div>
        <div class="alert-item-body">
            <strong><?= $alerts['without_sim'] ?> pager<?= $alerts['without_sim'] > 1 ? 'e' : '' ?> mangler aktivt SIM-kort</strong>
            (på lager eller udleverede)
        </div>
        <a href="/reports/status-overview" class="btn btn-small">Status</a>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="quick-actions">
    <h3>Hurtig adgang</h3>
    <div class="action-buttons">
        <a href="/pagers" class="btn"><i class="fas fa-pager"></i> Pagere</a>
        <a href="/staff" class="btn"><i class="fas fa-users"></i> Brandfolk</a>
        <a href="/reports" class="btn"><i class="fas fa-chart-bar"></i> Rapporter</a>
        <?php if (Auth::hasRole('admin')): ?>
            <a href="/pagers/create" class="btn btn-primary"><i class="fas fa-plus"></i> Opret pager</a>
            <a href="/staff/create" class="btn btn-primary"><i class="fas fa-user-plus"></i> Opret brandmand</a>
        <?php endif; ?>
    </div>
</div>

<style>
.alerts-card { margin-bottom: 24px; }
.alert-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid var(--gray-100);
}
.alert-item:last-child { border-bottom: none; }
.alert-item-icon {
    width: 32px;
    text-align: center;
    flex-shrink: 0;
    font-size: 1.1rem;
}
.alert-item-body { flex: 1; }
.alert-item-warning .alert-item-icon { color: var(--warning); }
.alert-item-danger  .alert-item-icon { color: var(--danger); }
.alert-item-info    .alert-item-icon { color: var(--info); }
</style>

<?php
$content = ob_get_clean();
require __DIR__ . '/layouts/main.php';
?>

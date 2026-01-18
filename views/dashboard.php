<?php
// views/dashboard.php
use App\Core\Auth;
use App\Config\Database;

$user = Auth::user();
$db = Database::getInstance();

// Statistik
$stats = [];

$stmt = $db->query("SELECT COUNT(*) FROM pagers WHERE status = 'in_stock'");
$stats['in_stock'] = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM pagers WHERE status = 'issued'");
$stats['issued'] = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM pagers WHERE status = 'in_repair'");
$stats['in_repair'] = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM pagers WHERE status = 'for_preparation'");
$stats['for_preparation'] = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM staff WHERE status = 'active' AND deleted_at IS NULL");
$stats['active_staff'] = $stmt->fetchColumn();

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

<?php
$content = ob_get_clean();
require __DIR__ . '/layouts/main.php';
?>

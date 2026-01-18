<?php
// views/reports/status_overview.php
use App\Core\Auth;
$title = 'Statusoverblik';
ob_start();
?>

<div class="page-header">
    <h1><i class="fas fa-tachometer-alt"></i> Statusoverblik</h1>
    <a href="/reports" class="btn"><i class="fas fa-arrow-left"></i> Tilbage til rapporter</a>
</div>

<div class="stats-grid">
    <?php foreach ($stats['pager_status'] as $status => $count): ?>
        <div class="stat-card">
            <h3><?= status_badge($status, 'pager') ?></h3>
            <div class="stat-number"><?= $count ?></div>
        </div>
    <?php endforeach; ?>
</div>

<div class="alert-grid">
    <?php if ($stats['preparation_overdue'] > 0): ?>
        <div class="alert alert-warning">
            <div class="alert-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="alert-content">
                <h4>Pagere til klargøring for længe</h4>
                <p><strong><?= $stats['preparation_overdue'] ?></strong> pagere har været til klargøring i mere end 7 dage</p>
                <a href="/pagers?status=for_preparation" class="btn btn-small">Se pagere</a>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($stats['repair_overdue'] > 0): ?>
        <div class="alert alert-warning">
            <div class="alert-icon">
                <i class="fas fa-wrench"></i>
            </div>
            <div class="alert-content">
                <h4>Reparationer tager for lang tid</h4>
                <p><strong><?= $stats['repair_overdue'] ?></strong> pagere har været til reparation i mere end 30 dage</p>
                <a href="/pagers?status=in_repair" class="btn btn-small">Se pagere</a>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($stats['without_sim'] > 0): ?>
        <div class="alert alert-info">
            <div class="alert-icon">
                <i class="fas fa-sim-card"></i>
            </div>
            <div class="alert-content">
                <h4>Pagere uden SIM-kort</h4>
                <p><strong><?= $stats['without_sim'] ?></strong> pagere mangler SIM-kort</p>
                <a href="/pagers?status=in_stock" class="btn btn-small">Se pagere</a>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($stats['staff_without_pager'] > 0): ?>
        <div class="alert alert-info">
            <div class="alert-icon">
                <i class="fas fa-user-times"></i>
            </div>
            <div class="alert-content">
                <h4>Brandfolk uden pager</h4>
                <p><strong><?= $stats['staff_without_pager'] ?></strong> aktive brandfolk har ingen tildelt pager</p>
                <a href="/reports/missing-pagers" class="btn btn-small">Se liste</a>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($stats['preparation_overdue'] == 0 && $stats['repair_overdue'] == 0 && $stats['without_sim'] == 0 && $stats['staff_without_pager'] == 0): ?>
        <div class="alert alert-success">
            <div class="alert-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="alert-content">
                <h4>Alt er i orden</h4>
                <p>Ingen afvigelser eller advarsler registreret</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
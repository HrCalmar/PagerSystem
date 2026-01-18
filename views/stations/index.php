<?php
// views/stations/index.php
use App\Core\Auth;
$title = 'Stationer';
ob_start();
?>

<div class="page-header">
    <h1><i class="fas fa-building"></i> Stationer</h1>
    <?php if (Auth::hasRole('admin')): ?>
        <a href="/stations/create" class="btn btn-primary"><i class="fas fa-plus"></i> Opret station</a>
    <?php endif; ?>
</div>

<div class="grid">
    <?php foreach ($stations as $station): ?>
        <div class="station-card">
            <h3><?= htmlspecialchars($station['name']) ?></h3>
            <?php if ($station['code']): ?>
                <div class="station-code"><?= htmlspecialchars($station['code']) ?></div>
            <?php endif; ?>
            <div class="station-stats">
                <div><i class="fas fa-users"></i> <?= $station['staff_count'] ?> brandfolk</div>
                <div><i class="fas fa-pager"></i> <?= $station['pager_count'] ?> pagere</div>
            </div>
            <a href="/stations/<?= $station['id'] ?>" class="btn btn-block"><i class="fas fa-eye"></i> Se detaljer</a>
        </div>
    <?php endforeach; ?>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
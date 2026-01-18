<?php
// views/reports/missing_pagers.php
use App\Core\Auth;
$title = 'Manglende pagere';
ob_start();
?>

<div class="page-header">
    <h1><i class="fas fa-exclamation-triangle"></i> Brandfolk uden pager</h1>
    <a href="/reports" class="btn"><i class="fas fa-arrow-left"></i> Tilbage til rapporter</a>
</div>

<?php if (empty($staff)): ?>
    <div class="card">
        <div class="empty-state">
            <i class="fas fa-check-circle"></i>
            <p>Alle aktive brandfolk har tildelt pager</p>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-warning">
        <i class="fas fa-info-circle"></i>
        <strong><?= count($staff) ?></strong> aktive brandfolk mangler tildelt pager
    </div>
    
    <div class="card">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Navn</th>
                        <th>Lønnummer</th>
                        <th>Station</th>
                        <th class="text-right">Handling</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($staff as $s): ?>
                    <tr>
                        <td>
                            <a href="/staff/<?= $s['id'] ?>" class="text-link">
                                <i class="fas fa-user"></i> <?= htmlspecialchars($s['name']) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($s['employee_number']) ?></td>
                        <td><?= htmlspecialchars($s['station_name']) ?></td>
                        <td>
                            <?php if (Auth::hasRole('admin')): ?>
                                <div class="action-buttons">
                                    <a href="/staff/<?= $s['id'] ?>" class="btn btn-small btn-primary">
                                        <i class="fas fa-hand-holding"></i> Udlever pager
                                    </a>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
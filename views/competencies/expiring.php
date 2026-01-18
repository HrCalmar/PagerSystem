<?php
use App\Core\Auth;
$title = 'Udløbende kompetencer';
$days = (int)($_GET['days'] ?? 30);
ob_start();
?>

<div class="page-header">
    <h1><i class="fas fa-clock"></i> Udløbende kompetencer</h1>
    <a href="/competencies" class="btn"><i class="fas fa-arrow-left"></i> Tilbage</a>
</div>

<div class="filters">
    <form method="GET" class="filter-form">
        <label style="margin-right: 8px;">Vis kompetencer der udløber inden for:</label>
        <select name="days" class="filter-select" onchange="this.form.submit()">
            <option value="7" <?= $days == 7 ? 'selected' : '' ?>>7 dage</option>
            <option value="14" <?= $days == 14 ? 'selected' : '' ?>>14 dage</option>
            <option value="30" <?= $days == 30 ? 'selected' : '' ?>>30 dage</option>
            <option value="60" <?= $days == 60 ? 'selected' : '' ?>>60 dage</option>
            <option value="90" <?= $days == 90 ? 'selected' : '' ?>>90 dage</option>
            <option value="180" <?= $days == 180 ? 'selected' : '' ?>>180 dage</option>
            <option value="365" <?= $days == 365 ? 'selected' : '' ?>>1 år</option>
        </select>
    </form>
</div>

<?php
$expired = array_filter($expiringCompetencies, fn($c) => $c['days_until_expiry'] < 0);
$expiringSoon = array_filter($expiringCompetencies, fn($c) => $c['days_until_expiry'] >= 0);
?>

<?php if (!empty($expired)): ?>
<div class="card">
    <h2 style="color: var(--danger)"><i class="fas fa-exclamation-triangle"></i> Udløbede kompetencer (<?= count($expired) ?>)</h2>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Brandmand</th>
                    <th>Lønnummer</th>
                    <th>Station</th>
                    <th>Kompetence</th>
                    <th>Udløbet</th>
                    <th>Dage siden</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($expired as $c): ?>
                <tr>
                    <td>
                        <a href="/staff/<?= $c['staff_id'] ?>" class="text-link">
                            <i class="fas fa-user"></i> <?= htmlspecialchars($c['staff_name']) ?>
                        </a>
                    </td>
                    <td><?= htmlspecialchars($c['employee_number']) ?></td>
                    <td><?= htmlspecialchars($c['stations'] ?? '-') ?></td>
                    <td>
                        <a href="/competencies/<?= $c['competency_id'] ?>" class="text-link">
                            <i class="fas fa-certificate"></i> <?= htmlspecialchars($c['competency_name']) ?>
                        </a>
                    </td>
                    <td><?= date('d/m/Y', strtotime($c['expiry_date'])) ?></td>
                    <td>
                        <span class="badge badge-danger">
                            <?= abs($c['days_until_expiry']) ?> dage
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <h2 style="color: var(--warning)"><i class="fas fa-clock"></i> Udløber snart (<?= count($expiringSoon) ?>)</h2>
    
    <?php if (empty($expiringSoon)): ?>
        <div class="empty-state-small">
            <i class="fas fa-check-circle"></i>
            <p>Ingen kompetencer udløber inden for <?= $days ?> dage</p>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Brandmand</th>
                        <th>Lønnummer</th>
                        <th>Station</th>
                        <th>Kompetence</th>
                        <th>Udløber</th>
                        <th>Dage tilbage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($expiringSoon as $c): ?>
                    <tr>
                        <td>
                            <a href="/staff/<?= $c['staff_id'] ?>" class="text-link">
                                <i class="fas fa-user"></i> <?= htmlspecialchars($c['staff_name']) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($c['employee_number']) ?></td>
                        <td><?= htmlspecialchars($c['stations'] ?? '-') ?></td>
                        <td>
                            <a href="/competencies/<?= $c['competency_id'] ?>" class="text-link">
                                <i class="fas fa-certificate"></i> <?= htmlspecialchars($c['competency_name']) ?>
                            </a>
                        </td>
                        <td><?= date('d/m/Y', strtotime($c['expiry_date'])) ?></td>
                        <td>
                            <?php
                            $badgeClass = 'badge-success';
                            if ($c['days_until_expiry'] <= 7) $badgeClass = 'badge-danger';
                            elseif ($c['days_until_expiry'] <= 30) $badgeClass = 'badge-warning';
                            ?>
                            <span class="badge <?= $badgeClass ?>">
                                <?= $c['days_until_expiry'] ?> dage
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php if (empty($expired) && empty($expiringSoon)): ?>
<div class="alert alert-success">
    <i class="fas fa-check-circle alert-icon"></i>
    <div class="alert-content">
        <strong>Alt i orden!</strong>
        <p>Ingen kompetencer udløber eller er udløbet inden for de næste <?= $days ?> dage.</p>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>

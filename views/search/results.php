<?php
use App\Core\Auth;
$title = 'Søgeresultater: ' . htmlspecialchars($query);
ob_start();
$includeArchived = isset($_GET['archived']) && $_GET['archived'] === '1';
?>

<div class="page-header">
    <h1><i class="fas fa-search"></i> Søgeresultater for "<?= htmlspecialchars($query) ?>"</h1>
    <div class="page-actions">
        <?php if ($includeArchived): ?>
            <a href="/search?q=<?= urlencode($query) ?>" class="btn">
                <i class="fas fa-eye-slash"></i> Skjul arkiverede
            </a>
        <?php else: ?>
            <a href="/search?q=<?= urlencode($query) ?>&archived=1" class="btn">
                <i class="fas fa-archive"></i> Inkluder arkiverede
            </a>
        <?php endif; ?>
    </div>
</div>

<?php
$totalResults = count($results['pagers']) + count($results['staff']) + count($results['stations']);
?>

<?php if ($totalResults === 0): ?>
    <div class="card">
        <div class="empty-state">
            <i class="fas fa-search"></i>
            <p>Ingen resultater fundet for "<?= htmlspecialchars($query) ?>"</p>
            <?php if (!$includeArchived): ?>
                <a href="/search?q=<?= urlencode($query) ?>&archived=1" class="btn btn-primary">
                    <i class="fas fa-archive"></i> Søg også i arkiverede
                </a>
            <?php endif; ?>
            <a href="/dashboard" class="btn">
                <i class="fas fa-home"></i> Tilbage til dashboard
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle alert-icon"></i>
        <div class="alert-content">
            Fandt <strong><?= $totalResults ?></strong> resultat<?= $totalResults !== 1 ? 'er' : '' ?>
            <?php if ($includeArchived): ?>
                <span class="text-muted">(inkl. arkiverede)</span>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($results['pagers'])): ?>
    <div class="card">
        <h2><i class="fas fa-pager"></i> Pagere (<?= count($results['pagers']) ?>)</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Serienummer</th>
                        <th>Artikelnummer</th>
                        <th>Telefonnummer</th>
                        <th>Status</th>
                        <th>Udleveret til</th>
                        <th class="text-right">Handling</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results['pagers'] as $pager): ?>
                    <tr>
                        <td class="font-medium">
                            <a href="/pagers/<?= $pager['id'] ?>" class="text-link">
                                <?= htmlspecialchars($pager['serial_number']) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($pager['article_number'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($pager['phone_number'] ?? '-') ?></td>
                        <td><?= status_badge($pager['status'], 'pager') ?></td>
                        <td>
                            <?php if ($pager['staff_name']): ?>
                                <?= htmlspecialchars($pager['staff_name']) ?>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="/pagers/<?= $pager['id'] ?>" class="btn-icon" title="Vis">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($results['staff'])): ?>
    <div class="card">
        <h2><i class="fas fa-users"></i> Brandfolk (<?= count($results['staff']) ?>)</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Navn</th>
                        <th>Lønnummer</th>
                        <th>Stationer</th>
                        <th>Status</th>
                        <th class="text-right">Handling</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results['staff'] as $s): ?>
                    <tr>
                        <td class="font-medium">
                            <a href="/staff/<?= $s['id'] ?>" class="text-link">
                                <i class="fas fa-user"></i> <?= htmlspecialchars($s['name']) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($s['employee_number']) ?></td>
                        <td><?= htmlspecialchars($s['stations'] ?? '-') ?></td>
                        <td><?= status_badge($s['status'], 'staff') ?></td>
                        <td>
                            <div class="action-buttons">
                                <a href="/staff/<?= $s['id'] ?>" class="btn-icon" title="Vis">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($results['stations'])): ?>
    <div class="card">
        <h2><i class="fas fa-building"></i> Stationer (<?= count($results['stations']) ?>)</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Navn</th>
                        <th>Kode</th>
                        <th>Brandfolk</th>
                        <th class="text-right">Handling</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results['stations'] as $station): ?>
                    <tr>
                        <td class="font-medium">
                            <a href="/stations/<?= $station['id'] ?>" class="text-link">
                                <i class="fas fa-building"></i> <?= htmlspecialchars($station['name']) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($station['code'] ?? '-') ?></td>
                        <td><?= $station['staff_count'] ?></td>
                        <td>
                            <div class="action-buttons">
                                <a href="/stations/<?= $station['id'] ?>" class="btn-icon" title="Vis">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
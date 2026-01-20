<?php
// views/search/results.php
use App\Core\Auth;
ob_start();

$totalResults = count($results['pagers']) + count($results['staff']) + count($results['stations']);
?>

<div class="page-header">
    <h1><i class="fas fa-search"></i> Søgeresultater</h1>
    <p class="page-subtitle">Fandt <?= $totalResults ?> resultater for "<?= htmlspecialchars($query) ?>"</p>
</div>

<?php if ($totalResults === 0): ?>
    <div class="card">
        <div class="empty-state">
            <i class="fas fa-search"></i>
            <p>Ingen resultater fundet</p>
        </div>
    </div>
<?php else: ?>
    
    <?php if (!empty($results['pagers'])): ?>
    <div class="card">
        <h2><i class="fas fa-pager"></i> Pagere (<?= count($results['pagers']) ?>)</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Serienummer</th>
                        <th>Artikelnummer</th>
                        <th>Telefon</th>
                        <th>Status</th>
                        <th>Udleveret til</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results['pagers'] as $p): ?>
                    <tr>
                        <td>
                            <a href="/pagers/<?= $p['id'] ?>" class="text-link">
                                <?= htmlspecialchars($p['serial_number']) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($p['article_number'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($p['phone_number'] ?? '-') ?></td>
                        <td><?= status_badge($p['status'], 'pager') ?></td>
                        <td><?= htmlspecialchars($p['staff_name'] ?? '-') ?></td>
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
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results['staff'] as $s): ?>
                    <tr>
                        <td>
                            <a href="/staff/<?= $s['id'] ?>" class="text-link">
                                <i class="fas fa-user"></i> <?= htmlspecialchars($s['name']) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($s['employee_number']) ?></td>
                        <td><?= htmlspecialchars($s['stations'] ?? '-') ?></td>
                        <td><?= status_badge($s['status'], 'staff') ?></td>
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
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results['stations'] as $st): ?>
                    <tr>
                        <td>
                            <a href="/stations/<?= $st['id'] ?>" class="text-link">
                                <?= htmlspecialchars($st['name']) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($st['code'] ?? '-') ?></td>
                        <td><?= $st['staff_count'] ?></td>
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
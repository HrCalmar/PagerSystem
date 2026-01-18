<?php
// views/stations/show.php
use App\Core\Auth;
$title = $station['name'];
ob_start();
?>

<div class="page-header">
    <h1><i class="fas fa-building"></i> <?= htmlspecialchars($station['name']) ?></h1>
</div>

<div class="card">
    <h2>Information</h2>
    <dl>
        <dt>Navn</dt>
        <dd><?= htmlspecialchars($station['name']) ?></dd>
        
        <?php if ($station['code']): ?>
        <dt>Kode</dt>
        <dd><?= htmlspecialchars($station['code']) ?></dd>
        <?php endif; ?>
    </dl>
</div>

<div class="card">
    <h2><i class="fas fa-users"></i> Tilknyttede brandfolk</h2>
    <?php if (empty($staff)): ?>
        <p>Ingen tilknyttede brandfolk</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Navn</th>
                    <th>Lønnummer</th>
                    <th>Aktive pagere</th>
                    <th>Kompetencer</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($staff as $s): ?>
                <tr>
                    <td><?= htmlspecialchars($s['name']) ?></td>
                    <td><?= htmlspecialchars($s['employee_number']) ?></td>
                    <td><?= $s['pager_count'] ?></td>
                    <td><?= htmlspecialchars($s['competencies'] ?? '-') ?></td>
                    <td><a href="/staff/<?= $s['id'] ?>" class="btn btn-small"><i class="fas fa-eye"></i> Vis</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
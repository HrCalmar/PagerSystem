<?php
// views/stations/show.php
use App\Core\Auth;
$title = $station['name'];
ob_start();
?>

<div class="page-header">
    <h1><i class="fas fa-building"></i> <?= htmlspecialchars($station['name']) ?></h1>
    <?php if (Auth::hasRole('admin')): ?>
        <div class="page-actions">
            <button onclick="window.print()" class="btn no-print">
                <i class="fas fa-print"></i> Print
            </button>
            <a href="/stations/<?= $station['id'] ?>/edit" class="btn btn-primary no-print">
                <i class="fas fa-edit"></i> Rediger station
            </a>
        </div>
    <?php endif; ?>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success no-print">
        <i class="fas fa-check-circle alert-icon"></i>
        <div class="alert-content">
            <?= ['updated' => 'Station opdateret', 'deleted' => 'Station slettet'][$_GET['success']] ?? 'Handling udført' ?>
        </div>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error no-print">
        <i class="fas fa-exclamation-circle alert-icon"></i>
        <div class="alert-content"><?= htmlspecialchars($_GET['error']) ?></div>
    </div>
<?php endif; ?>

<div class="card">
    <h2>Information</h2>
    <dl>
        <dt>Navn</dt>
        <dd><?= htmlspecialchars($station['name']) ?></dd>

        <?php if ($station['code']): ?>
        <dt>Kode</dt>
        <dd><?= htmlspecialchars($station['code']) ?></dd>
        <?php endif; ?>

        <?php if ($station['address'] ?? null): ?>
        <dt>Adresse</dt>
        <dd><?= htmlspecialchars($station['address']) ?></dd>
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
                    <th class="no-print"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($staff as $s): ?>
                <tr>
                    <td><?= htmlspecialchars($s['name']) ?></td>
                    <td><?= htmlspecialchars($s['employee_number']) ?></td>
                    <td><?= $s['pager_count'] ?></td>
                    <td><?= htmlspecialchars($s['competencies'] ?? '-') ?></td>
                    <td class="no-print"><a href="/staff/<?= $s['id'] ?>" class="btn btn-small"><i class="fas fa-eye"></i> Vis</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php if (!empty($pagers)): ?>
<div class="card">
    <h2><i class="fas fa-pager"></i> Udleverede pagere</h2>
    <table>
        <thead>
            <tr>
                <th>Serienummer</th>
                <th>Telefonnummer</th>
                <th>Status</th>
                <th>Udleveret til</th>
                <th class="no-print"></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pagers as $p): ?>
            <tr>
                <td class="font-medium"><?= htmlspecialchars($p['serial_number']) ?></td>
                <td><?= htmlspecialchars($p['phone_number'] ?? '-') ?></td>
                <td><?= status_badge($p['status'], 'pager') ?></td>
                <td>
                    <a href="/staff/<?= $p['staff_id'] ?>" class="text-link">
                        <i class="fas fa-user"></i> <?= htmlspecialchars($p['staff_name']) ?>
                    </a>
                </td>
                <td class="no-print">
                    <a href="/pagers/<?= $p['id'] ?>" class="btn btn-small"><i class="fas fa-eye"></i> Vis</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php if (Auth::hasRole('admin')): ?>
<div class="card danger-zone no-print">
    <h2><i class="fas fa-exclamation-triangle"></i> Fareområde</h2>
    <p>Sletning er kun mulig hvis ingen brandfolk er tilknyttet stationen.</p>
    <form method="POST" action="/stations/<?= $station['id'] ?>/delete"
          onsubmit="return confirm('Slet station <?= htmlspecialchars(addslashes($station['name'])) ?>?')">
        <?= \App\Core\CSRF::field() ?>
        <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> Slet station</button>
    </form>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>

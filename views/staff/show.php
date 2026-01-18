<?php
// views/staff/show.php
use App\Core\{Auth, CSRF};
use App\Config\Database;

$db = Database::getInstance();
$availablePagers = $db->query(
    "SELECT id, serial_number, article_number FROM pagers WHERE status = 'in_stock' ORDER BY serial_number"
)->fetchAll();

$isDeleted = !empty($staff['deleted_at']);

$title = $staff['name'];
ob_start();
?>

<div class="page-header">
    <h1><i class="fas fa-user"></i> <?= htmlspecialchars($staff['name']) ?></h1>
    <div class="page-actions">
        <?php if (Auth::hasRole('admin') && !$isDeleted): ?>
            <a href="/staff/<?= $staff['id'] ?>/edit" class="btn">
                <i class="fas fa-edit"></i> Rediger
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle alert-icon"></i>
        <div class="alert-content">
            <?php
            $messages = [
                'created' => 'Brandmand oprettet',
                'updated' => 'Brandmand opdateret',
                'deactivated' => 'Brandmand deaktiveret',
                'reactivated' => 'Brandmand reaktiveret',
                'station_added' => 'Station tilføjet',
                'station_removed' => 'Station fjernet',
                'competency_added' => 'Kompetence tilføjet',
                'competency_removed' => 'Kompetence fjernet',
                'pager_assigned' => 'Pager udleveret'
            ];
            echo $messages[$_GET['success']] ?? 'Handling udført';
            ?>
        </div>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle alert-icon"></i>
        <div class="alert-content">
            <?= htmlspecialchars($_GET['error']) ?>
        </div>
    </div>
<?php endif; ?>

<?php if ($isDeleted): ?>
    <div class="alert alert-warning">
        <i class="fas fa-trash-alt alert-icon"></i>
        <div class="alert-content">
            <strong>Denne brandmand er slettet</strong>
            <?php if ($staff['deleted_at']): ?>
                <br>Slettet: <?= date('d/m/Y H:i', strtotime($staff['deleted_at'])) ?>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<div class="grid-2col">
    <div class="card">
        <h2><i class="fas fa-info-circle"></i> Stamdata</h2>
        <dl>
            <dt>Navn</dt>
            <dd><?= htmlspecialchars($staff['name']) ?></dd>
            
            <dt>Lønnummer</dt>
            <dd><?= htmlspecialchars($staff['employee_number']) ?></dd>
            
            <dt>Status</dt>
            <dd><?= status_badge($staff['status'], 'staff') ?></dd>
        </dl>
        
        <?php if (Auth::hasRole('admin') && !$isDeleted): ?>
            <div class="card-actions">
                <?php if ($staff['status'] === 'active'): ?>
                    <form method="POST" action="/staff/<?= $staff['id'] ?>/deactivate" onsubmit="return confirm('Deaktiver denne brandmand?')">
                        <?= CSRF::field() ?>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-user-times"></i> Deaktiver
                        </button>
                    </form>
                <?php else: ?>
                    <form method="POST" action="/staff/<?= $staff['id'] ?>/reactivate">
                        <?= CSRF::field() ?>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-check"></i> Reaktiver
                        </button>
                    </form>
                <?php endif; ?>
                
                <?php if (empty($activePagers)): ?>
                    <form method="POST" action="/staff/<?= $staff['id'] ?>/delete" onsubmit="return confirm('Er du sikker på du vil slette denne brandmand?\n\nData bevares og kan ses under \'Vis slettede\'.')">
                        <?= CSRF::field() ?>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash-alt"></i> Slet
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2><i class="fas fa-pager"></i> Aktive pagere</h2>
        
        <?php if (Auth::hasRole('admin') && $staff['status'] === 'active' && !$isDeleted && !empty($availablePagers)): ?>
            <div class="quick-assign">
                <form method="POST" action="/pagers/quick-assign" class="inline-form compact">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="staff_id" value="<?= $staff['id'] ?>">
                    <select name="pager_id" required>
                        <option value="">Vælg pager at udlevere</option>
                        <?php foreach ($availablePagers as $p): ?>
                            <option value="<?= $p['id'] ?>">
                                <?= htmlspecialchars($p['serial_number']) ?>
                                <?php if ($p['article_number']): ?>
                                    (<?= htmlspecialchars($p['article_number']) ?>)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Udlever
                    </button>
                </form>
            </div>
        <?php endif; ?>
        
        <?php if (empty($activePagers)): ?>
            <p class="text-muted"><i class="fas fa-info-circle"></i> Ingen aktive pagere</p>
        <?php else: ?>
            <div class="pager-list">
                <?php foreach ($activePagers as $p): ?>
                    <div class="pager-item">
                        <div class="pager-info">
                            <a href="/pagers/<?= $p['id'] ?>" class="pager-serial">
                                <i class="fas fa-pager"></i> <?= htmlspecialchars($p['serial_number']) ?>
                            </a>
                            <?php if ($p['phone_number']): ?>
                                <div class="pager-phone">
                                    <i class="fas fa-phone"></i> <?= htmlspecialchars($p['phone_number']) ?>
                                </div>
                            <?php endif; ?>
                            <div class="pager-date">
                                <?= status_badge($p['status'], 'pager') ?>
                                <?php if ($p['issued_at']): ?>
                                    <span class="text-muted">
                                        Udleveret <?= date('d/m/Y', strtotime($p['issued_at'])) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if (Auth::hasRole('admin') && !$isDeleted): ?>
                            <div class="pager-actions">
                                <a href="/pagers/<?= $p['id'] ?>/return" class="btn btn-small">
                                    <i class="fas fa-undo"></i> Returner
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Stationer -->
<div class="card">
    <h2><i class="fas fa-building"></i> Stationer</h2>
    
    <?php if (Auth::hasRole('admin') && $staff['status'] === 'active' && !$isDeleted): ?>
        <form method="POST" action="/staff/<?= $staff['id'] ?>/stations/add" class="inline-form">
            <?= CSRF::field() ?>
            <select name="station_id" required>
                <option value="">Vælg station</option>
                <?php foreach ($allStations as $st): ?>
                    <option value="<?= $st['id'] ?>"><?= htmlspecialchars($st['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="date" name="start_date" value="<?= date('Y-m-d') ?>" required>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-plus"></i> Tilføj
            </button>
        </form>
    <?php endif; ?>
    
    <?php if (empty($stations)): ?>
        <p class="text-muted"><i class="fas fa-info-circle"></i> Ingen stationer</p>
    <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Station</th>
                        <th>Startdato</th>
                        <th>Slutdato</th>
                        <th class="text-right">Handling</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stations as $st): ?>
                    <tr>
                        <td>
                            <a href="/stations/<?= $st['id'] ?>" class="text-link">
                                <i class="fas fa-building"></i> <?= htmlspecialchars($st['name']) ?>
                            </a>
                        </td>
                        <td><?= date('d/m/Y', strtotime($st['start_date'])) ?></td>
                        <td>
                            <?php if ($st['end_date']): ?>
                                <?= date('d/m/Y', strtotime($st['end_date'])) ?>
                            <?php else: ?>
                                <span class="badge badge-success">Aktiv</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!$st['end_date'] && Auth::hasRole('admin') && !$isDeleted): ?>
                                <form method="POST" action="/staff/<?= $staff['id'] ?>/stations/remove" class="inline-form-mini">
                                    <?= CSRF::field() ?>
                                    <input type="hidden" name="assignment_id" value="<?= $st['assignment_id'] ?>">
                                    <input type="date" name="end_date" value="<?= date('Y-m-d') ?>" required>
                                    <button type="submit" class="btn btn-small btn-danger">
                                        <i class="fas fa-times"></i> Fjern
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Kompetencer -->
<div class="card">
    <h2><i class="fas fa-certificate"></i> Kompetencer</h2>
    
    <?php if (Auth::hasRole('admin') && $staff['status'] === 'active' && !$isDeleted): ?>
        <form method="POST" action="/staff/<?= $staff['id'] ?>/competencies/add" class="inline-form">
            <?= CSRF::field() ?>
            <select name="competency_id" required>
                <option value="">Vælg kompetence</option>
                <?php foreach ($allCompetencies as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="date" name="obtained_date" placeholder="Opnået">
            <input type="date" name="expiry_date" placeholder="Udløb">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-plus"></i> Tilføj
            </button>
        </form>
    <?php endif; ?>
    
    <?php if (empty($competencies)): ?>
        <p class="text-muted"><i class="fas fa-info-circle"></i> Ingen kompetencer</p>
    <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Kompetence</th>
                        <th>Opnået</th>
                        <th>Udløb</th>
                        <th class="text-right">Handling</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($competencies as $c): ?>
                    <tr>
                        <td><i class="fas fa-certificate"></i> <?= htmlspecialchars($c['name']) ?></td>
                        <td><?= $c['obtained_date'] ? date('d/m/Y', strtotime($c['obtained_date'])) : '-' ?></td>
                        <td><?= $c['expiry_date'] ? date('d/m/Y', strtotime($c['expiry_date'])) : '-' ?></td>
                        <td>
                            <?php if (Auth::hasRole('admin') && !$isDeleted): ?>
                                <form method="POST" action="/staff/<?= $staff['id'] ?>/competencies/remove" style="display:inline;">
                                    <?= CSRF::field() ?>
                                    <input type="hidden" name="staff_competency_id" value="<?= $c['staff_competency_id'] ?>">
                                    <button type="submit" class="btn btn-small btn-danger" onclick="return confirm('Fjern kompetence?')">
                                        <i class="fas fa-trash"></i> Fjern
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Pager historik -->
<?php if (!empty($pagerHistory)): ?>
<div class="card">
    <h2><i class="fas fa-history"></i> Pager historik</h2>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Pager</th>
                    <th>Reserveret</th>
                    <th>Udleveret</th>
                    <th>Returneret</th>
                    <th>Årsag</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pagerHistory as $ph): ?>
                <tr>
                    <td>
                        <a href="/pagers/<?= $ph['id'] ?>" class="text-link">
                            <?= htmlspecialchars($ph['serial_number']) ?>
                        </a>
                    </td>
                    <td><?= $ph['reserved_at'] ? date('d/m/Y', strtotime($ph['reserved_at'])) : '-' ?></td>
                    <td><?= $ph['issued_at'] ? date('d/m/Y', strtotime($ph['issued_at'])) : '-' ?></td>
                    <td>
                        <?php if ($ph['returned_at']): ?>
                            <?= date('d/m/Y', strtotime($ph['returned_at'])) ?>
                        <?php else: ?>
                            <span class="badge badge-info"><i class="fas fa-clock"></i> Aktiv</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($ph['reason'] ?? '-') ?></td>
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

<?php
// views/pagers/show.php
use App\Core\{Auth, CSRF};

// Hent SIM historik
$db = \App\Config\Database::getInstance();
$stmt = $db->prepare("SELECT * FROM sim_cards WHERE pager_id = ? ORDER BY activated_at DESC");
$stmt->execute([$pager['id']]);
$simHistory = $stmt->fetchAll();

$title = 'Pager: ' . $pager['serial_number'];
ob_start();
?>

<div class="page-header">
    <h1><i class="fas fa-pager"></i> <?= htmlspecialchars($pager['serial_number']) ?></h1>
    <div class="page-actions">
        <?php if (Auth::hasRole('admin')): ?>
            <a href="/pagers/<?= $pager['id'] ?>/edit" class="btn">
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
                'created' => 'Pager oprettet',
                'updated' => 'Pager opdateret',
                'reserved' => 'Pager reserveret',
                'issued' => 'Pager udleveret',
                'returned' => 'Pager returneret',
                'stocked' => 'Pager sat på lager',
                'repair' => 'Pager sendt til reparation',
                'defect' => 'Pager markeret som defekt',
                'preparation' => 'Pager sat til klargøring',
                'sim_added' => 'SIM-kort tilføjet',
                'sim_deactivated' => 'SIM-kort deaktiveret',
                'repair_created' => 'Reparation oprettet',
                'repair_completed' => 'Reparation afsluttet',
                'restored' => 'Pager gendannet fra arkiv'
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

<?php if ($pager['status'] === 'archived'): ?>
    <div class="alert alert-warning">
        <i class="fas fa-archive alert-icon"></i>
        <div class="alert-content">
            <strong>Denne pager er arkiveret</strong>
            <?php if ($pager['archived_at']): ?>
                <br>Arkiveret: <?= date('d/m/Y H:i', strtotime($pager['archived_at'])) ?>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Workflow actions -->
<?php if (Auth::hasRole('admin') && $pager['status'] !== 'archived'): ?>
<div class="card">
    <h2><i class="fas fa-tasks"></i> Handlinger</h2>
    <div class="actions">
        <?php if ($pager['status'] === 'in_stock'): ?>
            <a href="/pagers/<?= $pager['id'] ?>/reserve" class="btn">
                <i class="fas fa-bookmark"></i> Reserver
            </a>
            <a href="/pagers/<?= $pager['id'] ?>/issue" class="btn btn-primary">
                <i class="fas fa-hand-holding"></i> Udlever
            </a>
        <?php elseif ($pager['status'] === 'reserved'): ?>
            <a href="/pagers/<?= $pager['id'] ?>/issue" class="btn btn-primary">
                <i class="fas fa-hand-holding"></i> Udlever
            </a>
        <?php elseif ($pager['status'] === 'issued'): ?>
            <a href="/pagers/<?= $pager['id'] ?>/return" class="btn btn-primary">
                <i class="fas fa-undo"></i> Returner
            </a>
        <?php elseif ($pager['status'] === 'for_preparation'): ?>
            <form method="POST" action="/pagers/<?= $pager['id'] ?>/stock" style="display:inline;">
                <?= CSRF::field() ?>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-box"></i> Sæt på lager
                </button>
            </form>
            <form method="POST" action="/pagers/<?= $pager['id'] ?>/repair" style="display:inline;">
                <?= CSRF::field() ?>
                <button type="submit" class="btn">
                    <i class="fas fa-wrench"></i> Send til reparation
                </button>
            </form>
        <?php elseif ($pager['status'] === 'in_repair'): ?>
            <form method="POST" action="/pagers/<?= $pager['id'] ?>/preparation" style="display:inline;">
                <?= CSRF::field() ?>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-tools"></i> Sæt til klargøring
                </button>
            </form>
            <form method="POST" action="/pagers/<?= $pager['id'] ?>/defect" style="display:inline;" onsubmit="return confirm('Marker som defekt?')">
                <?= CSRF::field() ?>
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-times-circle"></i> Marker defekt
                </button>
            </form>
        <?php elseif ($pager['status'] === 'defect'): ?>
            <form method="POST" action="/pagers/<?= $pager['id'] ?>/preparation" style="display:inline;">
                <?= CSRF::field() ?>
                <button type="submit" class="btn">
                    <i class="fas fa-tools"></i> Sæt til klargøring
                </button>
            </form>
        <?php endif; ?>
        
        <?php if (!in_array($pager['status'], ['issued', 'archived'])): ?>
            <form method="POST" action="/pagers/<?= $pager['id'] ?>/archive" style="display:inline;" onsubmit="return confirm('Arkiver denne pager? Den kan gendannes senere.')">
                <?= CSRF::field() ?>
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-archive"></i> Arkiver
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php if ($pager['status'] === 'archived' && Auth::hasRole('admin')): ?>
<div class="card">
    <h2><i class="fas fa-undo"></i> Gendan pager</h2>
    <form method="POST" action="/pagers/<?= $pager['id'] ?>/restore">
        <?= CSRF::field() ?>
        <p>Denne pager er arkiveret. Klik for at gendanne den til "På lager".</p>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-undo"></i> Gendan pager
        </button>
    </form>
</div>
<?php endif; ?>

<div class="grid-2col">
    <div class="card">
        <h2><i class="fas fa-info-circle"></i> Stamdata</h2>
        <dl>
            <dt>Serienummer</dt>
            <dd class="font-medium"><?= htmlspecialchars($pager['serial_number']) ?></dd>
            
            <dt>Artikelnummer</dt>
            <dd><?= htmlspecialchars($pager['article_number'] ?? '-') ?></dd>
            
            <dt>Indkøbsdato</dt>
            <dd><?= $pager['purchase_date'] ? date('d/m/Y', strtotime($pager['purchase_date'])) : '-' ?></dd>
            
            <dt>Status</dt>
            <dd><?= status_badge($pager['status'], 'pager') ?></dd>
            
            <?php if ($pager['staff_name']): ?>
            <dt>Udleveret til</dt>
            <dd>
                <a href="/staff/<?= $pager['staff_id'] ?>" class="text-link">
                    <i class="fas fa-user"></i> <?= htmlspecialchars($pager['staff_name']) ?>
                </a>
            </dd>
            <?php endif; ?>
        </dl>
    </div>

    <div class="card">
        <h2><i class="fas fa-sim-card"></i> SIM-kort</h2>
        
        <?php if (Auth::hasRole('admin') && !$pager['sim_number'] && $pager['status'] !== 'archived'): ?>
            <div class="quick-assign">
                <form method="POST" action="/pagers/<?= $pager['id'] ?>/sim" class="inline-form compact">
                    <?= CSRF::field() ?>
                    <div class="form-row">
                        <input type="text" name="sim_number" placeholder="SIM-nummer" required>
                        <input type="text" name="phone_number" placeholder="Telefonnummer" required>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Tilføj
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
        
        <?php if ($pager['sim_number']): ?>
            <div class="sim-active">
                <dl>
                    <dt>SIM-nummer</dt>
                    <dd><?= htmlspecialchars($pager['sim_number']) ?></dd>
                    
                    <dt>Telefonnummer</dt>
                    <dd class="phone-number">
                        <i class="fas fa-phone"></i> <?= htmlspecialchars($pager['phone_number']) ?>
                    </dd>
                    
                    <dt>Status</dt>
                    <dd>
                        <span class="badge badge-success">
                            <i class="fas fa-check-circle"></i> Aktiv
                        </span>
                    </dd>
                </dl>
                
                <?php if (Auth::hasRole('admin') && $pager['status'] !== 'archived'): ?>
                    <div class="card-actions">
                        <?php
                        $activeSim = array_values(array_filter($simHistory, fn($s) => $s['status'] === 'active'))[0] ?? null;
                        if ($activeSim):
                        ?>
                            <form method="POST" action="/sim/<?= $activeSim['id'] ?>/deactivate" onsubmit="return confirm('Deaktiver SIM-kort?')">
                                <?= CSRF::field() ?>
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-times-circle"></i> Deaktiver SIM
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <p class="text-muted"><i class="fas fa-info-circle"></i> Ingen aktivt SIM-kort</p>
        <?php endif; ?>
        
        <?php if (count($simHistory) > 1): ?>
            <details class="sim-history-toggle">
                <summary>Vis SIM historik (<?= count($simHistory) - 1 ?> tidligere)</summary>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>SIM</th>
                                <th>Telefon</th>
                                <th>Aktiveret</th>
                                <th>Deaktiveret</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($simHistory as $sim): ?>
                                <?php if ($sim['status'] === 'deactivated'): ?>
                                <tr>
                                    <td><?= htmlspecialchars($sim['sim_number']) ?></td>
                                    <td><?= htmlspecialchars($sim['phone_number']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($sim['activated_at'])) ?></td>
                                    <td><?= $sim['deactivated_at'] ? date('d/m/Y', strtotime($sim['deactivated_at'])) : '-' ?></td>
                                </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </details>
        <?php endif; ?>
    </div>
</div>

<!-- Udleveringshistorik -->
<div class="card">
    <h2><i class="fas fa-history"></i> Udleveringshistorik</h2>
    
    <?php if (empty($history)): ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <p>Ingen udleveringshistorik</p>
            <?php if (Auth::hasRole('admin') && $pager['status'] === 'in_stock'): ?>
                <a href="/pagers/<?= $pager['id'] ?>/issue" class="btn btn-primary">
                    <i class="fas fa-hand-holding"></i> Udlever pager
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Brandmand</th>
                        <th>Reserveret</th>
                        <th>Udleveret</th>
                        <th>Returneret</th>
                        <th>Årsag</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $h): ?>
                    <tr>
                        <td>
                            <a href="/staff/<?= $h['staff_id'] ?>" class="text-link">
                                <i class="fas fa-user"></i> <?= htmlspecialchars($h['staff_name']) ?>
                            </a>
                        </td>
                        <td><?= $h['reserved_at'] ? date('d/m/Y H:i', strtotime($h['reserved_at'])) : '-' ?></td>
                        <td><?= $h['issued_at'] ? date('d/m/Y H:i', strtotime($h['issued_at'])) : '-' ?></td>
                        <td>
                            <?php if ($h['returned_at']): ?>
                                <?= date('d/m/Y H:i', strtotime($h['returned_at'])) ?>
                            <?php else: ?>
                                <span class="badge badge-info"><i class="fas fa-clock"></i> Aktiv</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($h['reason'] ?? '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Reparationer -->
<div class="card">
    <h2><i class="fas fa-wrench"></i> Reparationer</h2>
    
    <?php if (Auth::hasRole('admin') && $pager['status'] !== 'archived'): ?>
        <div style="margin-bottom: 16px;">
            <a href="/pagers/<?= $pager['id'] ?>/repairs/create" class="btn btn-small btn-primary">
                <i class="fas fa-plus"></i> Opret reparation
            </a>
        </div>
    <?php endif; ?>
    
    <?php if (empty($repairs)): ?>
        <div class="empty-state-small">
            <i class="fas fa-tools"></i>
            <p>Ingen reparationer registreret</p>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Dato</th>
                        <th>Leverandør</th>
                        <th>Beskrivelse</th>
                        <th>Omkostning</th>
                        <th>Status</th>
                        <th class="text-right">Handling</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($repairs as $r): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($r['repair_date'])) ?></td>
                        <td><?= htmlspecialchars($r['vendor'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($r['description'] ?? '-') ?></td>
                        <td>
                            <?php if ($r['cost']): ?>
                                <?= number_format($r['cost'], 2, ',', '.') ?> kr.
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($r['completed_at']): ?>
                                <span class="badge badge-success">
                                    <i class="fas fa-check"></i> Afsluttet <?= date('d/m/Y', strtotime($r['completed_at'])) ?>
                                </span>
                            <?php else: ?>
                                <span class="badge badge-warning">
                                    <i class="fas fa-clock"></i> Igangværende
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!$r['completed_at'] && Auth::hasRole('admin')): ?>
                                <form method="POST" action="/repairs/<?= $r['id'] ?>/complete" style="display:inline;">
                                    <?= CSRF::field() ?>
                                    <button type="submit" class="btn btn-small btn-primary">
                                        <i class="fas fa-check"></i> Afslut
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

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>

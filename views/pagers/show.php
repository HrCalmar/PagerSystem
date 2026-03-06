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
            <a href="/pagers/<?= $pager['id'] ?>/broken" class="btn btn-danger">
                <i class="fas fa-exclamation-triangle"></i> Registrer defekt
            </a>

        <?php elseif ($pager['status'] === 'reserved'): ?>
            <a href="/pagers/<?= $pager['id'] ?>/issue" class="btn btn-primary">
                <i class="fas fa-hand-holding"></i> Udlever
            </a>
            <a href="/pagers/<?= $pager['id'] ?>/broken" class="btn btn-danger">
                <i class="fas fa-exclamation-triangle"></i> Registrer defekt
            </a>

        <?php elseif ($pager['status'] === 'issued'): ?>
            <a href="/pagers/<?= $pager['id'] ?>/return" class="btn btn-primary">
                <i class="fas fa-undo"></i> Returner
            </a>
            <a href="/pagers/<?= $pager['id'] ?>/broken" class="btn btn-danger">
                <i class="fas fa-exclamation-triangle"></i> Registrer defekt
            </a>

        <?php elseif ($pager['status'] === 'for_preparation'): ?>
            <a href="/pagers/<?= $pager['id'] ?>/preparation" class="btn btn-primary">
                <i class="fas fa-clipboard-check"></i> Start klargøring
            </a>
            <a href="/pagers/<?= $pager['id'] ?>/broken" class="btn btn-danger">
                <i class="fas fa-exclamation-triangle"></i> Registrer defekt
            </a>

        <?php elseif ($pager['status'] === 'in_repair'): ?>
            <?php
            $stmtR = \App\Config\Database::getInstance()->prepare(
                "SELECT id FROM repairs WHERE pager_id = ? AND completed_at IS NULL ORDER BY id DESC LIMIT 1"
            );
            $stmtR->execute([$pager['id']]);
            $openRepairId = $stmtR->fetchColumn();
            ?>
            <?php if ($openRepairId): ?>
                <a href="/repairs/<?= $openRepairId ?>/complete" class="btn btn-primary">
                    <i class="fas fa-check-circle"></i> Afslut reparation
                </a>
            <?php else: ?>
                <a href="/pagers/<?= $pager['id'] ?>/repairs/create" class="btn btn-primary">
                    <i class="fas fa-wrench"></i> Registrer reparation
                </a>
            <?php endif; ?>

        <?php elseif ($pager['status'] === 'defect'): ?>
            <a href="/pagers/<?= $pager['id'] ?>/repairs/create" class="btn btn-primary">
                <i class="fas fa-wrench"></i> Send til reparation
            </a>

        <?php endif; ?>

    </div>
</div>
<?php endif; ?>

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
								<a href="/repairs/<?= $r['id'] ?>/complete" class="btn btn-small btn-primary">
									<i class="fas fa-check"></i> Afslut
								</a>
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

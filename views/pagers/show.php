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
            <button onclick="window.print()" class="btn no-print">
                <i class="fas fa-print"></i> Print
            </button>
            <a href="/pagers/<?= $pager['id'] ?>/edit" class="btn no-print">
                <i class="fas fa-edit"></i> Rediger
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success no-print">
        <i class="fas fa-check-circle alert-icon"></i>
        <div class="alert-content">
            <?php
            $messages = [
                'created'         => 'Pager oprettet',
                'updated'         => 'Pager opdateret',
                'reserved'        => 'Pager reserveret',
                'issued'          => 'Pager udleveret',
                'returned'        => 'Pager returneret',
                'stocked'         => 'Pager sat på lager',
                'repair'          => 'Pager sendt til reparation',
                'defect'          => 'Pager markeret som defekt',
                'preparation'     => 'Pager sat til klargøring',
                'sim_added'       => 'SIM-kort tilføjet',
                'sim_deactivated' => 'SIM-kort deaktiveret',
                'repair_created'  => 'Reparation oprettet',
                'repair_completed'=> 'Reparation afsluttet',
                'restored'        => 'Pager gendannet fra arkiv'
            ];
            echo $messages[$_GET['success']] ?? 'Handling udført';
            ?>
        </div>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error no-print">
        <i class="fas fa-exclamation-circle alert-icon"></i>
        <div class="alert-content">
            <?= htmlspecialchars($_GET['error']) ?>
        </div>
    </div>
<?php endif; ?>

<?php if ($pager['status'] === 'archived'): ?>
    <div class="alert alert-warning no-print">
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
<div class="card no-print">
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

<!-- Stamdata + QR -->
<div class="pager-info-grid">
    <div class="card">
        <h2><i class="fas fa-info-circle"></i> Stamdata</h2>
        <dl>
            <dt>Serienummer</dt>
            <dd><?= htmlspecialchars($pager['serial_number']) ?></dd>

            <?php if ($pager['article_number']): ?>
            <dt>Artikelnummer</dt>
            <dd><?= htmlspecialchars($pager['article_number']) ?></dd>
            <?php endif; ?>

            <?php if ($pager['purchase_date']): ?>
            <dt>Indkøbsdato</dt>
            <dd><?= date('d/m/Y', strtotime($pager['purchase_date'])) ?></dd>
            <?php endif; ?>

            <dt>Status</dt>
            <dd><?= status_badge($pager['status'], 'pager') ?></dd>

            <?php if ($pager['phone_number']): ?>
            <dt>Telefonnummer</dt>
            <dd><?= htmlspecialchars($pager['phone_number']) ?></dd>
            <?php endif; ?>

            <?php if ($pager['staff_name']): ?>
            <dt>Udleveret til</dt>
            <dd>
                <a href="/staff/<?= $pager['staff_id'] ?>" class="text-link">
                    <i class="fas fa-user"></i> <?= htmlspecialchars($pager['staff_name']) ?>
                </a>
            </dd>
            <?php endif; ?>

            <?php if ($pager['programming_file_path']): ?>
            <dt>Programmeringsfil</dt>
            <dd>
                <a href="/uploads/programming/<?= htmlspecialchars($pager['programming_file_path']) ?>" class="btn btn-small" download>
                    <i class="fas fa-download"></i> Download
                </a>
            </dd>
            <?php endif; ?>
        </dl>
    </div>

    <div class="card text-center no-print" id="qr-card">
        <h2><i class="fas fa-qrcode"></i> QR-kode</h2>
        <div id="qr-code" style="display:flex;justify-content:center;padding:8px 0;"></div>
        <p class="text-sm text-muted" style="margin-top:8px;"><?= htmlspecialchars($pager['serial_number']) ?></p>
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
                        <td><?= $h['issued_at']   ? date('d/m/Y H:i', strtotime($h['issued_at']))   : '-' ?></td>
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

<!-- Klargøringshistorik -->
<?php if (!empty($preparationHistory)): ?>
<div class="card">
    <h2><i class="fas fa-clipboard-check"></i> Klargøringshistorik</h2>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Dato</th>
                    <th>Udført af</th>
                    <th>Resultat</th>
                    <th>Note</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($preparationHistory as $prep): ?>
                <tr>
                    <td class="text-sm"><?= date('d/m/Y H:i', strtotime($prep['completed_at'])) ?></td>
                    <td><?= htmlspecialchars($prep['checked_by_name'] ?? '-') ?></td>
                    <td>
                        <?php if ($prep['forced']): ?>
                            <span class="badge badge-warning"><i class="fas fa-exclamation"></i> Tvunget</span>
                        <?php elseif ($prep['all_ok']): ?>
                            <span class="badge badge-success"><i class="fas fa-check"></i> Godkendt</span>
                        <?php else: ?>
                            <span class="badge badge-danger"><i class="fas fa-times"></i> Fejl</span>
                        <?php endif; ?>
                        <?php if (!empty($prep['items'])): ?>
                            <details class="prep-details">
                                <summary>Vis tjekliste</summary>
                                <ul class="prep-items">
                                    <?php foreach ($prep['items'] as $item): ?>
                                    <li class="<?= $item['passed'] ? 'passed' : 'failed' ?>">
                                        <i class="fas <?= $item['passed'] ? 'fa-check' : 'fa-times' ?>"></i>
                                        <?= htmlspecialchars($item['label']) ?>
                                        <?php if ($item['note']): ?>
                                            <span class="text-muted"> — <?= htmlspecialchars($item['note']) ?></span>
                                        <?php endif; ?>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </details>
                        <?php endif; ?>
                    </td>
                    <td class="text-sm"><?= htmlspecialchars($prep['note'] ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Defekthistorik -->
<?php if (!empty($defectHistory)): ?>
<div class="card">
    <h2><i class="fas fa-times-circle"></i> Defekthistorik</h2>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Dato</th>
                    <th>Indberettet af</th>
                    <th>Symptomer</th>
                    <th>Beskrivelse</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($defectHistory as $defect): ?>
                <tr>
                    <td class="text-sm"><?= date('d/m/Y H:i', strtotime($defect['reported_at'])) ?></td>
                    <td><?= htmlspecialchars($defect['reported_by_name'] ?? '-') ?></td>
                    <td>
                        <?php if ($defect['symptoms']): ?>
                            <span class="badge badge-danger"><?= htmlspecialchars($defect['symptoms']) ?></span>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-sm"><?= htmlspecialchars($defect['description'] ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Reparationer -->
<div class="card">
    <h2><i class="fas fa-wrench"></i> Reparationer</h2>

    <?php if (Auth::hasRole('admin') && $pager['status'] !== 'archived'): ?>
        <div style="margin-bottom: 16px;" class="no-print">
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
                        <th>Kvittering</th>
                        <th>Status</th>
                        <th class="text-right no-print">Handling</th>
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
                            <?php if ($r['receipt_path']): ?>
                                <a href="/uploads/receipts/<?= htmlspecialchars($r['receipt_path']) ?>" target="_blank" class="btn btn-small no-print">
                                    <i class="fas fa-paperclip"></i> Åbn
                                </a>
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
                        <td class="no-print">
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

<style>
.pager-info-grid {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 16px;
    align-items: start;
}
@media (max-width: 640px) {
    .pager-info-grid { grid-template-columns: 1fr; }
    #qr-card { display: none; }
}
#qr-card { min-width: 180px; }
.prep-details { margin-top: 6px; }
.prep-details summary { cursor: pointer; font-size: 0.8rem; color: var(--gray-500); }
.prep-items { list-style: none; padding: 6px 0 0; margin: 0; font-size: 0.85rem; }
.prep-items li { padding: 2px 0; }
.prep-items li.passed { color: var(--success); }
.prep-items li.failed  { color: var(--danger); }
@media print {
    .no-print { display: none !important; }
    .pager-info-grid { grid-template-columns: 1fr; }
}
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
new QRCode(document.getElementById('qr-code'), {
    text: window.location.origin + '/pagers/<?= $pager['id'] ?>',
    width: 140,
    height: 140,
    colorDark: '#1a1a2e',
    colorLight: '#ffffff',
    correctLevel: QRCode.CorrectLevel.M
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>

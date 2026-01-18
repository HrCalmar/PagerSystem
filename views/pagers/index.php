<?php
// views/pagers/index.php
use App\Core\Auth;
$title = 'Pagere';
$showArchived = $filters['show_archived'] ?? false;
ob_start();
?>

<div class="page-header">
    <h1><i class="fas fa-pager"></i> <?= $showArchived ? 'Arkiverede pagere' : 'Pagere' ?></h1>
    <div class="page-actions">
        <?php if ($showArchived): ?>
            <a href="/pagers" class="btn">
                <i class="fas fa-arrow-left"></i> Tilbage til aktive
            </a>
        <?php else: ?>
            <?php if (Auth::hasRole('admin')): ?>
                <a href="/pagers?archived=1" class="btn">
                    <i class="fas fa-archive"></i> Vis arkiverede
                </a>
                <a href="/pagers/create" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Opret pager
                </a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle alert-icon"></i>
        <div class="alert-content">
            <?php
            $messages = [
                'archived' => 'Pager arkiveret',
                'restored' => 'Pager gendannet'
            ];
            echo $messages[$_GET['success']] ?? 'Handling udført';
            ?>
        </div>
    </div>
<?php endif; ?>

<?php if (!$showArchived): ?>
<div class="filters">
    <form method="GET" class="filter-form">
        <div class="filter-group">
            <i class="fas fa-search"></i>
            <input type="text" name="search" placeholder="Søg serienummer, artikel, telefon..." value="<?= htmlspecialchars($filters['search']) ?>">
        </div>
        <select name="status" class="filter-select">
            <option value="">Alle status</option>
            <option value="in_stock" <?= $filters['status'] === 'in_stock' ? 'selected' : '' ?>>På lager</option>
            <option value="reserved" <?= $filters['status'] === 'reserved' ? 'selected' : '' ?>>Reserveret</option>
            <option value="issued" <?= $filters['status'] === 'issued' ? 'selected' : '' ?>>Udleveret</option>
            <option value="for_preparation" <?= $filters['status'] === 'for_preparation' ? 'selected' : '' ?>>Til klargøring</option>
            <option value="in_repair" <?= $filters['status'] === 'in_repair' ? 'selected' : '' ?>>Til reparation</option>
            <option value="defect" <?= $filters['status'] === 'defect' ? 'selected' : '' ?>>Defekt</option>
        </select>
        <button type="submit" class="btn"><i class="fas fa-filter"></i> Filtrer</button>
        <?php if ($filters['search'] || $filters['status']): ?>
            <a href="/pagers" class="btn"><i class="fas fa-times"></i> Nulstil</a>
        <?php endif; ?>
    </form>
</div>
<?php endif; ?>

<div class="card">
    <?php if (empty($pagers)): ?>
        <div class="empty-state">
            <i class="fas fa-<?= $showArchived ? 'archive' : 'pager' ?>"></i>
            <p><?= $showArchived ? 'Ingen arkiverede pagere' : 'Ingen pagere fundet' ?></p>
            <?php if (!$showArchived && Auth::hasRole('admin')): ?>
                <a href="/pagers/create" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Opret pager
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Serienummer</th>
                        <th>Artikelnummer</th>
                        <th>Telefonnummer</th>
                        <th>Status</th>
                        <?php if (!$showArchived): ?>
                            <th>Udleveret til</th>
                        <?php else: ?>
                            <th>Arkiveret</th>
                        <?php endif; ?>
                        <th class="text-right">Handlinger</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pagers as $pager): ?>
                    <tr>
                        <td class="font-medium">
                            <a href="/pagers/<?= $pager['id'] ?>" class="text-link">
                                <?= htmlspecialchars($pager['serial_number']) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($pager['article_number'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($pager['phone_number'] ?? '-') ?></td>
                        <td><?= status_badge($pager['status'], 'pager') ?></td>
                        <?php if (!$showArchived): ?>
                            <td>
                                <?php if ($pager['staff_name']): ?>
                                    <a href="/staff/<?= $pager['staff_id'] ?>" class="text-link">
                                        <i class="fas fa-user"></i> <?= htmlspecialchars($pager['staff_name']) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        <?php else: ?>
                            <td>
                                <?= $pager['archived_at'] ? date('d/m/Y', strtotime($pager['archived_at'])) : '-' ?>
                            </td>
                        <?php endif; ?>
                        <td>
                            <div class="action-buttons">
                                <a href="/pagers/<?= $pager['id'] ?>" class="btn-icon" title="Vis detaljer">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if (Auth::hasRole('admin')): ?>
                                    <?php if ($showArchived): ?>
                                        <form method="POST" action="/pagers/<?= $pager['id'] ?>/restore" style="display:inline;">
                                            <?= \App\Core\CSRF::field() ?>
                                            <button type="submit" class="btn-icon btn-primary" title="Gendan">
                                                <i class="fas fa-undo"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <a href="/pagers/<?= $pager['id'] ?>/edit" class="btn-icon" title="Rediger">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($pager['status'] === 'in_stock'): ?>
                                            <a href="/pagers/<?= $pager['id'] ?>/issue" class="btn-icon btn-primary" title="Udlever">
                                                <i class="fas fa-hand-holding"></i>
                                            </a>
                                        <?php elseif ($pager['status'] === 'issued'): ?>
                                            <a href="/pagers/<?= $pager['id'] ?>/return" class="btn-icon btn-warning" title="Returner">
                                                <i class="fas fa-undo"></i>
                                            </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
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

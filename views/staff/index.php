<?php
// views/staff/index.php
use App\Core\Auth;
$title = 'Brandfolk';
$showDeleted = $filters['show_deleted'] ?? false;
ob_start();
?>

<div class="page-header">
    <h1><i class="fas fa-users"></i> <?= $showDeleted ? 'Slettede brandfolk' : 'Brandfolk' ?></h1>
    <div class="page-actions">
        <?php if ($showDeleted): ?>
            <a href="/staff" class="btn">
                <i class="fas fa-arrow-left"></i> Tilbage til aktive
            </a>
        <?php else: ?>
            <?php if (Auth::hasRole('admin')): ?>
                <a href="/staff?deleted=1" class="btn">
                    <i class="fas fa-trash-alt"></i> Vis slettede
                </a>
                <a href="/staff/create" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Opret brandmand
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
                'deleted' => 'Brandmand slettet',
                'created' => 'Brandmand oprettet'
            ];
            echo $messages[$_GET['success']] ?? 'Handling udført';
            ?>
        </div>
    </div>
<?php endif; ?>

<?php if (!$showDeleted): ?>
<div class="filters">
    <form method="GET" class="filter-form">
        <div class="filter-group">
            <i class="fas fa-search"></i>
            <input type="text" name="search" placeholder="Søg navn, lønnummer..." value="<?= htmlspecialchars($filters['search']) ?>">
        </div>
        <select name="status" class="filter-select">
            <option value="">Alle status</option>
            <option value="active" <?= $filters['status'] === 'active' ? 'selected' : '' ?>>Aktiv</option>
            <option value="inactive" <?= $filters['status'] === 'inactive' ? 'selected' : '' ?>>Inaktiv</option>
        </select>
        <button type="submit" class="btn"><i class="fas fa-filter"></i> Filtrer</button>
        <?php if ($filters['search'] || $filters['status']): ?>
            <a href="/staff" class="btn"><i class="fas fa-times"></i> Nulstil</a>
        <?php endif; ?>
    </form>
</div>
<?php endif; ?>

<div class="card">
    <?php if (empty($staff)): ?>
        <div class="empty-state">
            <i class="fas fa-<?= $showDeleted ? 'trash-alt' : 'users' ?>"></i>
            <p><?= $showDeleted ? 'Ingen slettede brandfolk' : 'Ingen brandfolk fundet' ?></p>
            <?php if (!$showDeleted && Auth::hasRole('admin')): ?>
                <a href="/staff/create" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Opret brandmand
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Navn</th>
                        <th>Lønnummer</th>
                        <th>Stationer</th>
                        <th>Aktive pagere</th>
                        <th>Status</th>
                        <?php if ($showDeleted): ?>
                            <th>Slettet</th>
                        <?php endif; ?>
                        <th class="text-right">Handlinger</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($staff as $s): ?>
                    <tr>
                        <td class="font-medium">
                            <a href="/staff/<?= $s['id'] ?>" class="text-link">
                                <i class="fas fa-user"></i> <?= htmlspecialchars($s['name']) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($s['employee_number']) ?></td>
                        <td>
                            <?php if ($s['stations']): ?>
                                <span class="text-sm"><i class="fas fa-building"></i> <?= htmlspecialchars($s['stations']) ?></span>
                            <?php else: ?>
                                <span class="text-muted">Ingen stationer</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($s['active_pagers'] > 0): ?>
                                <span class="badge badge-info">
                                    <i class="fas fa-pager"></i> <?= $s['active_pagers'] ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">0</span>
                            <?php endif; ?>
                        </td>
                        <td><?= status_badge($s['status'], 'staff') ?></td>
                        <?php if ($showDeleted): ?>
                            <td><?= $s['deleted_at'] ? date('d/m/Y', strtotime($s['deleted_at'])) : '-' ?></td>
                        <?php endif; ?>
                        <td>
                            <div class="action-buttons">
                                <a href="/staff/<?= $s['id'] ?>" class="btn-icon" title="Vis detaljer">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if (Auth::hasRole('admin') && !$showDeleted): ?>
                                    <a href="/staff/<?= $s['id'] ?>/edit" class="btn-icon" title="Rediger">
                                        <i class="fas fa-edit"></i>
                                    </a>
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

<?php
use App\Core\Auth;
$title = 'Aktivitetslog';
ob_start();

$actionLabels = [
    'reserve_pager' => ['label' => 'Reserveret pager', 'icon' => 'fa-bookmark', 'color' => 'warning'],
    'issue_pager' => ['label' => 'Udleveret pager', 'icon' => 'fa-hand-holding', 'color' => 'success'],
    'return_pager' => ['label' => 'Returneret pager', 'icon' => 'fa-undo', 'color' => 'info'],
    'stock_pager' => ['label' => 'Sat på lager', 'icon' => 'fa-box', 'color' => 'success'],
    'repair_pager' => ['label' => 'Sendt til reparation', 'icon' => 'fa-wrench', 'color' => 'warning'],
    'defect_pager' => ['label' => 'Markeret defekt', 'icon' => 'fa-times-circle', 'color' => 'danger'],
    'preparation_pager' => ['label' => 'Sat til klargøring', 'icon' => 'fa-tools', 'color' => 'info'],
    'archive_pager' => ['label' => 'Arkiveret pager', 'icon' => 'fa-archive', 'color' => 'secondary'],
    'restore_pager' => ['label' => 'Gendannet pager', 'icon' => 'fa-undo', 'color' => 'success'],
    'add_sim' => ['label' => 'Tilføjet SIM', 'icon' => 'fa-sim-card', 'color' => 'success'],
    'deactivate_sim' => ['label' => 'Deaktiveret SIM', 'icon' => 'fa-sim-card', 'color' => 'danger'],
    'deactivate_staff' => ['label' => 'Deaktiveret brandmand', 'icon' => 'fa-user-times', 'color' => 'warning'],
    'reactivate_staff' => ['label' => 'Reaktiveret brandmand', 'icon' => 'fa-user-check', 'color' => 'success'],
    'delete_staff' => ['label' => 'Slettet brandmand', 'icon' => 'fa-trash', 'color' => 'danger'],
    'add_station' => ['label' => 'Tilføjet station', 'icon' => 'fa-building', 'color' => 'success'],
    'remove_station' => ['label' => 'Fjernet station', 'icon' => 'fa-building', 'color' => 'warning'],
    'add_competency' => ['label' => 'Tilføjet kompetence', 'icon' => 'fa-certificate', 'color' => 'success'],
    'remove_competency' => ['label' => 'Fjernet kompetence', 'icon' => 'fa-certificate', 'color' => 'warning'],
    'complete_repair' => ['label' => 'Afsluttet reparation', 'icon' => 'fa-check', 'color' => 'success'],
];

$entityLabels = [
    'pager' => ['label' => 'Pager', 'icon' => 'fa-pager', 'route' => '/pagers/'],
    'staff' => ['label' => 'Brandmand', 'icon' => 'fa-user', 'route' => '/staff/'],
    'sim_card' => ['label' => 'SIM-kort', 'icon' => 'fa-sim-card', 'route' => null],
    'station_assignment' => ['label' => 'Stationstilknytning', 'icon' => 'fa-building', 'route' => null],
    'staff_competency' => ['label' => 'Kompetence', 'icon' => 'fa-certificate', 'route' => null],
    'repair' => ['label' => 'Reparation', 'icon' => 'fa-wrench', 'route' => null],
];
?>

<div class="page-header">
    <h1><i class="fas fa-history"></i> Aktivitetslog</h1>
</div>

<div class="filters">
    <form method="GET" class="filter-form">
        <select name="user_id" class="filter-select">
            <option value="">Alle brugere</option>
            <?php foreach ($users as $u): ?>
                <option value="<?= $u['id'] ?>" <?= $filters['user_id'] == $u['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($u['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <select name="action_type" class="filter-select">
            <option value="">Alle handlinger</option>
            <?php foreach ($actionTypes as $type): ?>
                <option value="<?= $type ?>" <?= $filters['action_type'] === $type ? 'selected' : '' ?>>
                    <?= $actionLabels[$type]['label'] ?? $type ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <select name="entity_type" class="filter-select">
            <option value="">Alle typer</option>
            <?php foreach ($entityTypes as $type): ?>
                <option value="<?= $type ?>" <?= $filters['entity_type'] === $type ? 'selected' : '' ?>>
                    <?= $entityLabels[$type]['label'] ?? $type ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <input type="date" name="date_from" value="<?= $filters['date_from'] ?>" placeholder="Fra dato">
        <input type="date" name="date_to" value="<?= $filters['date_to'] ?>" placeholder="Til dato">
        
        <button type="submit" class="btn"><i class="fas fa-filter"></i> Filtrer</button>
        <?php if (array_filter($filters)): ?>
            <a href="/audit" class="btn"><i class="fas fa-times"></i> Nulstil</a>
        <?php endif; ?>
    </form>
</div>

<div class="card">
    <?php if (empty($logs)): ?>
        <div class="empty-state">
            <i class="fas fa-history"></i>
            <p>Ingen aktivitet fundet</p>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Tidspunkt</th>
                        <th>Bruger</th>
                        <th>Handling</th>
                        <th>Type</th>
                        <th>ID</th>
                        <th>IP</th>
                        <th class="text-right">Detaljer</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <?php
                    $action = $actionLabels[$log['action_type']] ?? ['label' => $log['action_type'], 'icon' => 'fa-circle', 'color' => 'secondary'];
                    $entity = $entityLabels[$log['entity_type']] ?? ['label' => $log['entity_type'], 'icon' => 'fa-cube', 'route' => null];
                    ?>
                    <tr>
                        <td class="text-sm">
                            <?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?>
                        </td>
                        <td>
                            <?php if ($log['user_name']): ?>
                                <span class="text-link">
                                    <i class="fas fa-user"></i> <?= htmlspecialchars($log['user_name']) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">System</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge-<?= $action['color'] ?>">
                                <i class="fas <?= $action['icon'] ?>"></i> <?= $action['label'] ?>
                            </span>
                        </td>
                        <td>
                            <span class="text-sm">
                                <i class="fas <?= $entity['icon'] ?>"></i> <?= $entity['label'] ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($entity['route']): ?>
                                <a href="<?= $entity['route'] . $log['entity_id'] ?>" class="text-link">
                                    #<?= $log['entity_id'] ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">#<?= $log['entity_id'] ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-sm text-muted"><?= htmlspecialchars($log['ip_address'] ?? '-') ?></td>
                        <td>
                            <div class="action-buttons">
                                <a href="/audit/<?= $log['id'] ?>" class="btn-icon" title="Vis detaljer">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if (count($logs) >= 500): ?>
            <div class="alert alert-info" style="margin-top: 16px;">
                <i class="fas fa-info-circle"></i>
                Viser de seneste 500 poster. Brug filtre for at indsnævre resultatet.
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
.filter-form {
    display: flex;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
}

.filter-form input[type="date"] {
    width: 150px;
}

.badge-secondary {
    background: var(--gray-200);
    color: var(--gray-700);
}
</style>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
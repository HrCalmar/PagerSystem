<?php
use App\Core\Auth;
$title = 'Aktivitetsdetaljer';
ob_start();

$actionLabels = [
    'reserve_pager' => 'Reserveret pager',
    'issue_pager' => 'Udleveret pager',
    'return_pager' => 'Returneret pager',
    'stock_pager' => 'Sat på lager',
    'repair_pager' => 'Sendt til reparation',
    'defect_pager' => 'Markeret defekt',
    'preparation_pager' => 'Sat til klargøring',
    'archive_pager' => 'Arkiveret pager',
    'restore_pager' => 'Gendannet pager',
    'add_sim' => 'Tilføjet SIM-kort',
    'deactivate_sim' => 'Deaktiveret SIM-kort',
    'deactivate_staff' => 'Deaktiveret brandmand',
    'reactivate_staff' => 'Reaktiveret brandmand',
    'delete_staff' => 'Slettet brandmand',
    'add_station' => 'Tilføjet stationstilknytning',
    'remove_station' => 'Fjernet stationstilknytning',
    'add_competency' => 'Tilføjet kompetence',
    'remove_competency' => 'Fjernet kompetence',
    'complete_repair' => 'Afsluttet reparation',
];

$entityLabels = [
    'pager' => ['label' => 'Pager', 'route' => '/pagers/'],
    'staff' => ['label' => 'Brandmand', 'route' => '/staff/'],
    'sim_card' => ['label' => 'SIM-kort', 'route' => null],
    'station_assignment' => ['label' => 'Stationstilknytning', 'route' => null],
    'staff_competency' => ['label' => 'Kompetence', 'route' => null],
    'repair' => ['label' => 'Reparation', 'route' => null],
];

$beforeData = $log['before_data'] ? json_decode($log['before_data'], true) : null;
$afterData = $log['after_data'] ? json_decode($log['after_data'], true) : null;
?>

<div class="page-header">
    <h1><i class="fas fa-history"></i> Aktivitetsdetaljer</h1>
    <a href="/audit" class="btn"><i class="fas fa-arrow-left"></i> Tilbage til log</a>
</div>

<div class="grid-2col">
    <div class="card">
        <h2><i class="fas fa-info-circle"></i> Grunddata</h2>
        <dl>
            <dt>ID</dt>
            <dd>#<?= $log['id'] ?></dd>
            
            <dt>Tidspunkt</dt>
            <dd><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></dd>
            
            <dt>Bruger</dt>
            <dd>
                <?php if ($log['user_name']): ?>
                    <i class="fas fa-user"></i> <?= htmlspecialchars($log['user_name']) ?>
                    <span class="text-muted">(<?= htmlspecialchars($log['username']) ?>)</span>
                <?php else: ?>
                    <span class="text-muted">System</span>
                <?php endif; ?>
            </dd>
            
            <dt>Handling</dt>
            <dd>
                <span class="badge badge-info">
                    <?= $actionLabels[$log['action_type']] ?? $log['action_type'] ?>
                </span>
            </dd>
            
            <dt>Entitet</dt>
            <dd>
                <?= $entityLabels[$log['entity_type']]['label'] ?? $log['entity_type'] ?>
                <?php 
                $route = $entityLabels[$log['entity_type']]['route'] ?? null;
                if ($route): 
                ?>
                    <a href="<?= $route . $log['entity_id'] ?>" class="text-link">
                        #<?= $log['entity_id'] ?>
                    </a>
                <?php else: ?>
                    <span class="text-muted">#<?= $log['entity_id'] ?></span>
                <?php endif; ?>
            </dd>
            
            <dt>IP-adresse</dt>
            <dd><?= htmlspecialchars($log['ip_address'] ?? '-') ?></dd>
        </dl>
    </div>
    
    <div class="card">
        <h2><i class="fas fa-exchange-alt"></i> Ændringer</h2>
        
        <?php if ($beforeData || $afterData): ?>
            <div class="data-comparison">
                <?php if ($beforeData): ?>
                    <div class="data-block before">
                        <h4><i class="fas fa-minus-circle"></i> Før</h4>
                        <pre><?= htmlspecialchars(json_encode($beforeData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                    </div>
                <?php endif; ?>
                
                <?php if ($afterData): ?>
                    <div class="data-block after">
                        <h4><i class="fas fa-plus-circle"></i> Efter</h4>
                        <pre><?= htmlspecialchars(json_encode($afterData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <p class="text-muted"><i class="fas fa-info-circle"></i> Ingen data registreret</p>
        <?php endif; ?>
    </div>
</div>

<style>
.data-comparison {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.data-block {
    padding: 12px;
    border-radius: var(--radius-md);
}

.data-block h4 {
    margin: 0 0 8px 0;
    font-size: 14px;
    font-weight: 600;
}

.data-block.before {
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.2);
}

.data-block.before h4 {
    color: var(--danger);
}

.data-block.after {
    background: rgba(34, 197, 94, 0.1);
    border: 1px solid rgba(34, 197, 94, 0.2);
}

.data-block.after h4 {
    color: var(--success);
}

.data-block pre {
    margin: 0;
    font-size: 12px;
    white-space: pre-wrap;
    word-break: break-word;
    background: rgba(0,0,0,0.05);
    padding: 8px;
    border-radius: var(--radius-sm);
}
</style>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
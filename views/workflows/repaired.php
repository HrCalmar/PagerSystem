<?php
// views/workflows/repaired.php
use App\Core\CSRF;
$title = 'Afslut reparation';
ob_start();

// $repair, $transitions og $hasAssignment sættes af WorkflowController::showRepaired
?>

<div class="page-header">
    <h1><i class="fas fa-check-circle"></i> Afslut reparation</h1>
    <a href="/pagers/<?= $repair['pager_id'] ?>" class="btn"><i class="fas fa-arrow-left"></i> Tilbage</a>
</div>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle alert-icon"></i>
        <div class="alert-content"><?= htmlspecialchars($_GET['error']) ?></div>
    </div>
<?php endif; ?>

<div class="card">
    <h2><i class="fas fa-wrench"></i> Reparation #<?= $repair['id'] ?> — <?= htmlspecialchars($repair['serial_number'] ?? '') ?></h2>
    <dl>
        <dt>Dato</dt>
        <dd><?= htmlspecialchars($repair['repair_date']) ?></dd>
        <?php if ($repair['vendor']): ?>
            <dt>Værksted</dt>
            <dd><?= htmlspecialchars($repair['vendor']) ?></dd>
        <?php endif; ?>
        <?php if ($repair['description']): ?>
            <dt>Beskrivelse</dt>
            <dd><?= htmlspecialchars($repair['description']) ?></dd>
        <?php endif; ?>
    </dl>

    <?php if ($hasAssignment): ?>
        <div class="alert alert-info" style="margin-top:1rem">
            <i class="fas fa-info-circle alert-icon"></i>
            <div class="alert-content">
                Pageren er stadig tilknyttet en brandmand. Reparationen markeres som afsluttet, men status ændres først når pageren returneres.
            </div>
        </div>
    <?php endif; ?>

    <form method="POST" action="/repairs/<?= $repair['id'] ?>/complete" style="margin-top:1.25rem">
        <?= CSRF::field() ?>

        <div class="form-group">
            <label for="cost"><i class="fas fa-receipt"></i> Reparationsomkostning</label>
            <div class="input-group">
                <input type="number" id="cost" name="cost" step="0.01" min="0"
                       value="<?= htmlspecialchars($repair['cost'] ?? '') ?>"
                       placeholder="0.00">
                <span class="input-addon">DKK</span>
            </div>
        </div>

        <?php if (!$hasAssignment): ?>
            <div class="form-group">
                <label><i class="fas fa-arrow-right"></i> Næste status</label>
                <div class="transition-options">
                    <?php foreach ($transitions as $t): ?>
                        <label class="transition-card <?= $t['is_default'] ? 'is-default' : '' ?>">
                            <input type="radio" name="to_status" value="<?= $t['to_status'] ?>"
                                   <?= $t['is_default'] ? 'checked' : '' ?> required>
                            <span class="transition-label"><?= htmlspecialchars($t['label']) ?></span>
                            <?php if ($t['is_default']): ?>
                                <span class="badge badge-info">Standard</span>
                            <?php endif; ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <input type="hidden" name="to_status" value="for_preparation">
        <?php endif; ?>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Afslut reparation</button>
            <a href="/pagers/<?= $repair['pager_id'] ?>" class="btn"><i class="fas fa-times"></i> Annuller</a>
        </div>
    </form>
</div>

<style>
.input-group{display:flex;align-items:stretch;gap:0}
.input-group input{border-radius:var(--radius) 0 0 var(--radius);flex:1}
.input-addon{background:var(--bg-secondary);border:1px solid var(--border);border-left:none;border-radius:0 var(--radius) var(--radius) 0;padding:0 .75rem;display:flex;align-items:center;color:var(--text-muted);font-size:.875rem;white-space:nowrap}
.transition-options{display:flex;flex-direction:column;gap:.5rem;margin-top:.5rem}
.transition-card{display:flex;align-items:center;gap:.75rem;padding:.75rem 1rem;border:2px solid var(--border);border-radius:var(--radius);cursor:pointer;transition:border-color .15s}
.transition-card:hover{border-color:var(--primary)}
.transition-card.is-default{border-color:var(--primary-light,#93c5fd);background:var(--bg-secondary)}
.transition-card input{margin:0}
</style>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
<?php
// views/workflows/return.php
use App\Core\CSRF;
$title = 'Returner pager';
ob_start();
?>

<div class="page-header">
    <h1><i class="fas fa-undo"></i> Returner pager</h1>
    <a href="/pagers/<?= $pager['id'] ?>" class="btn"><i class="fas fa-arrow-left"></i> Tilbage</a>
</div>

<div class="card">
    <h2><i class="fas fa-pager"></i> <?= htmlspecialchars($pager['serial_number']) ?></h2>
    <form method="POST" action="/pagers/<?= $pager['id'] ?>/return">
        <?= CSRF::field() ?>

        <div class="form-group">
            <label for="reason"><i class="fas fa-comment"></i> Årsag til returnering</label>
            <input type="text" id="reason" name="reason" placeholder="Fx. opsigelse, bytter pager, ...">
        </div>

        <?php if (count($transitions) > 1): ?>
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
            <input type="hidden" name="to_status" value="<?= htmlspecialchars($transitions[0]['to_status']) ?>">
        <?php endif; ?>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-undo"></i> Returner</button>
            <a href="/pagers/<?= $pager['id'] ?>" class="btn"><i class="fas fa-times"></i> Annuller</a>
        </div>
    </form>
</div>

<style>
.transition-options { display: flex; flex-direction: column; gap: 0.5rem; margin-top: 0.5rem; }
.transition-card { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; border: 2px solid var(--border); border-radius: var(--radius); cursor: pointer; transition: all 0.15s; }
.transition-card:hover { border-color: var(--primary); }
.transition-card.is-default { border-color: var(--primary-light, #93c5fd); background: var(--bg-secondary); }
.transition-card input { margin: 0; }
.transition-card input:checked ~ .transition-label { font-weight: 600; }
</style>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
<?php
// views/workflows/broken.php
use App\Core\CSRF;
$title = 'Registrer defekt';
ob_start();
?>

<div class="page-header">
    <h1><i class="fas fa-exclamation-triangle"></i> Registrer defekt</h1>
    <a href="/pagers/<?= $pager['id'] ?>" class="btn"><i class="fas fa-arrow-left"></i> Tilbage</a>
</div>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle alert-icon"></i>
        <div class="alert-content"><?= htmlspecialchars($_GET['error']) ?></div>
    </div>
<?php endif; ?>

<div class="card">
    <h2><i class="fas fa-pager"></i> <?= htmlspecialchars($pager['serial_number']) ?></h2>
    <form method="POST" action="/pagers/<?= $pager['id'] ?>/broken" id="broken-form">
        <?= CSRF::field() ?>

        <div class="form-group">
            <label><i class="fas fa-bug"></i> Symptomer *</label>
            <small class="form-help">Vælg alle relevante symptomer</small>
            <div class="checkbox-grid">
                <?php foreach ($symptoms as $symptom): ?>
                    <label class="checkbox-card">
                        <input type="checkbox" name="symptom_ids[]" value="<?= $symptom['id'] ?>">
                        <span><i class="fas fa-times-circle"></i> <?= htmlspecialchars($symptom['label']) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <div id="symptom-error" class="form-error" style="display:none">
                <i class="fas fa-exclamation-circle"></i> Vælg mindst ét symptom
            </div>
        </div>

        <div class="form-group">
            <label for="description"><i class="fas fa-comment"></i> Beskrivelse</label>
            <textarea id="description" name="description" rows="3" placeholder="Yderligere detaljer om fejlen..."></textarea>
        </div>

        <div class="form-group">
            <label><i class="fas fa-arrow-right"></i> Næste status *</label>
            <div class="transition-options">
                <?php foreach ($transitions as $i => $t): ?>
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

        <div class="form-actions">
            <button type="submit" class="btn btn-danger"><i class="fas fa-save"></i> Registrer defekt</button>
            <a href="/pagers/<?= $pager['id'] ?>" class="btn"><i class="fas fa-times"></i> Annuller</a>
        </div>
    </form>
</div>

<script>
document.getElementById('broken-form').addEventListener('submit', function(e) {
    const checked = document.querySelectorAll('input[name="symptom_ids[]"]:checked').length;
    if (checked === 0) {
        e.preventDefault();
        document.getElementById('symptom-error').style.display = 'block';
    }
});
</script>

<style>
.checkbox-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 0.5rem; margin-top: 0.5rem; }
.checkbox-card { display: flex; align-items: center; gap: 0.5rem; padding: 0.6rem 0.75rem; border: 1px solid var(--border); border-radius: var(--radius); cursor: pointer; transition: all 0.15s; }
.checkbox-card:hover { border-color: var(--primary); background: var(--bg-secondary); }
.checkbox-card input { margin: 0; }
.checkbox-card input:checked + span { color: var(--danger); font-weight: 500; }
.transition-options { display: flex; flex-direction: column; gap: 0.5rem; margin-top: 0.5rem; }
.transition-card { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; border: 2px solid var(--border); border-radius: var(--radius); cursor: pointer; transition: all 0.15s; }
.transition-card:hover { border-color: var(--primary); }
.transition-card input { margin: 0; }
.transition-card.is-default { border-color: var(--primary-light, #93c5fd); background: var(--bg-secondary); }
.transition-card input:checked ~ .transition-label { font-weight: 600; }
.form-error { color: var(--danger); font-size: 0.875rem; margin-top: 0.4rem; }
</style>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
<?php
// views/workflows/preparation.php
use App\Core\CSRF;
$title = 'Klargøring';
ob_start();
?>

<div class="page-header">
    <h1><i class="fas fa-tools"></i> Klargøring</h1>
    <a href="/pagers/<?= $pager['id'] ?>" class="btn"><i class="fas fa-arrow-left"></i> Tilbage</a>
</div>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle alert-icon"></i>
        <div class="alert-content"><?= htmlspecialchars($_GET['error']) ?></div>
    </div>
<?php endif; ?>

<?php if ($latest): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle alert-icon"></i>
        <div class="alert-content">
            <strong>Tidligere registrerede fejl:</strong> <?= htmlspecialchars($latest['symptoms'] ?? 'Ingen symptomer registreret') ?>
            <?php if ($latest['description']): ?>
                <br><em><?= htmlspecialchars($latest['description']) ?></em>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<div class="card">
    <h2><i class="fas fa-pager"></i> <?= htmlspecialchars($pager['serial_number']) ?></h2>

    <form method="POST" action="/pagers/<?= $pager['id'] ?>/preparation" id="prep-form">
        <?= CSRF::field() ?>

        <div class="form-group">
            <div class="prep-header">
                <label><i class="fas fa-clipboard-check"></i> Tjekliste</label>
                <button type="button" class="btn btn-primary" id="check-all-btn">
                    <i class="fas fa-check-double"></i> Check alle OK
                </button>
            </div>
            <input type="hidden" name="all_ok" id="all_ok_input" value="">

            <div class="prep-checks">
                <?php foreach ($checks as $check): ?>
                    <div class="prep-check-row" id="row-<?= $check['id'] ?>">
                        <label class="prep-check-label">
                            <input type="checkbox" name="checks[<?= $check['id'] ?>][passed]"
                                   value="1" class="check-item" data-id="<?= $check['id'] ?>">
                            <span class="check-text"><?= htmlspecialchars($check['label']) ?></span>
                        </label>
                        <input type="text"
                               name="checks[<?= $check['id'] ?>][note]"
                               class="check-note"
                               placeholder="Evt. bemærkning...">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="form-group">
            <label for="note"><i class="fas fa-comment"></i> Generel note</label>
            <textarea id="note" name="note" rows="2" placeholder="Evt. generelle bemærkninger..."></textarea>
        </div>

        <div id="force-section" style="display:none">
            <?php if ($canForce): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle alert-icon"></i>
                    <div class="alert-content">
                        <strong>En eller flere checks er ikke godkendt.</strong>
                        Du kan gennemtvinge pageren til lager, men det kræver en begrundelse.
                    </div>
                </div>
                <div class="form-group">
                    <label for="forced_reason"><i class="fas fa-pen"></i> Begrundelse for gennemtvingning *</label>
                    <textarea id="forced_reason" name="forced_reason" rows="2"></textarea>
                </div>
                <input type="hidden" name="forced" value="1">
            <?php else: ?>
                <div class="alert alert-error">
                    <i class="fas fa-lock alert-icon"></i>
                    <div class="alert-content">Du har ikke rettigheder til at gennemtvinge klargøring med fejlede checks.</div>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary" id="submit-btn">
                <i class="fas fa-box"></i> Sæt på lager
            </button>
            <a href="/pagers/<?= $pager['id'] ?>" class="btn"><i class="fas fa-times"></i> Annuller</a>
        </div>
    </form>
</div>

<style>
.prep-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; }
.prep-checks { display: flex; flex-direction: column; gap: 0.4rem; }
.prep-check-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; align-items: center; padding: 0.6rem 0.75rem; border: 1px solid var(--border); border-radius: var(--radius); transition: background 0.15s; }
.prep-check-row.is-ok { background: #f0fdf4; border-color: #86efac; }
.prep-check-row.is-fail { background: #fef2f2; border-color: #fca5a5; }
.prep-check-label { display: flex; align-items: center; gap: 0.5rem; cursor: pointer; }
.prep-check-label input { margin: 0; }
.check-note { font-size: 0.875rem; padding: 0.3rem 0.5rem; border: 1px solid var(--border); border-radius: var(--radius); width: 100%; }
</style>

<script>
const checkItems = document.querySelectorAll('.check-item');
const forceSection = document.getElementById('force-section');
const allOkInput = document.getElementById('all_ok_input');
const canForce = <?= $canForce ? 'true' : 'false' ?>;

function updateForceSection() {
    const total = checkItems.length;
    const checked = document.querySelectorAll('.check-item:checked').length;
    const hasFailed = checked < total;

    checkItems.forEach(cb => {
        const row = cb.closest('.prep-check-row');
        row.classList.toggle('is-ok', cb.checked);
        row.classList.toggle('is-fail', !cb.checked);
    });

    if (hasFailed && allOkInput.value !== '1') {
        forceSection.style.display = 'block';
    } else {
        forceSection.style.display = 'none';
    }
}

checkItems.forEach(cb => cb.addEventListener('change', updateForceSection));

document.getElementById('check-all-btn').addEventListener('click', function() {
    checkItems.forEach(cb => { cb.checked = true; });
    allOkInput.value = '1';
    forceSection.style.display = 'none';
    checkItems.forEach(cb => {
        cb.closest('.prep-check-row').classList.add('is-ok');
        cb.closest('.prep-check-row').classList.remove('is-fail');
    });
});

document.getElementById('prep-form').addEventListener('submit', function(e) {
    const total = checkItems.length;
    const checked = document.querySelectorAll('.check-item:checked').length;
    const hasFailed = checked < total && allOkInput.value !== '1';

    if (hasFailed && canForce) {
        const reason = document.getElementById('forced_reason');
        if (reason && !reason.value.trim()) {
            e.preventDefault();
            reason.focus();
            reason.style.borderColor = 'var(--danger)';
        }
    } else if (hasFailed && !canForce) {
        e.preventDefault();
        alert('Alle checks skal godkendes før pageren kan sættes på lager.');
    }
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
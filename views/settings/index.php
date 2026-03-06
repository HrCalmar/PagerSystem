<?php
// views/settings/index.php
use App\Core\CSRF;
$title = 'Indstillinger';
ob_start();

$settingsMap = [];
foreach ($settings as $s) $settingsMap[$s['key']] = $s;

$eventLabels = [
    'return'   => 'Returnering',
    'broken'   => 'Defekt',
    'repaired' => 'Reparation afsluttet',
];
$statusLabels = [
    'in_stock'        => 'På lager',
    'for_preparation' => 'Til klargøring',
    'in_repair'       => 'Til reparation',
    'defect'          => 'Defekt',
    'issued'          => 'Udleveret',
    'reserved'        => 'Reserveret',
];
$groupedTransitions = [];
foreach ($transitions as $t) $groupedTransitions[$t['event']][] = $t;
?>

<div class="page-header">
    <h1><i class="fas fa-cog"></i> Indstillinger</h1>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle alert-icon"></i>
        <div class="alert-content">Indstillinger gemt</div>
    </div>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle alert-icon"></i>
        <div class="alert-content"><?= htmlspecialchars($_GET['error']) ?></div>
    </div>
<?php endif; ?>

<!-- GENERELLE INDSTILLINGER -->
<div class="card">
    <h2><i class="fas fa-sliders-h"></i> Generelt</h2>
    <form method="POST" action="/settings/update">
        <?= CSRF::field() ?>
        <?php foreach ($settings as $s): ?>
            <div class="form-group">
                <label><strong><?= htmlspecialchars($s['label']) ?></strong></label>
                <?php if ($s['description']): ?>
                    <small class="form-help"><?= htmlspecialchars($s['description']) ?></small>
                <?php endif; ?>
                <?php if ($s['type'] === 'boolean'): ?>
                    <label class="toggle-label">
                        <input type="checkbox" name="<?= $s['key'] ?>" <?= $s['value'] === '1' ? 'checked' : '' ?>>
                        <span class="toggle-track"></span>
                        Aktiveret
                    </label>
                <?php elseif ($s['key'] === 'preparation_force_min_role'): ?>
                    <select name="<?= $s['key'] ?>">
                        <option value="admin"        <?= $s['value'] === 'admin'        ? 'selected' : '' ?>>Administrator</option>
                        <option value="global_read"  <?= $s['value'] === 'global_read'  ? 'selected' : '' ?>>Global læser</option>
                        <option value="station_read" <?= $s['value'] === 'station_read' ? 'selected' : '' ?>>Station læser</option>
                    </select>
                <?php else: ?>
                    <input type="text" name="<?= $s['key'] ?>" value="<?= htmlspecialchars($s['value'] ?? '') ?>">
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Gem</button>
        </div>
    </form>
</div>

<!-- WORKFLOW TRANSITIONS -->
<div class="card" id="transitions">
    <h2><i class="fas fa-project-diagram"></i> Workflow</h2>
    <p class="text-muted">Konfigurer hvad der sker i hvert trin. <strong>Standard</strong> er det der præ-vælges. <strong>Aktiv</strong> betyder at det kan vælges.</p>

    <?php foreach ($groupedTransitions as $event => $group): ?>
        <h3 class="settings-section-title"><?= htmlspecialchars($eventLabels[$event] ?? $event) ?></h3>
        <div class="transitions-table">
            <div class="transitions-header">
                <span>Fra status</span>
                <span>Næste status</span>
                <span>Aktiv</span>
                <span>Standard</span>
                <span></span>
            </div>
            <?php foreach ($group as $t): ?>
                <form method="POST" action="/settings/transitions/update" class="transition-row">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                    <span class="transition-from">
                        <?= htmlspecialchars($statusLabels[$t['from_status']] ?? $t['from_status']) ?>
                    </span>
                    <span class="transition-to">
                        <i class="fas fa-arrow-right text-muted"></i>
                        <strong><?= htmlspecialchars($statusLabels[$t['to_status']] ?? $t['to_status']) ?></strong>
                        <small class="text-muted"><?= htmlspecialchars($t['label']) ?></small>
                    </span>
                    <span class="transition-toggle">
                        <label class="toggle-label toggle-small">
                            <input type="checkbox" name="is_enabled" <?= $t['is_enabled'] ? 'checked' : '' ?>>
                            <span class="toggle-track"></span>
                        </label>
                    </span>
                    <span class="transition-toggle">
                        <label class="toggle-label toggle-small">
                            <input type="checkbox" name="is_default" <?= $t['is_default'] ? 'checked' : '' ?>>
                            <span class="toggle-track"></span>
                        </label>
                    </span>
                    <span>
                        <button type="submit" class="btn btn-small"><i class="fas fa-save"></i></button>
                    </span>
                </form>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</div>

<!-- DEFEKT SYMPTOMER -->
<div class="card" id="symptoms">
    <h2><i class="fas fa-bug"></i> Defekt symptomer</h2>
    <div class="settings-list">
        <?php foreach ($symptoms as $sym): ?>
            <form method="POST" action="/settings/symptoms/save" class="settings-list-row">
                <?= CSRF::field() ?>
                <input type="hidden" name="id" value="<?= $sym['id'] ?>">
                <input type="text" name="label" value="<?= htmlspecialchars($sym['label']) ?>" required>
                <input type="number" name="sort_order" value="<?= $sym['sort_order'] ?>" min="0" style="width:70px">
                <label class="toggle-label toggle-small">
                    <input type="checkbox" name="is_enabled" <?= $sym['is_enabled'] ? 'checked' : '' ?>>
                    <span class="toggle-track"></span>
                    Aktiv
                </label>
                <button type="submit" class="btn btn-small"><i class="fas fa-save"></i></button>
            </form>
            <form method="POST" action="/settings/symptoms/delete" onsubmit="return confirm('Slet symptom?')">
                <?= CSRF::field() ?>
                <input type="hidden" name="id" value="<?= $sym['id'] ?>">
                <button type="submit" class="btn btn-small btn-danger"><i class="fas fa-trash"></i></button>
            </form>
        <?php endforeach; ?>
    </div>
    <h4>Tilføj symptom</h4>
    <form method="POST" action="/settings/symptoms/save" class="settings-list-row">
        <?= CSRF::field() ?>
        <input type="text" name="label" placeholder="Nyt symptom" required>
        <input type="number" name="sort_order" value="<?= count($symptoms) + 1 ?>" min="0" style="width:70px">
        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Tilføj</button>
    </form>
</div>

<!-- KLARGØRINGSTJEK -->
<div class="card" id="checks">
    <h2><i class="fas fa-clipboard-check"></i> Klargøringstjek</h2>
    <div class="settings-list">
        <?php foreach ($checks as $check): ?>
            <form method="POST" action="/settings/checks/save" class="settings-list-row">
                <?= CSRF::field() ?>
                <input type="hidden" name="id" value="<?= $check['id'] ?>">
                <input type="text" name="label" value="<?= htmlspecialchars($check['label']) ?>" required>
                <input type="number" name="sort_order" value="<?= $check['sort_order'] ?>" min="0" style="width:70px">
                <label class="toggle-label toggle-small">
                    <input type="checkbox" name="is_enabled" <?= $check['is_enabled'] ? 'checked' : '' ?>>
                    <span class="toggle-track"></span>
                    Aktiv
                </label>
                <button type="submit" class="btn btn-small"><i class="fas fa-save"></i></button>
            </form>
            <form method="POST" action="/settings/checks/delete" onsubmit="return confirm('Slet tjek?')">
                <?= CSRF::field() ?>
                <input type="hidden" name="id" value="<?= $check['id'] ?>">
                <button type="submit" class="btn btn-small btn-danger"><i class="fas fa-trash"></i></button>
            </form>
        <?php endforeach; ?>
    </div>
    <h4>Tilføj tjek</h4>
    <form method="POST" action="/settings/checks/save" class="settings-list-row">
        <?= CSRF::field() ?>
        <input type="text" name="label" placeholder="Nyt tjek" required>
        <input type="number" name="sort_order" value="<?= count($checks) + 1 ?>" min="0" style="width:70px">
        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Tilføj</button>
    </form>
</div>

<style>
.settings-section-title{font-size:.8rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin:1.5rem 0 .5rem;padding-bottom:.25rem;border-bottom:1px solid var(--border)}
.transitions-table{display:flex;flex-direction:column;gap:.25rem;margin-bottom:.5rem}
.transitions-header{display:grid;grid-template-columns:1fr 1.4fr 60px 80px 48px;gap:.75rem;padding:.3rem .75rem;font-size:.75rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em}
.transition-row{display:grid;grid-template-columns:1fr 1.4fr 60px 80px 48px;gap:.75rem;align-items:center;padding:.55rem .75rem;border:1px solid var(--border);border-radius:var(--radius);background:var(--bg)}
.transition-row:hover{background:var(--bg-secondary)}
.transition-from{font-size:.875rem;color:var(--text)}
.transition-to{display:flex;align-items:center;gap:.4rem;font-size:.875rem}
.transition-to small{color:var(--text-muted);font-size:.78rem}
.transition-toggle{display:flex;justify-content:center}
.settings-list{display:flex;flex-direction:column;gap:.35rem;margin-bottom:1.25rem}
.settings-list-row{display:grid;grid-template-columns:1fr 70px auto auto;gap:.5rem;align-items:center}
.settings-list-row input[type="text"]{width:100%}
.toggle-label{display:flex;align-items:center;gap:.5rem;cursor:pointer;white-space:nowrap}
.toggle-label input[type="checkbox"]{display:none}
.toggle-track{width:40px;height:22px;background:#ccc;border-radius:11px;position:relative;transition:background .2s;flex-shrink:0}
.toggle-track::after{content:'';position:absolute;top:3px;left:3px;width:16px;height:16px;background:#fff;border-radius:50%;transition:left .2s}
.toggle-label input:checked+.toggle-track{background:var(--primary)}
.toggle-label input:checked+.toggle-track::after{left:21px}
.toggle-small .toggle-track{width:34px;height:19px}
.toggle-small .toggle-track::after{width:13px;height:13px}
.toggle-small input:checked+.toggle-track::after{left:18px}
</style>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
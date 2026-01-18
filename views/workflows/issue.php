<?php
// views/workflows/issue.php
use App\Core\CSRF;
$title = 'Udlever pager';
ob_start();
?>

<div class="page-header">
    <h1>Udlever pager: <?= htmlspecialchars($pager['serial_number']) ?></h1>
</div>

<div class="card">
    <form method="POST" action="/pagers/<?= $pager['id'] ?>/issue">
        <?= CSRF::field() ?>
        
        <div class="form-group">
            <label for="staff_id">Brandmand *</label>
            <select id="staff_id" name="staff_id" required <?= $preselected ? 'disabled' : '' ?>>
                <option value="">Vælg brandmand</option>
                <?php foreach ($staff as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $preselected == $s['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['name']) ?> (<?= htmlspecialchars($s['employee_number']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($preselected): ?>
                <input type="hidden" name="staff_id" value="<?= $preselected ?>">
                <small>Pager er reserveret til denne brandmand</small>
            <?php endif; ?>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Udlever</button>
            <a href="/pagers/<?= $pager['id'] ?>" class="btn">Annuller</a>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
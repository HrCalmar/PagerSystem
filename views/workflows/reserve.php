<?php
// views/workflows/reserve.php
use App\Core\CSRF;
$title = 'Reserver pager';
ob_start();
?>

<div class="page-header">
    <h1>Reserver pager: <?= htmlspecialchars($pager['serial_number']) ?></h1>
</div>

<div class="card">
    <form method="POST" action="/pagers/<?= $pager['id'] ?>/reserve">
        <?= CSRF::field() ?>
        
        <div class="form-group">
            <label for="staff_id">Brandmand *</label>
            <select id="staff_id" name="staff_id" required>
                <option value="">Vælg brandmand</option>
                <?php foreach ($staff as $s): ?>
                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?> (<?= htmlspecialchars($s['employee_number']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Reserver</button>
            <a href="/pagers/<?= $pager['id'] ?>" class="btn">Annuller</a>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
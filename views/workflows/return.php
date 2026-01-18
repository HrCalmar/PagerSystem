<?php
// views/workflows/return.php
use App\Core\CSRF;
$title = 'Returner pager';
ob_start();
?>

<div class="page-header">
    <h1>Returner pager: <?= htmlspecialchars($pager['serial_number']) ?></h1>
</div>

<div class="card">
    <form method="POST" action="/pagers/<?= $pager['id'] ?>/return">
        <?= CSRF::field() ?>
        
        <div class="form-group">
            <label for="reason">Årsag</label>
            <textarea id="reason" name="reason" rows="3"></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Returner</button>
            <a href="/pagers/<?= $pager['id'] ?>" class="btn">Annuller</a>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
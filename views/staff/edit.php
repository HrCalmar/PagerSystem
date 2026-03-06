<?php
// views/staff/edit.php
use App\Core\CSRF;
$title = 'Rediger brandmand';
ob_start();
?>

<div class="page-header">
    <h1><i class="fas fa-user-edit"></i> Rediger brandmand</h1>
    <a href="/staff/<?= $staff['id'] ?>" class="btn"><i class="fas fa-arrow-left"></i> Tilbage</a>
</div>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle alert-icon"></i>
        <div class="alert-content"><?= htmlspecialchars($_GET['error']) ?></div>
    </div>
<?php endif; ?>

<div class="card">
    <form method="POST" action="/staff/<?= $staff['id'] ?>/update">
        <?= CSRF::field() ?>

        <div class="form-group">
            <label for="name"><i class="fas fa-user"></i> Navn *</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($staff['name']) ?>" required>
        </div>

        <div class="form-group">
            <label for="employee_number"><i class="fas fa-id-card"></i> Lønnummer *</label>
            <input type="text" id="employee_number" name="employee_number"
                   value="<?= htmlspecialchars($staff['employee_number']) ?>" required>
        </div>

        <div class="form-group">
            <label for="ric_code"><i class="fas fa-broadcast-tower"></i> RIC kode</label>
            <input type="text" id="ric_code" name="ric_code" inputmode="numeric" pattern="[0-9]*"
                   value="<?= htmlspecialchars($staff['ric_code'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="odin_id"><i class="fas fa-fingerprint"></i> ODIN ID</label>
            <input type="text" id="odin_id" name="odin_id" inputmode="numeric" pattern="[0-9]*"
                   value="<?= htmlspecialchars($staff['odin_id'] ?? '') ?>">
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Gem</button>
            <a href="/staff/<?= $staff['id'] ?>" class="btn"><i class="fas fa-times"></i> Annuller</a>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
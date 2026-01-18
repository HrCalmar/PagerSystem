<?php
// views/staff/edit.php - tilføj fejlvisning
use App\Core\CSRF;
$title = 'Rediger brandmand';
ob_start();
?>

<div class="page-header">
    <h1><i class="fas fa-user-edit"></i> Rediger brandmand</h1>
</div>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i>
        <?= htmlspecialchars($_GET['error']) ?>
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
            <input type="text" id="employee_number" name="employee_number" value="<?= htmlspecialchars($staff['employee_number']) ?>" required>
            <small class="form-help">Må kun indeholde tal og bogstaver</small>
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
<?php
// views/staff/create.php - tilføj fejlvisning
use App\Core\CSRF;
$title = 'Opret brandmand';
ob_start();
?>

<div class="page-header">
    <h1><i class="fas fa-user-plus"></i> Opret brandmand</h1>
</div>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i>
        <?= htmlspecialchars($_GET['error']) ?>
    </div>
<?php endif; ?>

<div class="card">
    <form method="POST" action="/staff">
        <?= CSRF::field() ?>
        
        <div class="form-group">
            <label for="name"><i class="fas fa-user"></i> Navn *</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($_GET['name'] ?? '') ?>" required autofocus>
        </div>
        
        <div class="form-group">
            <label for="employee_number"><i class="fas fa-id-card"></i> Lønnummer *</label>
            <input type="text" id="employee_number" name="employee_number" value="<?= htmlspecialchars($_GET['employee_number'] ?? '') ?>" required>
            <small class="form-help">Må kun indeholde tal og bogstaver</small>
        </div>
        
        <div class="form-group">
            <label><i class="fas fa-building"></i> Stationer</label>
            <?php if (empty($stations)): ?>
                <p class="text-muted">Ingen stationer tilgængelige</p>
            <?php else: ?>
                <?php foreach ($stations as $station): ?>
                    <div class="checkbox">
                        <input type="checkbox" name="station_ids[]" value="<?= $station['id'] ?>" id="station_<?= $station['id'] ?>">
                        <label for="station_<?= $station['id'] ?>"><?= htmlspecialchars($station['name']) ?></label>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Opret</button>
            <a href="/staff" class="btn"><i class="fas fa-times"></i> Annuller</a>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
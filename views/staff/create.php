<?php
// views/staff/create.php
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
    <form method="POST" action="/staff" id="staff-form">
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
            <label><i class="fas fa-building"></i> Stationer *</label>
            <?php if (empty($stations)): ?>
                <p class="text-muted">Ingen stationer tilgængelige</p>
            <?php else: ?>
                <div id="stations-container">
                    <?php foreach ($stations as $station): ?>
                        <div class="checkbox">
                            <input type="checkbox" name="station_ids[]" value="<?= $station['id'] ?>" id="station_<?= $station['id'] ?>" class="station-checkbox">
                            <label for="station_<?= $station['id'] ?>"><?= htmlspecialchars($station['name']) ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <small class="form-help">Vælg mindst én station</small>
                <div id="station-error" class="form-error" style="display:none;color:#991b1b;font-size:13px;margin-top:6px;">
                    <i class="fas fa-exclamation-circle"></i> Du skal vælge mindst én station
                </div>
            <?php endif; ?>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Opret</button>
            <a href="/staff" class="btn"><i class="fas fa-times"></i> Annuller</a>
        </div>
    </form>
</div>

<script>
document.getElementById('staff-form').addEventListener('submit', function(e) {
    const checked = document.querySelectorAll('.station-checkbox:checked').length;
    const errorEl = document.getElementById('station-error');
    
    if (checked === 0) {
        e.preventDefault();
        errorEl.style.display = 'block';
        document.getElementById('stations-container').scrollIntoView({ behavior: 'smooth', block: 'center' });
        return false;
    }
    errorEl.style.display = 'none';
});

document.querySelectorAll('.station-checkbox').forEach(cb => {
    cb.addEventListener('change', function() {
        if (document.querySelectorAll('.station-checkbox:checked').length > 0) {
            document.getElementById('station-error').style.display = 'none';
        }
    });
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
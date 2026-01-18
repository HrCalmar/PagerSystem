<?php
// views/users/create.php
use App\Core\CSRF;
$title = 'Opret bruger';
ob_start();
?>

<div class="page-header">
    <h1><i class="fas fa-user-plus"></i> Opret bruger</h1>
</div>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i>
        <?= htmlspecialchars($_GET['error']) ?>
    </div>
<?php endif; ?>

<div class="card">
    <form method="POST" action="/users">
        <?= CSRF::field() ?>
        
        <div class="form-group">
            <label for="username"><i class="fas fa-user"></i> Brugernavn *</label>
            <input type="text" id="username" name="username" required autofocus>
            <small class="form-help">Bruges til login</small>
        </div>
        
        <div class="form-group">
            <label for="password"><i class="fas fa-lock"></i> Password *</label>
            <input type="password" id="password" name="password" required minlength="8">
            <small class="form-help">Minimum 8 tegn</small>
        </div>
        
        <div class="form-group">
            <label for="name"><i class="fas fa-id-card"></i> Fulde navn *</label>
            <input type="text" id="name" name="name" required>
        </div>
        
        <div class="form-group">
            <label for="role"><i class="fas fa-user-tag"></i> Rolle *</label>
            <select id="role" name="role" required onchange="toggleStationField(this.value)">
                <option value="">Vælg rolle</option>
                <option value="admin">Administrator - Fuld adgang</option>
                <option value="global_read">Global læser - Kan se alt, ingen redigering</option>
                <option value="station_read">Station læser - Kun egen station</option>
            </select>
        </div>
        
        <div class="form-group" id="station-field" style="display: none;">
            <label for="station_id"><i class="fas fa-building"></i> Station *</label>
            <select id="station_id" name="station_id">
                <option value="">Vælg station</option>
                <?php foreach ($stations as $station): ?>
                    <option value="<?= $station['id'] ?>"><?= htmlspecialchars($station['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Opret bruger</button>
            <a href="/users" class="btn"><i class="fas fa-times"></i> Annuller</a>
        </div>
    </form>
</div>

<script>
function toggleStationField(role) {
    const stationField = document.getElementById('station-field');
    const stationSelect = document.getElementById('station_id');
    if (role === 'station_read') {
        stationField.style.display = 'block';
        stationSelect.required = true;
    } else {
        stationField.style.display = 'none';
        stationSelect.required = false;
        stationSelect.value = '';
    }
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
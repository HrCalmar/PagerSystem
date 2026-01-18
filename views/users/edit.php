<?php
// views/users/edit.php
use App\Core\CSRF;
$title = 'Rediger bruger';
ob_start();
?>

<div class="page-header">
    <h1><i class="fas fa-user-edit"></i> Rediger bruger</h1>
</div>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i>
        <?= htmlspecialchars($_GET['error']) ?>
    </div>
<?php endif; ?>

<div class="grid-2col">
    <div class="card">
        <h2>Brugeroplysninger</h2>
        <form method="POST" action="/users/<?= $user['id'] ?>/update">
            <?= CSRF::field() ?>
            
            <div class="form-group">
                <label for="username"><i class="fas fa-user"></i> Brugernavn</label>
                <input type="text" id="username" value="<?= htmlspecialchars($user['username']) ?>" disabled>
                <small class="form-help">Brugernavn kan ikke ændres</small>
            </div>
            
            <div class="form-group">
                <label for="name"><i class="fas fa-id-card"></i> Fulde navn *</label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="role"><i class="fas fa-user-tag"></i> Rolle *</label>
                <select id="role" name="role" required onchange="toggleStationField(this.value)">
                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Administrator</option>
                    <option value="global_read" <?= $user['role'] === 'global_read' ? 'selected' : '' ?>>Global læser</option>
                    <option value="station_read" <?= $user['role'] === 'station_read' ? 'selected' : '' ?>>Station læser</option>
                </select>
            </div>
            
            <div class="form-group" id="station-field" style="display: <?= $user['role'] === 'station_read' ? 'block' : 'none' ?>;">
                <label for="station_id"><i class="fas fa-building"></i> Station *</label>
                <select id="station_id" name="station_id" <?= $user['role'] === 'station_read' ? 'required' : '' ?>>
                    <option value="">Vælg station</option>
                    <?php foreach ($stations as $station): ?>
                        <option value="<?= $station['id'] ?>" <?= $user['station_id'] == $station['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($station['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="status"><i class="fas fa-toggle-on"></i> Status *</label>
                <select id="status" name="status" required>
                    <option value="active" <?= $user['status'] === 'active' ? 'selected' : '' ?>>Aktiv</option>
                    <option value="inactive" <?= $user['status'] === 'inactive' ? 'selected' : '' ?>>Inaktiv</option>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Gem ændringer</button>
                <a href="/users" class="btn"><i class="fas fa-times"></i> Annuller</a>
            </div>
        </form>
    </div>
    
    <div class="card">
        <h2>Nulstil password</h2>
        <form method="POST" action="/users/<?= $user['id'] ?>/reset-password" onsubmit="return confirm('Nulstil password for denne bruger?')">
            <?= CSRF::field() ?>
            
            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Nyt password *</label>
                <input type="password" id="password" name="password" required minlength="8">
                <small class="form-help">Minimum 8 tegn</small>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-key"></i> Nulstil password
                </button>
            </div>
        </form>
        
        <div style="margin-top: 24px; padding-top: 24px; border-top: 1px solid var(--gray-200);">
            <h3>Brugerinfo</h3>
            <dl>
                <dt>Oprettet</dt>
                <dd><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></dd>
                
                <dt>Sidst opdateret</dt>
                <dd><?= date('d/m/Y H:i', strtotime($user['updated_at'])) ?></dd>
                
                <dt>Sidst logget ind</dt>
                <dd><?= $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : 'Aldrig' ?></dd>
            </dl>
        </div>
    </div>
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
<?php
// views/stations/create.php
use App\Core\CSRF;
$title = 'Opret station';
ob_start();
?>

<div class="page-header">
    <h1><i class="fas fa-building"></i> Opret station</h1>
</div>

<div class="card">
    <form method="POST" action="/stations">
        <?= CSRF::field() ?>
        
        <div class="form-group">
            <label for="name">Navn *</label>
            <input type="text" id="name" name="name" required>
        </div>
        
        <div class="form-group">
            <label for="code">Kode</label>
            <input type="text" id="code" name="code">
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Opret</button>
            <a href="/stations" class="btn"><i class="fas fa-times"></i> Annuller</a>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
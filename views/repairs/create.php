<?php
// views/repairs/create.php - sæt også default til i dag her
use App\Core\CSRF;
$title = 'Opret reparation';
ob_start();
?>

<div class="page-header">
    <h1><i class="fas fa-wrench"></i> Opret reparation</h1>
</div>

<div class="card">
    <form method="POST" action="/pagers/<?= $pagerId ?>/repairs">
        <?= CSRF::field() ?>
        
        <div class="form-group">
            <label for="repair_date"><i class="fas fa-calendar"></i> Reparationsdato *</label>
            <input type="date" id="repair_date" name="repair_date" value="<?= date('Y-m-d') ?>" required>
        </div>
        
        <div class="form-group">
            <label for="vendor"><i class="fas fa-building"></i> Leverandør</label>
            <input type="text" id="vendor" name="vendor" placeholder="Fx Motorola Service Center">
        </div>
        
        <div class="form-group">
            <label for="description"><i class="fas fa-file-alt"></i> Beskrivelse</label>
            <textarea id="description" name="description" rows="4" placeholder="Beskriv fejlen og reparationen..."></textarea>
        </div>
        
        <div class="form-group">
            <label for="cost"><i class="fas fa-money-bill"></i> Omkostning (kr.)</label>
            <input type="number" id="cost" name="cost" step="0.01" placeholder="0.00">
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Opret og sæt til reparation
            </button>
            <a href="/pagers/<?= $pagerId ?>" class="btn">
                <i class="fas fa-times"></i> Annuller
            </a>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
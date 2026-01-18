<?php
use App\Core\CSRF;
$title = 'Rediger kompetence';
ob_start();
?>

<div class="page-header">
    <h1><i class="fas fa-certificate"></i> Rediger kompetence</h1>
</div>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle alert-icon"></i>
        <div class="alert-content"><?= htmlspecialchars($_GET['error']) ?></div>
    </div>
<?php endif; ?>

<div class="card">
    <form method="POST" action="/competencies/<?= $competency['id'] ?>/update">
        <?= CSRF::field() ?>
        
        <div class="form-group">
            <label for="name"><i class="fas fa-tag"></i> Navn *</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($competency['name']) ?>" required>
        </div>
        
        <div class="form-group">
            <label for="description"><i class="fas fa-file-alt"></i> Beskrivelse</label>
            <textarea id="description" name="description" rows="3"><?= htmlspecialchars($competency['description'] ?? '') ?></textarea>
        </div>
        
        <div class="form-group">
            <div class="checkbox">
                <input type="checkbox" id="requires_renewal" name="requires_renewal" value="1" <?= $competency['requires_renewal'] ? 'checked' : '' ?>>
                <label for="requires_renewal"><i class="fas fa-sync"></i> Kræver periodisk fornyelse</label>
            </div>
            <small class="form-help">Marker hvis kompetencen skal fornyes med jævne mellemrum</small>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Gem</button>
            <a href="/competencies/<?= $competency['id'] ?>" class="btn"><i class="fas fa-times"></i> Annuller</a>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>

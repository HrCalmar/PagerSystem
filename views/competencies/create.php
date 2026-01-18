<?php
use App\Core\CSRF;
$title = 'Opret kompetence';
ob_start();
?>

<div class="page-header">
    <h1><i class="fas fa-certificate"></i> Opret kompetence</h1>
</div>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle alert-icon"></i>
        <div class="alert-content"><?= htmlspecialchars($_GET['error']) ?></div>
    </div>
<?php endif; ?>

<div class="card">
    <form method="POST" action="/competencies">
        <?= CSRF::field() ?>
        
        <div class="form-group">
            <label for="name"><i class="fas fa-tag"></i> Navn *</label>
            <input type="text" id="name" name="name" required autofocus placeholder="F.eks. Røgdykker">
        </div>
        
        <div class="form-group">
            <label for="description"><i class="fas fa-file-alt"></i> Beskrivelse</label>
            <textarea id="description" name="description" rows="3" placeholder="Valgfri beskrivelse af kompetencen..."></textarea>
        </div>
        
        <div class="form-group">
            <div class="checkbox">
                <input type="checkbox" id="requires_renewal" name="requires_renewal" value="1">
                <label for="requires_renewal"><i class="fas fa-sync"></i> Kræver periodisk fornyelse</label>
            </div>
            <small class="form-help">Marker hvis kompetencen skal fornyes med jævne mellemrum (f.eks. årligt)</small>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Opret</button>
            <a href="/competencies" class="btn"><i class="fas fa-times"></i> Annuller</a>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>

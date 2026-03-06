<?php
// views/stations/edit.php
use App\Core\CSRF;
$title = 'Rediger station';
ob_start();
?>

<div class="page-header">
    <h1><i class="fas fa-building"></i> Rediger station</h1>
    <a href="/stations/<?= $station['id'] ?>" class="btn"><i class="fas fa-arrow-left"></i> Tilbage</a>
</div>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle alert-icon"></i>
        <div class="alert-content"><?= htmlspecialchars($_GET['error']) ?></div>
    </div>
<?php endif; ?>

<div class="card">
    <form method="POST" action="/stations/<?= $station['id'] ?>/update">
        <?= CSRF::field() ?>

        <div class="form-group">
            <label for="name"><i class="fas fa-building"></i> Navn *</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($station['name']) ?>" required>
        </div>

        <div class="form-group">
            <label for="code"><i class="fas fa-hashtag"></i> Kode</label>
            <input type="text" id="code" name="code" value="<?= htmlspecialchars($station['code'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="address"><i class="fas fa-map-marker-alt"></i> Adresse</label>
            <input type="text" id="address" name="address" value="<?= htmlspecialchars($station['address'] ?? '') ?>">
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Gem ændringer</button>
            <a href="/stations/<?= $station['id'] ?>" class="btn"><i class="fas fa-times"></i> Annuller</a>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
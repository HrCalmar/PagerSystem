<?php
// views/pagers/edit.php
use App\Core\CSRF;
$title = 'Rediger pager';
ob_start();
?>

<div class="page-header">
    <h1><i class="fas fa-pager"></i> Rediger pager</h1>
</div>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle alert-icon"></i>
        <div class="alert-content">
            <?= htmlspecialchars($_GET['error']) ?>
        </div>
    </div>
<?php endif; ?>

<div class="card">
    <form method="POST" action="/pagers/<?= $pager['id'] ?>/update" enctype="multipart/form-data">
        <?= CSRF::field() ?>

        <div class="form-group">
            <label for="serial_number"><i class="fas fa-barcode"></i> Serienummer *</label>
            <input type="text" id="serial_number" name="serial_number" value="<?= htmlspecialchars($pager['serial_number']) ?>" required>
        </div>

        <div class="form-group">
            <label for="article_number"><i class="fas fa-tag"></i> Artikelnummer</label>
            <input type="text" id="article_number" name="article_number" value="<?= htmlspecialchars($pager['article_number'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="purchase_date"><i class="fas fa-calendar"></i> Indkøbsdato</label>
            <input type="date" id="purchase_date" name="purchase_date" value="<?= $pager['purchase_date'] ?>">
            <small class="form-help">Valgfrit - lad stå tom hvis ukendt</small>
        </div>

        <div class="form-group">
            <label for="programming_file"><i class="fas fa-file-code"></i> Programmeringsfil</label>
            <?php if ($pager['programming_file_path']): ?>
                <div style="margin-bottom: 8px;">
                    <a href="/uploads/programming/<?= htmlspecialchars($pager['programming_file_path']) ?>" class="btn btn-small" download>
                        <i class="fas fa-download"></i> Download nuværende fil
                    </a>
                    <span class="text-muted text-sm"> (upload ny fil for at erstatte)</span>
                </div>
            <?php endif; ?>
            <input type="file" id="programming_file" name="programming_file" accept=".bin,.cps,.rcp,.dat,.hex,.s19,.srec,.mdf">
            <small class="form-help">BIN, CPS, RCP, DAT, HEX, S19, SREC eller MDF (maks. 10 MB) — lad stå tom for at beholde eksisterende</small>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Gem</button>
            <a href="/pagers/<?= $pager['id'] ?>" class="btn"><i class="fas fa-times"></i> Annuller</a>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>

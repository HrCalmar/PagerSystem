<?php
// views/pagers/create.php
use App\Core\CSRF;
$title = 'Opret pager';
ob_start();
?>

<div class="page-header">
    <h1><i class="fas fa-pager"></i> Opret pager</h1>
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
    <form method="POST" action="/pagers">
        <?= CSRF::field() ?>
        
        <div class="form-group">
            <label for="serial_number"><i class="fas fa-barcode"></i> Serienummer *</label>
            <div class="input-with-scanner">
                <input type="text" id="serial_number" name="serial_number" value="<?= htmlspecialchars($_GET['serial_number'] ?? '') ?>" required autofocus>
                <button type="button" class="btn-scanner" onclick="startScanner('serial_number')">
                    <i class="fas fa-camera"></i> Scan
                </button>
            </div>
            <small class="form-help">Unikt serienummer for pageren</small>
        </div>
        
        <div class="form-group">
            <label for="article_number"><i class="fas fa-tag"></i> Artikelnummer</label>
            <div class="input-with-scanner">
                <input type="text" id="article_number" name="article_number" value="<?= htmlspecialchars($_GET['article_number'] ?? '') ?>">
                <button type="button" class="btn-scanner" onclick="startScanner('article_number')">
                    <i class="fas fa-camera"></i> Scan
                </button>
            </div>
            <small class="form-help">Kan være ens for samme model</small>
        </div>
        
        <div class="form-group">
            <label for="purchase_date"><i class="fas fa-calendar"></i> Indkøbsdato</label>
            <input type="date" id="purchase_date" name="purchase_date" value="<?= $_GET['purchase_date'] ?? date('Y-m-d') ?>">
            <small class="form-help">Standard er i dag - slet hvis ukendt</small>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Opret</button>
            <a href="/pagers" class="btn"><i class="fas fa-times"></i> Annuller</a>
        </div>
    </form>
</div>

<!-- Scanner modal -->
<div id="scanner-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-camera"></i> Scan barcode</h3>
            <button type="button" class="btn-close" onclick="stopScanner()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="scanner-container"></div>
        <div class="modal-footer">
            <button type="button" class="btn" onclick="stopScanner()">Annuller</button>
        </div>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
let html5QrCode = null;
let targetField = null;

function startScanner(fieldId) {
    targetField = fieldId;
    document.getElementById('scanner-modal').classList.add('active');
    
    html5QrCode = new Html5Qrcode("scanner-container");
    html5QrCode.start(
        { facingMode: "environment" },
        { fps: 10, qrbox: { width: 250, height: 250 } },
        (decodedText) => {
            document.getElementById(targetField).value = decodedText;
            stopScanner();
        }
    ).catch(err => {
        console.error("Scanner error:", err);
        alert("Kunne ikke starte scanner. Tjek kamera-tilladelser.");
        stopScanner();
    });
}

function stopScanner() {
    if (html5QrCode) {
        html5QrCode.stop().then(() => {
            html5QrCode.clear();
            html5QrCode = null;
        }).catch(err => console.error(err));
    }
    document.getElementById('scanner-modal').classList.remove('active');
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>

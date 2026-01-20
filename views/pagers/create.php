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
            <label for="barcode_scan"><i class="fas fa-qrcode"></i> Scan Data Matrix</label>
            <div class="input-with-scanner">
                <input type="text" id="barcode_scan" placeholder="Scan eller indtast Data Matrix kode..." autofocus>
                <button type="button" class="btn-scanner" onclick="startScanner('barcode_scan')">
                    <i class="fas fa-camera"></i> Kamera
                </button>
            </div>
            <small class="form-help">Scanner udfylder automatisk serienummer og artikelnummer</small>
        </div>
        
        <hr style="margin: 24px 0; border: none; border-top: 1px solid var(--gray-200);">
        
        <div class="form-group">
            <label for="serial_number"><i class="fas fa-barcode"></i> Serienummer *</label>
            <input type="text" id="serial_number" name="serial_number" value="<?= htmlspecialchars($_GET['serial_number'] ?? '') ?>" required>
        </div>
        
        <div class="form-group">
            <label for="article_number"><i class="fas fa-tag"></i> Artikelnummer</label>
            <input type="text" id="article_number" name="article_number" value="<?= htmlspecialchars($_GET['article_number'] ?? '') ?>">
        </div>
        
        <div class="form-group">
            <label for="purchase_date"><i class="fas fa-calendar"></i> Indkøbsdato</label>
            <input type="date" id="purchase_date" name="purchase_date" value="<?= $_GET['purchase_date'] ?? '' ?>">
            <small class="form-help">Valgfrit</small>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Opret</button>
            <a href="/pagers" class="btn"><i class="fas fa-times"></i> Annuller</a>
        </div>
    </form>
</div>

<div id="scanner-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-camera"></i> Scan barcode</h3>
            <button type="button" class="btn-close" onclick="stopScanner()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="scanner-controls">
            <button type="button" class="btn btn-small" id="btn-flip" onclick="toggleMirror()">
                <i class="fas fa-arrows-alt-h"></i> Spejlvend
            </button>
            <button type="button" class="btn btn-small" id="btn-switch" onclick="switchCamera()">
                <i class="fas fa-sync-alt"></i> Skift kamera
            </button>
        </div>
        <div id="scanner-container"></div>
        <div class="modal-footer">
            <button type="button" class="btn" onclick="stopScanner()">Annuller</button>
        </div>
    </div>
</div>

<style>
.scanner-controls {
    display: flex;
    gap: 8px;
    padding: 12px 16px;
    background: var(--gray-100);
    border-bottom: 1px solid var(--gray-200);
}

#scanner-container {
    padding: 16px;
}

#scanner-container video {
    border-radius: var(--radius-md);
}

.scanner-mirrored #scanner-container video {
    transform: scaleX(-1);
}
</style>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
let html5QrCode = null;
let targetField = null;
let isMirrored = false;
let currentFacingMode = "environment";

function parseDataMatrix(scannedValue) {
    const value = scannedValue.trim();
    const parts = value.split(/\s+/);
    
    if (parts.length >= 2) {
        return {
            serial: parts[0],
            article: parts.slice(1).join(' ')
        };
    }
    
    return {
        serial: value,
        article: ''
    };
}

function handleBarcodeScan(value) {
    const parsed = parseDataMatrix(value);
    
    document.getElementById('serial_number').value = parsed.serial;
    document.getElementById('article_number').value = parsed.article;
    document.getElementById('barcode_scan').value = '';
    
    if (parsed.serial) {
        document.getElementById('purchase_date').focus();
    }
}

document.getElementById('barcode_scan').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        handleBarcodeScan(this.value);
    }
});

let scanTimeout = null;
document.getElementById('barcode_scan').addEventListener('input', function(e) {
    clearTimeout(scanTimeout);
    scanTimeout = setTimeout(() => {
        if (this.value.includes(' ') || this.value.length > 10) {
            handleBarcodeScan(this.value);
        }
    }, 100);
});

function toggleMirror() {
    isMirrored = !isMirrored;
    const modal = document.getElementById('scanner-modal');
    
    if (isMirrored) {
        modal.classList.add('scanner-mirrored');
    } else {
        modal.classList.remove('scanner-mirrored');
    }
    
    localStorage.setItem('scanner_mirrored', isMirrored);
}

function switchCamera() {
    if (!html5QrCode) return;
    
    html5QrCode.stop().then(() => {
        currentFacingMode = currentFacingMode === "environment" ? "user" : "environment";
        startCameraWithMode(currentFacingMode);
    }).catch(err => console.error(err));
}

function startCameraWithMode(facingMode) {
    html5QrCode.start(
        { facingMode: facingMode },
        { fps: 10, qrbox: { width: 250, height: 250 } },
        (decodedText) => {
            stopScanner();
            
            if (targetField === 'barcode_scan') {
                handleBarcodeScan(decodedText);
            } else {
                document.getElementById(targetField).value = decodedText;
            }
        }
    ).catch(err => {
        console.error("Scanner error:", err);
        
        if (facingMode === "environment") {
            currentFacingMode = "user";
            startCameraWithMode("user");
        } else {
            alert("Kunne ikke starte scanner. Tjek kamera-tilladelser.");
            stopScanner();
        }
    });
}

function startScanner(fieldId) {
    targetField = fieldId;
    
    isMirrored = localStorage.getItem('scanner_mirrored') === 'true';
    const modal = document.getElementById('scanner-modal');
    modal.classList.add('active');
    
    if (isMirrored) {
        modal.classList.add('scanner-mirrored');
    } else {
        modal.classList.remove('scanner-mirrored');
    }
    
    html5QrCode = new Html5Qrcode("scanner-container");
    currentFacingMode = "environment";
    startCameraWithMode(currentFacingMode);
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
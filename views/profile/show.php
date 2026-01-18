<?php
// views/profile/show.php
use App\Core\{Auth, CSRF};
$title = 'Min profil';
ob_start();

$roleLabels = [
    'admin' => 'Administrator',
    'global_read' => 'Global læser',
    'station_read' => 'Station læser'
];
?>

<div class="page-header">
    <h1><i class="fas fa-user-circle"></i> Min profil</h1>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle alert-icon"></i>
        <div class="alert-content">
            <?php
            $messages = [
                'updated' => 'Dine oplysninger er opdateret',
                'password_changed' => 'Dit password er ændret'
            ];
            echo $messages[$_GET['success']] ?? 'Handling udført';
            ?>
        </div>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle alert-icon"></i>
        <div class="alert-content">
            <?= htmlspecialchars($_GET['error']) ?>
        </div>
    </div>
<?php endif; ?>

<div class="grid-2col">
    <div class="card">
        <h2><i class="fas fa-id-card"></i> Mine oplysninger</h2>
        <form method="POST" action="/profile/update">
            <?= CSRF::field() ?>
            
            <div class="form-group">
                <label for="username"><i class="fas fa-user"></i> Brugernavn</label>
                <input type="text" id="username" value="<?= htmlspecialchars($user['username']) ?>" disabled>
                <small class="form-help">Brugernavn kan ikke ændres</small>
            </div>
            
            <div class="form-group">
                <label for="name"><i class="fas fa-signature"></i> Fulde navn *</label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-user-tag"></i> Rolle</label>
                <input type="text" value="<?= $roleLabels[$user['role']] ?? $user['role'] ?>" disabled>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Gem ændringer
                </button>
            </div>
        </form>
    </div>
    
    <div class="card">
        <h2><i class="fas fa-lock"></i> Skift password</h2>
        <form method="POST" action="/profile/password">
            <?= CSRF::field() ?>
            
            <div class="form-group">
                <label for="current_password"><i class="fas fa-key"></i> Nuværende password *</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>
            
            <div class="form-group">
                <label for="new_password"><i class="fas fa-lock"></i> Nyt password *</label>
                <input type="password" id="new_password" name="new_password" required minlength="8">
                <small class="form-help">Minimum 8 tegn</small>
            </div>
            
            <div class="form-group">
                <label for="confirm_password"><i class="fas fa-lock"></i> Bekræft nyt password *</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-key"></i> Skift password
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <h2><i class="fas fa-info-circle"></i> Kontoinformation</h2>
    <dl>
        <dt>Oprettet</dt>
        <dd><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></dd>
        
        <dt>Sidst logget ind</dt>
        <dd><?= $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : 'Aldrig' ?></dd>
        
        <dt>Status</dt>
        <dd><?= status_badge($user['status'], 'staff') ?></dd>
    </dl>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>

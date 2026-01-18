<?php
use App\Core\{Auth, CSRF};
$title = $competency['name'];
ob_start();
?>

<div class="page-header">
    <h1><i class="fas fa-certificate"></i> <?= htmlspecialchars($competency['name']) ?></h1>
    <div class="page-actions">
        <?php if (Auth::hasRole('admin')): ?>
            <a href="/competencies/<?= $competency['id'] ?>/edit" class="btn">
                <i class="fas fa-edit"></i> Rediger
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle alert-icon"></i>
        <div class="alert-content">
            <?php
            $messages = [
                'updated' => 'Kompetence opdateret'
            ];
            echo $messages[$_GET['success']] ?? 'Handling udført';
            ?>
        </div>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle alert-icon"></i>
        <div class="alert-content"><?= htmlspecialchars($_GET['error']) ?></div>
    </div>
<?php endif; ?>

<div class="grid-2col">
    <div class="card">
        <h2><i class="fas fa-info-circle"></i> Information</h2>
        <dl>
            <dt>Navn</dt>
            <dd><?= htmlspecialchars($competency['name']) ?></dd>
            
            <dt>Beskrivelse</dt>
            <dd><?= htmlspecialchars($competency['description'] ?? '-') ?></dd>
            
            <dt>Kræver fornyelse</dt>
            <dd>
                <?php if ($competency['requires_renewal']): ?>
                    <span class="badge badge-warning"><i class="fas fa-sync"></i> Ja</span>
                <?php else: ?>
                    <span class="badge badge-success"><i class="fas fa-check"></i> Nej</span>
                <?php endif; ?>
            </dd>
            
            <dt>Oprettet</dt>
            <dd><?= date('d/m/Y H:i', strtotime($competency['created_at'])) ?></dd>
        </dl>
        
        <?php if (Auth::hasRole('admin') && empty($staffWithCompetency)): ?>
            <div class="card-actions">
                <form method="POST" action="/competencies/<?= $competency['id'] ?>/delete" onsubmit="return confirm('Slet denne kompetence?')">
                    <?= CSRF::field() ?>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Slet kompetence
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="card">
        <h2><i class="fas fa-chart-pie"></i> Statistik</h2>
        <?php
        $total = count($staffWithCompetency);
        $expired = 0;
        $expiringSoon = 0;
        $valid = 0;
        
        foreach ($staffWithCompetency as $s) {
            if ($s['expiry_date']) {
                $expiryDate = strtotime($s['expiry_date']);
                $now = time();
                $thirtyDays = strtotime('+30 days');
                
                if ($expiryDate < $now) {
                    $expired++;
                } elseif ($expiryDate <= $thirtyDays) {
                    $expiringSoon++;
                } else {
                    $valid++;
                }
            } else {
                $valid++;
            }
        }
        ?>
        <div class="stats-grid">
            <div class="stat-card">
                <h3>I alt</h3>
                <div class="stat-number"><?= $total ?></div>
            </div>
            <div class="stat-card">
                <h3>Gyldige</h3>
                <div class="stat-number" style="color: var(--success)"><?= $valid ?></div>
            </div>
            <div class="stat-card">
                <h3>Udløber snart</h3>
                <div class="stat-number" style="color: var(--warning)"><?= $expiringSoon ?></div>
            </div>
            <div class="stat-card">
                <h3>Udløbet</h3>
                <div class="stat-number" style="color: var(--danger)"><?= $expired ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <h2><i class="fas fa-users"></i> Brandfolk med denne kompetence</h2>
    
    <?php if (empty($staffWithCompetency)): ?>
        <div class="empty-state-small">
            <i class="fas fa-user-slash"></i>
            <p>Ingen brandfolk har denne kompetence endnu</p>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Navn</th>
                        <th>Lønnummer</th>
                        <th>Stationer</th>
                        <th>Opnået</th>
                        <th>Udløber</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($staffWithCompetency as $s): ?>
                    <?php
                    $statusClass = 'badge-success';
                    $statusText = 'Gyldig';
                    $statusIcon = 'fa-check';
                    
                    if ($s['expiry_date']) {
                        $expiryDate = strtotime($s['expiry_date']);
                        $now = time();
                        $thirtyDays = strtotime('+30 days');
                        
                        if ($expiryDate < $now) {
                            $statusClass = 'badge-danger';
                            $statusText = 'Udløbet';
                            $statusIcon = 'fa-times';
                        } elseif ($expiryDate <= $thirtyDays) {
                            $statusClass = 'badge-warning';
                            $statusText = 'Udløber snart';
                            $statusIcon = 'fa-clock';
                        }
                    }
                    ?>
                    <tr>
                        <td>
                            <a href="/staff/<?= $s['id'] ?>" class="text-link">
                                <i class="fas fa-user"></i> <?= htmlspecialchars($s['name']) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($s['employee_number']) ?></td>
                        <td><?= htmlspecialchars($s['stations'] ?? '-') ?></td>
                        <td><?= $s['obtained_date'] ? date('d/m/Y', strtotime($s['obtained_date'])) : '-' ?></td>
                        <td><?= $s['expiry_date'] ? date('d/m/Y', strtotime($s['expiry_date'])) : '-' ?></td>
                        <td>
                            <span class="badge <?= $statusClass ?>">
                                <i class="fas <?= $statusIcon ?>"></i> <?= $statusText ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>

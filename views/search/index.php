<?php
use App\Core\Auth;
$title = 'Søgeresultater';
$query = $_GET['q'] ?? '';
$totalResults = count($results['pagers']) + count($results['staff']) + count($results['stations']);
ob_start();
?>

<div class="page-header">
    <h1><i class="fas fa-search"></i> Søgeresultater</h1>
</div>

<div class="search-page-form">
    <form method="GET" action="/search" class="search-form-large">
        <div class="search-input-wrapper">
            <i class="fas fa-search"></i>
            <input type="text" name="q" value="<?= htmlspecialchars($query) ?>" placeholder="Søg på serienummer, telefon, navn, lønnummer..." autofocus>
            <button type="submit" class="btn btn-primary">Søg</button>
        </div>
    </form>
</div>

<?php if ($query && strlen($query) >= 2): ?>
    <div class="search-summary">
        <p>Fandt <strong><?= $totalResults ?></strong> resultater for "<?= htmlspecialchars($query) ?>"</p>
    </div>
    
    <?php if (!empty($results['pagers'])): ?>
    <div class="card">
        <h2><i class="fas fa-pager"></i> Pagere (<?= count($results['pagers']) ?>)</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Serienummer</th>
                        <th>Artikelnummer</th>
                        <th>Telefon</th>
                        <th>Status</th>
                        <th>Udleveret til</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results['pagers'] as $p): ?>
                    <tr>
                        <td class="font-medium"><?= htmlspecialchars($p['serial_number']) ?></td>
                        <td><?= htmlspecialchars($p['article_number'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($p['phone_number'] ?? '-') ?></td>
                        <td><?= status_badge($p['status'], 'pager') ?></td>
                        <td><?= htmlspecialchars($p['staff_name'] ?? '-') ?></td>
                        <td>
                            <a href="/pagers/<?= $p['id'] ?>" class="btn btn-small">
                                <i class="fas fa-eye"></i> Vis
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($results['staff'])): ?>
    <div class="card">
        <h2><i class="fas fa-users"></i> Brandfolk (<?= count($results['staff']) ?>)</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Navn</th>
                        <th>Lønnummer</th>
                        <th>Stationer</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results['staff'] as $s): ?>
                    <tr>
                        <td class="font-medium"><?= htmlspecialchars($s['name']) ?></td>
                        <td><?= htmlspecialchars($s['employee_number']) ?></td>
                        <td><?= htmlspecialchars($s['stations'] ?? '-') ?></td>
                        <td><?= status_badge($s['status'], 'staff') ?></td>
                        <td>
                            <a href="/staff/<?= $s['id'] ?>" class="btn btn-small">
                                <i class="fas fa-eye"></i> Vis
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($results['stations'])): ?>
    <div class="card">
        <h2><i class="fas fa-building"></i> Stationer (<?= count($results['stations']) ?>)</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Navn</th>
                        <th>Kode</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results['stations'] as $st): ?>
                    <tr>
                        <td class="font-medium"><?= htmlspecialchars($st['name']) ?></td>
                        <td><?= htmlspecialchars($st['code'] ?? '-') ?></td>
                        <td>
                            <a href="/stations/<?= $st['id'] ?>" class="btn btn-small">
                                <i class="fas fa-eye"></i> Vis
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($totalResults === 0): ?>
    <div class="card">
        <div class="empty-state">
            <i class="fas fa-search"></i>
            <p>Ingen resultater fundet for "<?= htmlspecialchars($query) ?>"</p>
            <small class="text-muted">Prøv et andet søgeord eller tjek stavningen</small>
        </div>
    </div>
    <?php endif; ?>

<?php elseif ($query): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i>
        Indtast mindst 2 tegn for at søge
    </div>
<?php else: ?>
    <div class="card">
        <div class="empty-state">
            <i class="fas fa-search"></i>
            <p>Indtast et søgeord ovenfor</p>
            <small class="text-muted">Søg på serienummer, telefonnummer, navn, lønnummer eller stationsnavn</small>
        </div>
    </div>
<?php endif; ?>

<style>
.search-page-form {
    margin-bottom: 24px;
}

.search-form-large {
    background: white;
    padding: 20px;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-md);
}

.search-input-wrapper {
    display: flex;
    align-items: center;
    gap: 12px;
}

.search-input-wrapper i {
    color: var(--gray-400);
    font-size: 20px;
}

.search-input-wrapper input {
    flex: 1;
    font-size: 18px;
    padding: 12px 16px;
}

.search-summary {
    margin-bottom: 20px;
    color: var(--gray-600);
}
</style>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
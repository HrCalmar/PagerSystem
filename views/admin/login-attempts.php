<?php
use App\Core\Auth;
$title = 'Login forsøg';
ob_start();
?>

<div class="page-header">
    <h1><i class="fas fa-shield-alt"></i> Login forsøg</h1>
    <div class="page-actions">
        <a href="/users" class="btn"><i class="fas fa-arrow-left"></i> Tilbage til brugere</a>
    </div>
</div>

<div class="filters">
    <form method="GET" class="filter-form">
        <select name="filter" class="filter-select" onchange="this.form.submit()">
            <option value="" <?= ($filter ?? '') === '' ? 'selected' : '' ?>>Alle forsøg</option>
            <option value="failed"  <?= ($filter ?? '') === 'failed'  ? 'selected' : '' ?>>Kun mislykkede</option>
            <option value="success" <?= ($filter ?? '') === 'success' ? 'selected' : '' ?>>Kun vellykkede</option>
        </select>
    </form>
</div>

<div class="card">
    <?php if (empty($attempts)): ?>
        <div class="empty-state">
            <i class="fas fa-shield-alt"></i>
            <p>Ingen login forsøg fundet</p>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Tidspunkt</th>
                        <th>Brugernavn</th>
                        <th>IP-adresse</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attempts as $a): ?>
                    <tr>
                        <td class="text-sm"><?= date('d/m/Y H:i:s', strtotime($a['attempted_at'])) ?></td>
                        <td><?= htmlspecialchars($a['username']) ?></td>
                        <td class="text-sm text-muted"><?= htmlspecialchars($a['ip_address']) ?></td>
                        <td>
                            <?php if ($a['success']): ?>
                                <span class="badge badge-success"><i class="fas fa-check"></i> Logget ind</span>
                            <?php else: ?>
                                <span class="badge badge-danger"><i class="fas fa-times"></i> Mislykket</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php echo paginate_links($page, $total, $perPage, $filter ? ['filter' => $filter] : []); ?>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>

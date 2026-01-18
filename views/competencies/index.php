<?php
use App\Core\Auth;
$title = 'Kompetencer';
ob_start();
?>

<div class="page-header">
    <h1><i class="fas fa-certificate"></i> Kompetencer</h1>
    <div class="page-actions">
        <a href="/competencies/expiring" class="btn">
            <i class="fas fa-clock"></i> Udløbende
        </a>
        <?php if (Auth::hasRole('admin')): ?>
            <a href="/competencies/create" class="btn btn-primary">
                <i class="fas fa-plus"></i> Opret kompetence
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
                'created' => 'Kompetence oprettet',
                'updated' => 'Kompetence opdateret',
                'deleted' => 'Kompetence slettet'
            ];
            echo $messages[$_GET['success']] ?? 'Handling udført';
            ?>
        </div>
    </div>
<?php endif; ?>

<div class="card">
    <?php if (empty($competencies)): ?>
        <div class="empty-state">
            <i class="fas fa-certificate"></i>
            <p>Ingen kompetencer oprettet</p>
            <?php if (Auth::hasRole('admin')): ?>
                <a href="/competencies/create" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Opret kompetence
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Navn</th>
                        <th>Beskrivelse</th>
                        <th>Kræver fornyelse</th>
                        <th>Antal brandfolk</th>
                        <th>Udløbet</th>
                        <th>Udløber snart</th>
                        <th class="text-right">Handlinger</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($competencies as $c): ?>
                    <tr>
                        <td class="font-medium">
                            <a href="/competencies/<?= $c['id'] ?>" class="text-link">
                                <i class="fas fa-certificate"></i> <?= htmlspecialchars($c['name']) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($c['description'] ?? '-') ?></td>
                        <td>
                            <?php if ($c['requires_renewal']): ?>
                                <span class="badge badge-warning"><i class="fas fa-sync"></i> Ja</span>
                            <?php else: ?>
                                <span class="badge badge-success"><i class="fas fa-check"></i> Nej</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($c['staff_count'] > 0): ?>
                                <span class="badge badge-info"><?= $c['staff_count'] ?></span>
                            <?php else: ?>
                                <span class="text-muted">0</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($c['expired_count'] > 0): ?>
                                <span class="badge badge-danger"><i class="fas fa-exclamation-triangle"></i> <?= $c['expired_count'] ?></span>
                            <?php else: ?>
                                <span class="text-muted">0</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($c['expiring_soon_count'] > 0): ?>
                                <span class="badge badge-warning"><i class="fas fa-clock"></i> <?= $c['expiring_soon_count'] ?></span>
                            <?php else: ?>
                                <span class="text-muted">0</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="/competencies/<?= $c['id'] ?>" class="btn-icon" title="Vis detaljer">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if (Auth::hasRole('admin')): ?>
                                    <a href="/competencies/<?= $c['id'] ?>/edit" class="btn-icon" title="Rediger">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
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

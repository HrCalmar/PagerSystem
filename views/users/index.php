<?php
// views/users/index.php
use App\Core\Auth;
$title = 'Brugere';
ob_start();
?>

<div class="page-header">
    <h1><i class="fas fa-users-cog"></i> Brugere</h1>
    <div class="page-actions">
        <a href="/users/create" class="btn btn-primary">
            <i class="fas fa-user-plus"></i> Opret bruger
        </a>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?php
        $messages = [
            'created' => 'Bruger oprettet',
            'updated' => 'Bruger opdateret',
            'password_reset' => 'Password nulstillet'
        ];
        echo $messages[$_GET['success']] ?? 'Handling udført';
        ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Brugernavn</th>
                    <th>Navn</th>
                    <th>Rolle</th>
                    <th>Station</th>
                    <th>Status</th>
                    <th>Sidst logget ind</th>
                    <th class="text-right">Handlinger</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td class="font-medium"><?= htmlspecialchars($user['username']) ?></td>
                    <td><?= htmlspecialchars($user['name']) ?></td>
                    <td>
                        <?php
                        $roleLabels = [
                            'admin' => '<span class="badge badge-danger"><i class="fas fa-crown"></i> Administrator</span>',
                            'global_read' => '<span class="badge badge-info"><i class="fas fa-eye"></i> Global læser</span>',
                            'station_read' => '<span class="badge badge-warning"><i class="fas fa-building"></i> Station læser</span>'
                        ];
                        echo $roleLabels[$user['role']] ?? $user['role'];
                        ?>
                    </td>
                    <td><?= htmlspecialchars($user['station_name'] ?? '-') ?></td>
                    <td><?= status_badge($user['status'], 'staff') ?></td>
                    <td><?= $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : '-' ?></td>
                    <td>
                        <div class="action-buttons">
                            <a href="/users/<?= $user['id'] ?>/edit" class="btn-icon" title="Rediger">
                                <i class="fas fa-edit"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
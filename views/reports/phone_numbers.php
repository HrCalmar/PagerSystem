<?php
// views/reports/phone_numbers.php
use App\Core\Auth;
$title = 'Telefonnumre';
ob_start();
?>
<div class="page-header">
    <h1><i class="fas fa-phone"></i> Telefonnumre</h1>
    <div class="page-actions">
        <a href="/reports/export-phones" class="btn btn-primary">
            <i class="fas fa-download"></i> Eksporter CSV
        </a>
    </div>
</div>
<div class="card">
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Telefonnummer</th>
                    <th>SIM-nummer</th>
                    <th>Serienummer</th>
                    <th>Brandmand</th>
                    <th>Station</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($phones as $phone): ?>
                <tr>
                    <td class="phone-number-cell">
                        <i class="fas fa-phone"></i>
                        <strong><?= htmlspecialchars($phone['phone_number'] ?? '') ?></strong>
                    </td>
                    <td><?= htmlspecialchars($phone['sim_number'] ?? '') ?></td>
                    <td>
                        <?php if (!empty($phone['pager_id'])): ?>
                            <a href="/pagers/<?= (int)$phone['pager_id'] ?>" class="text-link">
                                <?= htmlspecialchars($phone['serial_number'] ?? '') ?>
                            </a>
                        <?php else: ?>
                            <?= htmlspecialchars($phone['serial_number'] ?? '-') ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($phone['staff_id']) && !empty($phone['staff_name'])): ?>
                            <a href="/staff/<?= (int)$phone['staff_id'] ?>" class="text-link">
                                <?= htmlspecialchars($phone['staff_name']) ?>
                            </a>
                        <?php else: ?>
                            <span class="text-muted">Ikke udleveret</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($phone['station_name'] ?? '-') ?></td>
                    <td><?= status_badge($phone['status'] ?? 'unknown', 'pager') ?></td>
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
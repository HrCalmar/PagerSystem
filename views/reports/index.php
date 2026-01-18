<?php
// views/reports/index.php
use App\Core\Auth;
$title = 'Rapporter';
ob_start();

$dayNames = ['Søndag', 'Mandag', 'Tirsdag', 'Onsdag', 'Torsdag', 'Fredag', 'Lørdag'];
$monthNames = ['januar', 'februar', 'marts', 'april', 'maj', 'juni', 'juli', 'august', 'september', 'oktober', 'november', 'december'];
$today = $dayNames[date('w')] . ' d. ' . date('j') . '. ' . $monthNames[date('n')-1] . ' ' . date('Y');
?>

<div class="page-header">
    <div>
        <h1><i class="fas fa-chart-bar"></i> Rapporter</h1>
        <p class="page-subtitle"><?= $today ?></p>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">
            <i class="fas fa-pager"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($stats['total_pagers']) ?></div>
            <div class="stat-label">Pagere i alt</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon green">
            <i class="fas fa-user-check"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($stats['issued_pagers']) ?></div>
            <div class="stat-label">Udleverede</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon cyan">
            <i class="fas fa-warehouse"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($stats['in_stock']) ?></div>
            <div class="stat-label">På lager</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon orange">
            <i class="fas fa-tools"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($stats['in_repair']) ?></div>
            <div class="stat-label">Til reparation</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon purple">
            <i class="fas fa-sim-card"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($stats['active_sims']) ?></div>
            <div class="stat-label">Aktive SIM-kort</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon teal">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($stats['active_staff']) ?></div>
            <div class="stat-label">Aktive brandfolk</div>
        </div>
    </div>
</div>

<?php if ($stats['staff_without_pager'] > 0 || $stats['for_preparation'] > 0 || $stats['defect'] > 0): ?>
<div class="alerts-section">
    <h2><i class="fas fa-exclamation-circle"></i> Kræver opmærksomhed</h2>
    <div class="alert-cards">
        <?php if ($stats['staff_without_pager'] > 0): ?>
        <a href="/reports/missing-pagers" class="alert-card warning">
            <div class="alert-icon">
                <i class="fas fa-user-slash"></i>
            </div>
            <div class="alert-content">
                <div class="alert-value"><?= $stats['staff_without_pager'] ?></div>
                <div class="alert-label">Brandfolk uden pager</div>
            </div>
            <i class="fas fa-chevron-right alert-arrow"></i>
        </a>
        <?php endif; ?>
        
        <?php if ($stats['for_preparation'] > 0): ?>
        <a href="/pagers?status=for_preparation" class="alert-card info">
            <div class="alert-icon">
                <i class="fas fa-clipboard-check"></i>
            </div>
            <div class="alert-content">
                <div class="alert-value"><?= $stats['for_preparation'] ?></div>
                <div class="alert-label">Afventer klargøring</div>
            </div>
            <i class="fas fa-chevron-right alert-arrow"></i>
        </a>
        <?php endif; ?>
        
        <?php if ($stats['defect'] > 0): ?>
        <a href="/pagers?status=defect" class="alert-card danger">
            <div class="alert-icon">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="alert-content">
                <div class="alert-value"><?= $stats['defect'] ?></div>
                <div class="alert-label">Defekte pagere</div>
            </div>
            <i class="fas fa-chevron-right alert-arrow"></i>
        </a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<div class="reports-section">
    <h2><i class="fas fa-file-alt"></i> Tilgængelige rapporter</h2>
    <div class="report-grid">
        <a href="/reports/status-overview" class="report-card">
            <div class="report-icon blue">
                <i class="fas fa-tachometer-alt"></i>
            </div>
            <div class="report-content">
                <h3>Statusoverblik</h3>
                <p>Detaljeret overblik over alle pagere, afvigelser og advarsler</p>
            </div>
            <i class="fas fa-arrow-right report-arrow"></i>
        </a>
        
        <a href="/reports/phone-numbers" class="report-card">
            <div class="report-icon green">
                <i class="fas fa-phone"></i>
            </div>
            <div class="report-content">
                <h3>Telefonnumre</h3>
                <p>Komplet liste over alle aktive telefonnumre med tilknytning</p>
            </div>
            <i class="fas fa-arrow-right report-arrow"></i>
        </a>
        
        <a href="/reports/missing-pagers" class="report-card">
            <div class="report-icon orange">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="report-content">
                <h3>Manglende pagere</h3>
                <p>Aktive brandfolk der mangler en tildelt pager</p>
            </div>
            <i class="fas fa-arrow-right report-arrow"></i>
        </a>
    </div>
</div>

<div class="export-section">
    <h2><i class="fas fa-download"></i> Eksporter data</h2>
    <div class="export-grid">
        <a href="/reports/export-phones" class="export-card">
            <div class="export-icon">
                <i class="fas fa-file-csv"></i>
            </div>
            <div class="export-content">
                <h3>Telefonnumre (CSV)</h3>
                <p>Download alle telefonnumre som CSV-fil til Excel</p>
            </div>
            <span class="export-btn">
                <i class="fas fa-download"></i> Download
            </span>
        </a>
    </div>
</div>

<style>
.page-subtitle {
    color: var(--text-secondary);
    margin-top: 0.25rem;
    font-size: 0.95rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: var(--card-bg);
    border-radius: 12px;
    padding: 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}

.stat-icon.blue { background: rgba(59, 130, 246, 0.15); color: #3b82f6; }
.stat-icon.green { background: rgba(34, 197, 94, 0.15); color: #22c55e; }
.stat-icon.cyan { background: rgba(6, 182, 212, 0.15); color: #06b6d4; }
.stat-icon.orange { background: rgba(249, 115, 22, 0.15); color: #f97316; }
.stat-icon.purple { background: rgba(168, 85, 247, 0.15); color: #a855f7; }
.stat-icon.teal { background: rgba(20, 184, 166, 0.15); color: #14b8a6; }

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1;
}

.stat-label {
    font-size: 0.85rem;
    color: var(--text-secondary);
    margin-top: 0.25rem;
}

.alerts-section,
.reports-section,
.export-section {
    margin-bottom: 2rem;
}

.alerts-section h2,
.reports-section h2,
.export-section h2 {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.alert-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1rem;
}

.alert-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.25rem;
    border-radius: 10px;
    text-decoration: none;
    transition: transform 0.15s, box-shadow 0.15s;
}

.alert-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.alert-card.warning {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.15), rgba(245, 158, 11, 0.05));
    border: 1px solid rgba(245, 158, 11, 0.3);
}
.alert-card.warning .alert-icon { color: #f59e0b; }
.alert-card.warning .alert-value { color: #d97706; }

.alert-card.info {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.15), rgba(59, 130, 246, 0.05));
    border: 1px solid rgba(59, 130, 246, 0.3);
}
.alert-card.info .alert-icon { color: #3b82f6; }
.alert-card.info .alert-value { color: #2563eb; }

.alert-card.danger {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.15), rgba(239, 68, 68, 0.05));
    border: 1px solid rgba(239, 68, 68, 0.3);
}
.alert-card.danger .alert-icon { color: #ef4444; }
.alert-card.danger .alert-value { color: #dc2626; }

.alert-icon {
    font-size: 1.5rem;
}

.alert-content {
    flex: 1;
}

.alert-value {
    font-size: 1.25rem;
    font-weight: 700;
    line-height: 1;
}

.alert-label {
    font-size: 0.85rem;
    color: var(--text-secondary);
    margin-top: 0.25rem;
}

.alert-arrow {
    color: var(--text-tertiary);
}

.report-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 1rem;
}

.report-card {
    background: var(--card-bg);
    border-radius: 12px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    text-decoration: none;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border: 1px solid var(--border-color);
    transition: transform 0.15s, box-shadow 0.15s, border-color 0.15s;
}

.report-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    border-color: var(--primary-color);
}

.report-icon {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.report-icon.blue { background: rgba(59, 130, 246, 0.15); color: #3b82f6; }
.report-icon.green { background: rgba(34, 197, 94, 0.15); color: #22c55e; }
.report-icon.orange { background: rgba(249, 115, 22, 0.15); color: #f97316; }

.report-content {
    flex: 1;
}

.report-content h3 {
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0 0 0.25rem 0;
}

.report-content p {
    font-size: 0.85rem;
    color: var(--text-secondary);
    margin: 0;
    line-height: 1.4;
}

.report-arrow {
    color: var(--text-tertiary);
    transition: transform 0.15s;
}

.report-card:hover .report-arrow {
    transform: translateX(4px);
    color: var(--primary-color);
}

.export-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 1rem;
}

.export-card {
    background: var(--card-bg);
    border-radius: 12px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    text-decoration: none;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border: 1px solid var(--border-color);
    transition: transform 0.15s, box-shadow 0.15s;
}

.export-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.export-icon {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    background: rgba(34, 197, 94, 0.15);
    color: #22c55e;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.export-content {
    flex: 1;
}

.export-content h3 {
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0 0 0.25rem 0;
}

.export-content p {
    font-size: 0.85rem;
    color: var(--text-secondary);
    margin: 0;
}

.export-btn {
    background: var(--primary-color);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: background 0.15s;
}

.export-card:hover .export-btn {
    background: var(--primary-hover);
}

@media (max-width: 640px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .stat-card {
        padding: 1rem;
    }
    
    .stat-icon {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }
    
    .stat-value {
        font-size: 1.25rem;
    }
    
    .report-grid,
    .export-grid,
    .alert-cards {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
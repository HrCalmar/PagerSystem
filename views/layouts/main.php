<?php
use App\Core\Auth;
$user = Auth::user();
$currentPath = $_SERVER['REQUEST_URI'];
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Pager System' ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/app.css?v=1.9">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="/dashboard" class="nav-brand">
                <i class="fas fa-pager"></i> 
                <span>Pager System</span>
            </a>
            
            <button class="nav-toggle" id="navToggle">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="nav-content" id="navContent">
                <div class="nav-search">
                    <form action="/search" method="GET" class="search-form">
                        <input type="search" name="q" placeholder="Søg..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                    </form>
                </div>
                
                <div class="nav-menu">
                    <a href="/dashboard" class="nav-link <?= str_starts_with($currentPath, '/dashboard') ? 'active' : '' ?>">
                        <i class="fas fa-home"></i> <span>Dashboard</span>
                    </a>
                    <a href="/pagers" class="nav-link <?= str_starts_with($currentPath, '/pagers') ? 'active' : '' ?>">
                        <i class="fas fa-pager"></i> <span>Pagere</span>
                    </a>
                    <a href="/staff" class="nav-link <?= str_starts_with($currentPath, '/staff') ? 'active' : '' ?>">
                        <i class="fas fa-users"></i> <span>Brandfolk</span>
                    </a>
                    <a href="/stations" class="nav-link <?= str_starts_with($currentPath, '/stations') ? 'active' : '' ?>">
                        <i class="fas fa-building"></i> <span>Stationer</span>
                    </a>
                    <a href="/competencies" class="nav-link <?= str_starts_with($currentPath, '/competencies') ? 'active' : '' ?>">
                        <i class="fas fa-certificate"></i> <span>Kompetencer</span>
                    </a>
                    <a href="/reports" class="nav-link <?= str_starts_with($currentPath, '/reports') ? 'active' : '' ?>">
                        <i class="fas fa-chart-bar"></i> <span>Rapporter</span>
                    </a>
                    <?php if (Auth::hasRole('admin')): ?>
                        <a href="/users" class="nav-link <?= str_starts_with($currentPath, '/users') ? 'active' : '' ?>">
                            <i class="fas fa-users-cog"></i> <span>Brugere</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="nav-user">
                <div class="user-dropdown">
                    <button class="user-dropdown-btn">
                        <i class="fas fa-user-circle"></i> 
                        <span class="user-name"><?= htmlspecialchars($user['name'] ?? $user['username']) ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="user-dropdown-menu">
                        <a href="/profile" class="dropdown-item">
                            <i class="fas fa-user-edit"></i> Min profil
                        </a>
                        <div class="dropdown-divider"></div>
                        <form method="POST" action="/logout">
                            <?= \App\Core\CSRF::field() ?>
                            <button type="submit" class="dropdown-item logout">
                                <i class="fas fa-sign-out-alt"></i> Log ud
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    
    <main class="container">
        <?= $content ?>
    </main>
    
    <script>
    // User dropdown
    document.querySelector('.user-dropdown-btn')?.addEventListener('click', function(e) {
        e.stopPropagation();
        this.parentElement.classList.toggle('open');
    });
    
    document.addEventListener('click', function() {
        document.querySelector('.user-dropdown')?.classList.remove('open');
    });
    
    // Mobile nav toggle
    const navToggle = document.getElementById('navToggle');
    const navContent = document.getElementById('navContent');
    
    navToggle?.addEventListener('click', function(e) {
        e.stopPropagation();
        navContent.classList.toggle('active');
        this.querySelector('i').classList.toggle('fa-bars');
        this.querySelector('i').classList.toggle('fa-times');
    });
    
    // Close mobile nav when clicking outside
    document.addEventListener('click', function(e) {
        if (!navContent?.contains(e.target) && !navToggle?.contains(e.target)) {
            navContent?.classList.remove('active');
            navToggle?.querySelector('i')?.classList.remove('fa-times');
            navToggle?.querySelector('i')?.classList.add('fa-bars');
        }
    });
    
    // Close mobile nav when clicking a link
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', function() {
            navContent?.classList.remove('active');
            navToggle?.querySelector('i')?.classList.remove('fa-times');
            navToggle?.querySelector('i')?.classList.add('fa-bars');
        });
    });
    </script>
</body>
</html>
<?php
// views/layouts/main.php
use App\Core\Auth;
$user = Auth::user();
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Pager System' ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/app.css?v=1.6">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="/dashboard" class="nav-brand">
                <i class="fas fa-pager"></i> Pager System
            </a>
            <div class="nav-menu">
                <a href="/dashboard" class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'], '/dashboard') ? 'active' : '' ?>">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="/pagers" class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'], '/pagers') ? 'active' : '' ?>">
                    <i class="fas fa-pager"></i> Pagere
                </a>
                <a href="/staff" class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'], '/staff') ? 'active' : '' ?>">
                    <i class="fas fa-users"></i> Brandfolk
                </a>
                <a href="/stations" class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'], '/stations') ? 'active' : '' ?>">
                    <i class="fas fa-building"></i> Stationer
                </a>
                <a href="/competencies" class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'], '/competencies') ? 'active' : '' ?>">
                    <i class="fas fa-certificate"></i> Kompetencer
                </a>
                <a href="/reports" class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'], '/reports') ? 'active' : '' ?>">
                    <i class="fas fa-chart-bar"></i> Rapporter
                </a>
                <?php if (Auth::hasRole('admin')): ?>
                    <a href="/users" class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'], '/users') ? 'active' : '' ?>">
                        <i class="fas fa-users-cog"></i> Brugere
                    </a>
                <?php endif; ?>
            </div>
            <div class="nav-user">
                <div class="user-dropdown">
                    <button class="user-dropdown-btn">
                        <i class="fas fa-user-circle"></i> 
                        <span><?= htmlspecialchars($user['name'] ?? $user['username']) ?></span>
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
    document.querySelector('.user-dropdown-btn')?.addEventListener('click', function(e) {
        e.stopPropagation();
        this.parentElement.classList.toggle('open');
    });
    
    document.addEventListener('click', function() {
        document.querySelector('.user-dropdown')?.classList.remove('open');
    });
    </script>
</body>
</html>

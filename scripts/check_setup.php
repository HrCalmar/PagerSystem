<?php
// scripts/check_setup.php
echo "=== SETUP CHECK ===\n\n";

$root = __DIR__ . '/..';

echo "Root path: $root\n\n";

$files = [
    'autoload.php',
    'config.php',
    '.env',
    'env_loader.php',
    'public/index.php',
    'public/.htaccess',
    'src/Core/Auth.php',
    'src/Core/Session.php',
    'src/Config/Database.php'
];

foreach ($files as $file) {
    $path = "$root/$file";
    $exists = file_exists($path);
    echo ($exists ? '✓' : '✗') . " $file\n";
}

echo "\n=== .ENV CHECK ===\n";
require "$root/autoload.php";
echo "DB_HOST: " . ($_ENV['DB_HOST'] ?? 'NOT SET') . "\n";
echo "DB_NAME: " . ($_ENV['DB_NAME'] ?? 'NOT SET') . "\n";
echo "DB_USER: " . ($_ENV['DB_USER'] ?? 'NOT SET') . "\n";
echo "DB_PASS: " . (isset($_ENV['DB_PASS']) ? '***SET***' : 'NOT SET') . "\n";

echo "\n=== DB CONNECTION ===\n";
try {
    $db = App\Config\Database::getInstance();
    echo "✓ Database connected\n";
    
    $stmt = $db->query("SELECT COUNT(*) FROM users");
    $count = $stmt->fetchColumn();
    echo "✓ Users in DB: $count\n";
    
    $stmt = $db->query("SELECT username, name, role FROM users");
    $users = $stmt->fetchAll();
    echo "\nUsers:\n";
    foreach ($users as $user) {
        echo "- {$user['username']} ({$user['name']}) - {$user['role']}\n";
    }
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
}
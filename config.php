<?php
// config.php - opdater til at bruge $_ENV
return [
    'db' => [
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'name' => $_ENV['DB_NAME'] ?? 'pager_system',
        'user' => $_ENV['DB_USER'] ?? 'root',
        'pass' => $_ENV['DB_PASS'] ?? '',
        'charset' => 'utf8mb4'
    ],
    'session' => [
        'name' => 'PAGER_SESSION',
        'lifetime' => 28800,
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ],
    'upload_path' => __DIR__ . '/public/uploads',
    'upload_max_size' => 10485760
];
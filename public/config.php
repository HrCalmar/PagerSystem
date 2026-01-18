<?php
// config.php
return [
    'db' => [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'name' => getenv('DB_NAME') ?: 'pager_system',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') ?: '',
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
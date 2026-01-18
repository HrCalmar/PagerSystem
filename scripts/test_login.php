<?php
// scripts/test_login.php - test login direkte
require_once __DIR__ . '/../autoload.php';
use App\Config\Database;

$db = Database::getInstance();

echo "=== LOGIN TEST ===\n\n";

$username = 'admin';
$password = 'Calmar@2026!';

echo "Testing login for: $username\n";

// Find user
$stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user) {
    echo "✗ User not found!\n";
    
    // Show all users
    $all = $db->query("SELECT username, name, role FROM users")->fetchAll();
    echo "\nExisting users:\n";
    foreach ($all as $u) {
        echo "- {$u['username']} ({$u['name']}) - {$u['role']}\n";
    }
    exit;
}

echo "✓ User found!\n";
echo "ID: {$user['id']}\n";
echo "Username: {$user['username']}\n";
echo "Name: {$user['name']}\n";
echo "Role: {$user['role']}\n";
echo "Status: {$user['status']}\n";
echo "Hash: " . substr($user['password_hash'], 0, 30) . "...\n\n";

// Test password
$result = password_verify($password, $user['password_hash']);

if ($result) {
    echo "✓ PASSWORD CORRECT!\n";
} else {
    echo "✗ PASSWORD WRONG!\n";
    echo "\nGenerating new hash for '$password':\n";
    $newHash = password_hash($password, PASSWORD_DEFAULT);
    echo "$newHash\n\n";
    echo "Run this SQL:\n";
    echo "UPDATE users SET password_hash = '$newHash' WHERE username = '$username';\n";
}
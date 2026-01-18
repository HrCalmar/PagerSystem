<?php
// scripts/reset_password.php
require_once __DIR__ . '/../autoload.php';
use App\Config\Database;

$db = Database::getInstance();

$username = readline("Username at ændre password for: admin");
$newPassword = readline("Nyt password: Calmar@2026!");

$hash = password_hash($newPassword, PASSWORD_DEFAULT);

$stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE username = ?");
try {
    $stmt->execute([$hash, $username]);
    if ($stmt->rowCount() > 0) {
        echo "✓ Password ændret for $username\n";
    } else {
        echo "✗ Bruger ikke fundet\n";
    }
} catch (Exception $e) {
    echo "✗ Fejl: " . $e->getMessage() . "\n";
}
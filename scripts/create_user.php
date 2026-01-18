<?php
// scripts/create_user.php - med validering
require_once __DIR__ . '/../autoload.php';
use App\Config\Database;

$db = Database::getInstance();

echo "=== Opret ny bruger ===\n\n";

$username = readline("Username: HrCalmar");
$password = readline("Password: Calmar@2026!");
$name = readline("Fulde navn: Calle Calmar");

echo "\nVælg rolle:\n";
echo "1. admin (fuld adgang)\n";
echo "2. global_read (kan se alt, ingen redigering)\n";
echo "3. station_read (kan kun se egen station)\n";
$roleChoice = readline("1");

$roles = [
    '1' => 'admin',
    '2' => 'global_read',
    '3' => 'station_read'
];

if (!isset($roles[$roleChoice])) {
    die("Ugyldig rolle valgt\n");
}

$role = $roles[$roleChoice];

$stationId = null;
if ($role === 'station_read') {
    $stmt = $db->query("SELECT id, name FROM stations ORDER BY name");
    $stations = $stmt->fetchAll();
    
    echo "\nVælg station:\n";
    foreach ($stations as $idx => $station) {
        echo ($idx + 1) . ". " . $station['name'] . "\n";
    }
    
    $stationChoice = (int)readline("Vælg station (nummer): ");
    if (!isset($stations[$stationChoice - 1])) {
        die("Ugyldig station valgt\n");
    }
    $stationId = $stations[$stationChoice - 1]['id'];
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $db->prepare(
    "INSERT INTO users (username, password_hash, name, role, station_id, status) 
     VALUES (?, ?, ?, ?, ?, 'active')"
);

try {
    $stmt->execute([$username, $hash, $name, $role, $stationId]);
    echo "\n✓ Bruger oprettet!\n";
    echo "Username: $username\n";
    echo "Rolle: $role\n";
    if ($stationId) {
        echo "Station: " . $stations[$stationChoice - 1]['name'] . "\n";
    }
} catch (Exception $e) {
    echo "\n✗ Fejl: " . $e->getMessage() . "\n";
}
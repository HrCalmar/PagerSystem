<?php
namespace App\Services;

use App\Config\Database;
use PDO;
use Exception;

class UserService {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getAll(): array {
        return $this->db->query(
            "SELECT u.*, s.name as station_name
             FROM users u
             LEFT JOIN stations s ON s.id = u.station_id
             ORDER BY u.created_at DESC"
        )->fetchAll();
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getStations(): array {
        return $this->db->query("SELECT id, name FROM stations ORDER BY name")->fetchAll();
    }

    public function usernameExists(string $username): bool {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return (bool) $stmt->fetchColumn();
    }

    public function create(string $username, string $password, string $name, string $role, ?int $stationId): void {
        if (empty($username) || empty($password) || empty($name)) {
            throw new Exception('Brugernavn, password og navn skal udfyldes');
        }
        if (!in_array($role, ['admin', 'global_read', 'station_read'])) {
            throw new Exception('Ugyldig rolle');
        }
        if ($role === 'station_read' && !$stationId) {
            throw new Exception('Station skal vælges for station-brugere');
        }
        if ($this->usernameExists($username)) {
            throw new Exception('Brugernavn eksisterer allerede');
        }

        $stmt = $this->db->prepare(
            "INSERT INTO users (username, password_hash, name, role, station_id, status)
             VALUES (?, ?, ?, ?, ?, 'active')"
        );
        $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), $name, $role, $stationId]);
    }

    public function update(int $id, string $name, string $role, ?int $stationId, string $status): void {
        if (!in_array($role, ['admin', 'global_read', 'station_read'])) {
            throw new Exception('Ugyldig rolle');
        }
        if ($role === 'station_read' && !$stationId) {
            throw new Exception('Station skal vælges for station-brugere');
        }

        $stmt = $this->db->prepare(
            "UPDATE users SET name = ?, role = ?, station_id = ?, status = ? WHERE id = ?"
        );
        $stmt->execute([$name, $role, $stationId, $status, $id]);
    }

    public function resetPassword(int $id, string $password): void {
        if (empty($password)) throw new Exception('Password skal udfyldes');

        $stmt = $this->db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
    }

    public function updateName(int $userId, string $name): void {
        if (empty($name)) throw new Exception('Navn skal udfyldes');

        $stmt = $this->db->prepare("UPDATE users SET name = ? WHERE id = ?");
        $stmt->execute([$name, $userId]);
    }

    public function getPasswordHash(int $userId): ?string {
        $stmt = $this->db->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn() ?: null;
    }

    public function changePassword(int $userId, string $newPassword, string $confirmPassword, string $currentPassword): void {
        $hash = $this->getPasswordHash($userId);
        if (!$hash || !password_verify($currentPassword, $hash)) {
            throw new Exception('Nuværende password er forkert');
        }
        if (strlen($newPassword) < 8) {
            throw new Exception('Nyt password skal være mindst 8 tegn');
        }
        if ($newPassword !== $confirmPassword) {
            throw new Exception('De to passwords matcher ikke');
        }

        $stmt = $this->db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $userId]);
    }
}

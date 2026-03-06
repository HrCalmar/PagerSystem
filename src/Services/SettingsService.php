<?php
// src/Services/SettingsService.php
namespace App\Services;

use App\Config\Database;
use PDO;

class SettingsService {
    private PDO $db;
    private static array $cache = [];

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function get(string $key, mixed $default = null): mixed {
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $stmt = $this->db->prepare("SELECT value, type FROM settings WHERE `key` = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();

        if (!$row) {
            return $default;
        }

        $value = match($row['type']) {
            'boolean' => (bool)(int)$row['value'],
            'integer' => (int)$row['value'],
            'json'    => json_decode($row['value'], true),
            default   => $row['value'],
        };

        self::$cache[$key] = $value;
        return $value;
    }

    public function set(string $key, mixed $value): void {
        $stmt = $this->db->prepare("SELECT type FROM settings WHERE `key` = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();

        if (!$row) return;

        $stored = match($row['type']) {
            'boolean' => $value ? '1' : '0',
            'json'    => json_encode($value),
            default   => (string)$value,
        };

        $stmt = $this->db->prepare("UPDATE settings SET value = ? WHERE `key` = ?");
        $stmt->execute([$stored, $key]);

        unset(self::$cache[$key]);
    }

    public function getAll(): array {
        $stmt = $this->db->query("SELECT * FROM settings ORDER BY `key`");
        return $stmt->fetchAll();
    }
}
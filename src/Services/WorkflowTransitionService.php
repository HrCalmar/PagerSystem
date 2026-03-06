<?php
// src/Services/WorkflowTransitionService.php
namespace App\Services;

use App\Config\Database;
use PDO;

class WorkflowTransitionService {
    private PDO $db;
    private static array $cache = [];

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getForEvent(string $event, string $fromStatus): array {
        $key = $event . ':' . $fromStatus;
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $stmt = $this->db->prepare(
            "SELECT * FROM workflow_transitions
             WHERE event = ? AND from_status = ? AND is_enabled = 1
             ORDER BY sort_order"
        );
        $stmt->execute([$event, $fromStatus]);
        self::$cache[$key] = $stmt->fetchAll();
        return self::$cache[$key];
    }

    public function getDefault(string $event, string $fromStatus): ?array {
        $transitions = $this->getForEvent($event, $fromStatus);
        foreach ($transitions as $t) {
            if ($t['is_default']) return $t;
        }
        return $transitions[0] ?? null;
    }

    public function getAll(): array {
        $stmt = $this->db->query(
            "SELECT * FROM workflow_transitions ORDER BY event, from_status, sort_order"
        );
        return $stmt->fetchAll();
    }

    public function update(int $id, bool $isEnabled, bool $isDefault): void {
        if ($isDefault) {
            $stmt = $this->db->prepare(
                "SELECT from_status, event FROM workflow_transitions WHERE id = ?"
            );
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if ($row) {
                $stmt = $this->db->prepare(
                    "UPDATE workflow_transitions SET is_default = 0
                     WHERE event = ? AND from_status = ?"
                );
                $stmt->execute([$row['event'], $row['from_status']]);
            }
        }

        $stmt = $this->db->prepare(
            "UPDATE workflow_transitions SET is_enabled = ?, is_default = ? WHERE id = ?"
        );
        $stmt->execute([(int)$isEnabled, (int)$isDefault, $id]);
        self::$cache = [];
    }
}
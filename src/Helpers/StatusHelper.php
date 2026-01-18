<?php
// src/Helpers/StatusHelper.php
namespace App\Helpers;

class StatusHelper {
    public static function pagerStatus(string $status): array {
        $statuses = [
            'in_stock' => ['label' => 'På lager', 'icon' => 'fa-box'],
            'reserved' => ['label' => 'Reserveret', 'icon' => 'fa-bookmark'],
            'issued' => ['label' => 'Udleveret', 'icon' => 'fa-check-circle'],
            'for_preparation' => ['label' => 'Til klargøring', 'icon' => 'fa-tools'],
            'in_repair' => ['label' => 'Til reparation', 'icon' => 'fa-wrench'],
            'defect' => ['label' => 'Defekt', 'icon' => 'fa-exclamation-triangle'],
            'archived' => ['label' => 'Arkiveret', 'icon' => 'fa-archive']
        ];
        return $statuses[$status] ?? ['label' => $status, 'icon' => 'fa-question'];
    }
    
    public static function staffStatus(string $status): array {
        $statuses = [
            'active' => ['label' => 'Aktiv', 'icon' => 'fa-check'],
            'inactive' => ['label' => 'Inaktiv', 'icon' => 'fa-times']
        ];
        return $statuses[$status] ?? ['label' => $status, 'icon' => 'fa-question'];
    }
}

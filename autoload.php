<?php
// autoload.php - tilføj helper
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/src/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

require_once __DIR__ . '/env_loader.php';

// Helper functions
function status_badge(string $status, string $type = 'pager'): string {
    $helper = $type === 'pager' ? 
        \App\Helpers\StatusHelper::pagerStatus($status) : 
        \App\Helpers\StatusHelper::staffStatus($status);
    
    return sprintf(
        '<span class="badge status-%s"><i class="fas %s"></i> %s</span>',
        $status,
        $helper['icon'],
        $helper['label']
    );
}
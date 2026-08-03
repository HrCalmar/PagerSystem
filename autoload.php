<?php
spl_autoload_register(function ($class) {
    $prefix   = 'App\\';
    $base_dir = __DIR__ . '/src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file           = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

require_once __DIR__ . '/env_loader.php';

function status_badge(string $status, string $type = 'pager'): string {
    $helper = $type === 'pager'
        ? \App\Helpers\StatusHelper::pagerStatus($status)
        : \App\Helpers\StatusHelper::staffStatus($status);

    return sprintf(
        '<span class="badge status-%s"><i class="fas %s"></i> %s</span>',
        $status,
        $helper['icon'],
        $helper['label']
    );
}

function paginate_links(int $page, int $total, int $perPage, array $queryParams = []): string {
    $pages = max(1, (int)ceil($total / $perPage));
    if ($pages <= 1) return '';

    $buildUrl = function (int $p) use ($queryParams): string {
        return '?' . http_build_query(array_merge($queryParams, ['page' => $p]));
    };

    $from = ($page - 1) * $perPage + 1;
    $to   = min($page * $perPage, $total);

    $html  = '<div class="pagination">';
    $html .= '<span class="pagination-info">Viser ' . $from . '–' . $to . ' af ' . $total . '</span>';
    $html .= '<div class="pagination-links">';

    if ($page > 1) {
        $html .= '<a href="' . $buildUrl($page - 1) . '" class="btn btn-small">&laquo; Forrige</a>';
    }

    $start = max(1, $page - 3);
    $end   = min($pages, $page + 3);

    if ($start > 1)  $html .= '<a href="' . $buildUrl(1) . '" class="btn btn-small">1</a>';
    if ($start > 2)  $html .= '<span class="pagination-ellipsis">…</span>';

    for ($i = $start; $i <= $end; $i++) {
        $cls   = $i === $page ? ' btn-primary' : '';
        $html .= '<a href="' . $buildUrl($i) . '" class="btn btn-small' . $cls . '">' . $i . '</a>';
    }

    if ($end < $pages - 1) $html .= '<span class="pagination-ellipsis">…</span>';
    if ($end < $pages)     $html .= '<a href="' . $buildUrl($pages) . '" class="btn btn-small">' . $pages . '</a>';

    if ($page < $pages) {
        $html .= '<a href="' . $buildUrl($page + 1) . '" class="btn btn-small">Næste &raquo;</a>';
    }

    $html .= '</div></div>';
    return $html;
}

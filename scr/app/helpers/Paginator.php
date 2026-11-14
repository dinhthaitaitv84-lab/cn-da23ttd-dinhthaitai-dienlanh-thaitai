<?php

function paginate($total, $perPage, $page)
{
    $total = max(0, (int) $total);
    if (!is_scalar($perPage) || !in_array((string) $perPage, ['10', '20', '50'], true)) {
        $perPage = 10;
    }
    $perPage = (int) $perPage;

    $totalPages = max(1, (int) ceil($total / $perPage));
    $page = max(1, (int) $page);
    $page = min($page, $totalPages);

    return [
        'page' => $page,
        'perPage' => $perPage,
        'totalPages' => $totalPages,
        'offset' => ($page - 1) * $perPage,
    ];
}

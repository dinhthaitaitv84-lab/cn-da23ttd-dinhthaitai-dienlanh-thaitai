<?php
$page = (int) ($page ?? 1);
$totalPages = (int) ($totalPages ?? 1);
$total = max(0, (int) ($total ?? 0));
$perPage = (int) ($perPage ?? 10);
$offset = (int) ($offset ?? 0);
$baseQuery = is_array($baseQuery ?? null) ? $baseQuery : [];
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$queryForPage = static function ($targetPage) use ($baseQuery, $requestPath) {
    $query = $baseQuery;
    $query['page'] = $targetPage;
    return htmlspecialchars($requestPath . '?' . http_build_query($query), ENT_QUOTES, 'UTF-8');
};
$visiblePages = [];
for ($number = 1; $number <= $totalPages; $number++) {
    if ($number === 1 || $number === $totalPages || abs($number - $page) <= 2) {
        $visiblePages[] = $number;
    }
}
$start = $total > 0 ? $offset + 1 : 0;
$end = min($total, $offset + $perPage);
?>
<?php if ($totalPages > 1): ?>
    <div class="pagination-bar">
        <p class="pagination-summary">Hiển thị <?= $start ?> - <?= $end ?> trên tổng <?= $total ?></p>
        <nav class="pagination" aria-label="Phân trang">
            <?php if ($page > 1): ?><a class="pagination-prev" href="<?= $queryForPage($page - 1) ?>">Trước</a><?php endif; ?>
            <?php $previous = 0; foreach ($visiblePages as $number): ?>
                <?php if ($previous > 0 && $number > $previous + 1): ?><span class="pagination-ellipsis">...</span><?php endif; ?>
                <?php if ($number === $page): ?>
                    <span class="active" aria-current="page"><?= $number ?></span>
                <?php else: ?>
                    <a class="pagination-page" href="<?= $queryForPage($number) ?>"><?= $number ?></a>
                <?php endif; ?>
                <?php $previous = $number; ?>
            <?php endforeach; ?>
            <?php if ($page < $totalPages): ?><a class="pagination-next" href="<?= $queryForPage($page + 1) ?>">Sau</a><?php endif; ?>
        </nav>
    </div>
<?php elseif ($total > 0): ?>
    <p class="pagination-summary">Hiển thị <?= $start ?> - <?= $end ?> trên tổng <?= $total ?></p>
<?php endif; ?>

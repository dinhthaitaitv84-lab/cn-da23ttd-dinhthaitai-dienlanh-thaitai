<?php
$reportType = 'parts';
$report = is_array($report ?? null) ? $report : [];
$printReport = is_array($printReport ?? null) ? $printReport : [];
$reportTop = is_array($report['top'] ?? null) ? $report['top'] : [];
$reportLowStock = is_array($report['low_stock'] ?? null) ? $report['low_stock'] : [];
$printTop = is_array($printReport['top'] ?? null) ? $printReport['top'] : [];
$printLowStock = is_array($printReport['low_stock'] ?? null) ? $printReport['low_stock'] : [];
$usedQtyTotal = 0.0;
$partRequestTotal = 0;
foreach ($printTop as $part) {
    $usedQtyTotal += (float) ($part['used_qty'] ?? 0);
    $partRequestTotal += (int) ($part['request_count'] ?? 0);
}
$partSeries = array_map(function ($part) {
    return ['label' => (string) ($part['name'] ?? ''), 'value' => (float) ($part['used_qty'] ?? 0)];
}, $printTop);
?>
<section class="report-page">
    <?php require __DIR__ . '/_tools.php'; ?>
    <section class="dashboard-grid report-summary">
        <article class="dashboard-card"><span>Linh kiện có phát sinh</span><strong><?= count($printTop) ?></strong></article>
        <article class="dashboard-card"><span>Tổng lượng đã dùng</span><strong><?= htmlspecialchars(rtrim(rtrim(number_format($usedQtyTotal, 2, '.', ''), '0'), '.'), ENT_QUOTES, 'UTF-8') ?></strong></article>
        <article class="dashboard-card"><span>Lượt dùng theo linh kiện</span><strong><?= $partRequestTotal ?></strong></article>
        <article class="dashboard-card"><span>Linh kiện sắp hết</span><strong><?= (int) ($report['low_stock_total'] ?? 0) ?></strong></article>
    </section>
    <section class="table-card screen-only">
        <div class="table-card-head"><h2>Top 5 linh kiện dùng nhiều nhất</h2></div>
        <table>
            <thead><tr><th>Linh kiện</th><th>SKU</th><th>Đã dùng</th><th>Số yêu cầu</th></tr></thead>
            <tbody>
            <?php foreach ($reportTop as $part): ?>
                <tr>
                    <td><?= htmlspecialchars((string) ($part['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) ($part['sku'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars(rtrim(rtrim(number_format((float) ($part['used_qty'] ?? 0), 2, '.', ''), '0'), '.'), ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string) ($part['unit'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= (int) ($part['request_count'] ?? 0) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($reportTop)): ?><tr><td colspan="4" class="empty-state">Chưa có dữ liệu linh kiện.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </section>
    <div class="report-print-all">
        <h2>Top 5 linh kiện dùng nhiều nhất</h2>
        <table>
            <thead><tr><th>Linh kiện</th><th>SKU</th><th>Đã dùng</th><th>Số yêu cầu</th></tr></thead>
            <tbody>
            <?php foreach ($printTop as $part): ?>
                <tr>
                    <td><?= htmlspecialchars((string) ($part['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) ($part['sku'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars(rtrim(rtrim(number_format((float) ($part['used_qty'] ?? 0), 2, '.', ''), '0'), '.'), ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string) ($part['unit'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= (int) ($part['request_count'] ?? 0) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <h2>Linh kiện sắp hết hàng (tồn kho ≤ 5)</h2>
        <table>
            <thead><tr><th>Linh kiện</th><th>SKU</th><th>Tồn kho</th><th>Đơn vị</th></tr></thead>
            <tbody>
            <?php foreach ($printLowStock as $part): ?>
                <tr>
                    <td><?= htmlspecialchars((string) ($part['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) ($part['sku'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= (int) ($part['stock_qty'] ?? 0) ?></td>
                    <td><?= htmlspecialchars((string) ($part['unit'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <section class="table-card screen-only">
        <div class="table-card-head"><h2>Linh kiện sắp hết hàng (tồn kho ≤ 5)</h2></div>
        <table>
            <thead><tr><th>Linh kiện</th><th>SKU</th><th>Tồn kho</th><th>Đơn vị</th></tr></thead>
            <tbody>
            <?php foreach ($reportLowStock as $part): ?>
                <tr class="low-stock-row">
                    <td><?= htmlspecialchars((string) ($part['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) ($part['sku'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= (int) ($part['stock_qty'] ?? 0) ?></td>
                    <td><?= htmlspecialchars((string) ($part['unit'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($reportLowStock)): ?><tr><td colspan="4" class="empty-state">Không có linh kiện sắp hết hàng.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </section>
    <section class="card report-chart-card">
        <div class="section-card-heading"><div><h2>Mức sử dụng theo linh kiện</h2><p>Trong kỳ báo cáo · Đơn vị theo linh kiện</p></div></div>
        <div class="chart-container">
            <canvas class="report-chart" data-chart="bar" data-series="<?= htmlspecialchars(json_encode($partSeries, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>" aria-label="Mức sử dụng theo linh kiện" role="img"></canvas>
        </div>
    </section>
    <?php
    $total = (int) ($report['low_stock_total'] ?? 0);
    require __DIR__ . '/../../partials/pagination.php';
    ?>
    <section class="report-highlight"><strong><?= (int) ($report['low_stock_total'] ?? 0) ?> linh kiện sắp hết hàng</strong><p>Tổng lượng sử dụng của các linh kiện phổ biến trong kỳ: <?= htmlspecialchars(rtrim(rtrim(number_format($usedQtyTotal, 2, '.', ''), '0'), '.'), ENT_QUOTES, 'UTF-8') ?>.</p></section>
</section>

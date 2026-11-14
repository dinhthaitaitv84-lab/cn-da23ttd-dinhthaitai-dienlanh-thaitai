<?php
$reportType = 'services';
$summary = ['requests' => 0, 'completed' => 0, 'revenue' => 0.0];
foreach ($printRows as $summaryRow) {
    $summary['requests'] += (int) $summaryRow['request_count'];
    $summary['completed'] += (int) $summaryRow['completed_count'];
    $summary['revenue'] += (float) $summaryRow['revenue'];
}
$completionRate = $summary['requests'] > 0 ? 100 * $summary['completed'] / $summary['requests'] : 0;
?>
<section class="report-page">
    <?php require __DIR__ . '/_tools.php'; ?>
    <section class="dashboard-grid report-summary">
        <article class="dashboard-card"><span>Tổng yêu cầu</span><strong><?= $summary['requests'] ?></strong></article>
        <article class="dashboard-card"><span>Đã hoàn thành</span><strong><?= $summary['completed'] ?></strong></article>
        <article class="dashboard-card"><span>Tổng doanh thu</span><strong><?= number_format($summary['revenue'], 0, ',', '.') ?> ₫</strong></article>
        <article class="dashboard-card"><span>Tỷ lệ hoàn thành</span><strong><?= number_format($completionRate, 1, ',', '.') ?>%</strong></article>
    </section>
    <section class="report-grid">
        <div class="table-card screen-only">
            <div class="table-card-head"><h2>Chi tiết theo dịch vụ</h2></div>
            <table>
                <thead><tr><th>Nhóm dịch vụ</th><th>Dịch vụ</th><th>Yêu cầu</th><th>Hoàn thành</th><th>Doanh thu</th><th>Tỷ lệ</th></tr></thead>
                <tbody>
                <?php foreach ($report as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['category_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($row['service_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int) $row['request_count'] ?></td>
                        <td><?= (int) $row['completed_count'] ?></td>
                        <td><?= number_format((float) $row['revenue'], 0, ',', '.') ?> ₫</td>
                        <td><?= number_format((float) $row['completion_rate'], 1, ',', '.') ?>%</td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($report)): ?><tr><td colspan="6" class="empty-state">Chưa có dữ liệu.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="report-print-all">
            <table>
                <thead><tr><th>Nhóm dịch vụ</th><th>Dịch vụ</th><th>Yêu cầu</th><th>Hoàn thành</th><th>Doanh thu</th><th>Tỷ lệ</th></tr></thead>
                <tbody>
                <?php foreach ($printRows as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['category_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($row['service_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int) $row['request_count'] ?></td>
                        <td><?= (int) $row['completed_count'] ?></td>
                        <td><?= number_format((float) $row['revenue'], 0, ',', '.') ?> ₫</td>
                        <td><?= number_format((float) $row['completion_rate'], 1, ',', '.') ?>%</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <section class="card report-chart-card">
        <div class="section-card-heading"><div><h2>Doanh thu theo nhóm</h2><p>Phân bổ theo kỳ báo cáo · Đơn vị: đồng</p></div></div>
            <div class="chart-container chart-container-pie">
                <canvas class="report-chart" data-chart="pie" data-series="<?= htmlspecialchars(json_encode($categorySeries, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>" aria-label="Biểu đồ tròn doanh thu theo nhóm dịch vụ" role="img"></canvas>
            </div>
        </section>
    </section>
    <section class="report-highlight"><strong><?= $summary['completed'] ?> yêu cầu hoàn thành trong kỳ</strong><p>Tỷ lệ hoàn thành <?= number_format($completionRate, 1, ',', '.') ?>% trên <?= $summary['requests'] ?> yêu cầu theo các dịch vụ.</p></section>
    <?php require __DIR__ . '/../../partials/pagination.php'; ?>
</section>

<?php
$reportType = 'technicians';
$summary = ['assigned' => 0, 'completed' => 0, 'revenue' => 0.0];
foreach ($printRows as $summaryRow) {
    $summary['assigned'] += (int) $summaryRow['assigned_count'];
    $summary['completed'] += (int) $summaryRow['completed_count'];
    $summary['revenue'] += (float) $summaryRow['revenue'];
}
?>
<section class="report-page">
    <?php require __DIR__ . '/_tools.php'; ?>
    <section class="dashboard-grid report-summary">
        <article class="dashboard-card"><span>Kỹ thuật viên</span><strong><?= count($printRows) ?></strong></article>
        <article class="dashboard-card"><span>Công việc được giao</span><strong><?= $summary['assigned'] ?></strong></article>
        <article class="dashboard-card"><span>Đã hoàn thành</span><strong><?= $summary['completed'] ?></strong></article>
        <article class="dashboard-card"><span>Tổng doanh thu</span><strong><?= number_format($summary['revenue'], 0, ',', '.') ?> ₫</strong></article>
    </section>
    <section class="card report-chart-card">
        <div class="section-card-heading"><div><h2>So sánh số việc được giao và hoàn thành</h2><p>Theo kỳ báo cáo · Số yêu cầu</p></div></div>
        <div class="chart-container">
            <canvas class="report-chart" data-chart="grouped-bar" data-series="<?= htmlspecialchars(json_encode(array_map(function ($row) {
                return ['label' => $row['technician_name'], 'assigned' => (int) $row['assigned_count'], 'completed' => (int) $row['completed_count']];
            }, $chartRows), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>" aria-label="So sánh công việc kỹ thuật viên" role="img"></canvas>
        </div>
    </section>
    <div class="table-card screen-only">
        <div class="table-card-head"><h2>Chi tiết theo kỹ thuật viên</h2></div>
        <table>
            <thead><tr><th>Kỹ thuật viên</th><th>Được giao</th><th>Hoàn thành</th><th>Doanh thu</th><th>Điểm đánh giá</th><th>Xử lý TB (giờ)</th></tr></thead>
            <tbody>
            <?php foreach ($report as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['technician_name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= (int) $row['assigned_count'] ?></td>
                    <td><?= (int) $row['completed_count'] ?></td>
                    <td><?= number_format((float) $row['revenue'], 0, ',', '.') ?> ₫</td>
                    <td><?= $row['average_rating'] === null ? '—' : number_format((float) $row['average_rating'], 2, ',', '.') ?></td>
                    <td><?= number_format((float) $row['average_hours'], 1, ',', '.') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($report)): ?><tr><td colspan="6" class="empty-state">Chưa có dữ liệu.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="report-print-all table-card">
        <table>
            <thead><tr><th>Kỹ thuật viên</th><th>Được giao</th><th>Hoàn thành</th><th>Doanh thu</th><th>Điểm đánh giá</th><th>Xử lý TB (giờ)</th></tr></thead>
            <tbody>
            <?php foreach ($printRows as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['technician_name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= (int) $row['assigned_count'] ?></td>
                    <td><?= (int) $row['completed_count'] ?></td>
                    <td><?= number_format((float) $row['revenue'], 0, ',', '.') ?> ₫</td>
                    <td><?= $row['average_rating'] === null ? '—' : number_format((float) $row['average_rating'], 2, ',', '.') ?></td>
                    <td><?= number_format((float) $row['average_hours'], 1, ',', '.') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <section class="report-highlight"><strong><?= $summary['completed'] ?> yêu cầu hoàn thành trong kỳ</strong><p><?= $summary['assigned'] ?> công việc được giao · Doanh thu <?= number_format($summary['revenue'], 0, ',', '.') ?> ₫.</p></section>
    <?php require __DIR__ . '/../../partials/pagination.php'; ?>
</section>

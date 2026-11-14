<?php
$reportType = 'status';
$statusLabels = [
    'pending' => 'Chờ tiếp nhận', 'received' => 'Đã tiếp nhận',
    'checking' => 'Đang kiểm tra', 'waiting_parts' => 'Chờ linh kiện',
    'repairing' => 'Đang sửa chữa', 'quoted' => 'Đã báo giá',
    'completed' => 'Hoàn thành', 'cancelled' => 'Đã hủy',
];
$priorityLabels = ['low' => 'Thấp', 'normal' => 'Bình thường', 'high' => 'Cao', 'urgent' => 'Khẩn cấp'];
$statusCounts = array_fill_keys(array_keys($statusLabels), 0);
$priorityCounts = array_fill_keys(array_keys($priorityLabels), 0);
foreach ($report['statuses'] as $row) {
    if (isset($statusCounts[$row['status']])) {
        $statusCounts[$row['status']] = (int) $row['total'];
    }
}
foreach ($report['priorities'] as $row) {
    if (isset($priorityCounts[$row['priority']])) {
        $priorityCounts[$row['priority']] = (int) $row['total'];
    }
}
$statusSeries = [];
foreach ($statusCounts as $key => $value) {
    $statusSeries[] = ['label' => $statusLabels[$key], 'value' => $value];
}
$prioritySeries = [];
foreach ($priorityCounts as $key => $value) {
    $prioritySeries[] = ['label' => $priorityLabels[$key], 'value' => $value];
}
$requestTotal = array_sum($statusCounts);
$priorityTotal = array_sum($priorityCounts);
?>
<section class="report-page">
    <?php require __DIR__ . '/_tools.php'; ?>
    <section class="dashboard-grid report-summary">
        <article class="dashboard-card"><span>Tổng yêu cầu</span><strong><?= $requestTotal ?></strong></article>
        <article class="dashboard-card"><span>Đã hoàn thành</span><strong><?= $statusCounts['completed'] ?></strong></article>
        <article class="dashboard-card"><span>Đang chờ tiếp nhận</span><strong><?= $statusCounts['pending'] ?></strong></article>
        <article class="dashboard-card"><span>Ưu tiên cao / khẩn cấp</span><strong><?= $priorityCounts['high'] + $priorityCounts['urgent'] ?></strong></article>
    </section>
    <section class="report-grid">
        <div>
            <section class="table-card">
            <div class="table-card-head"><h2>Theo trạng thái</h2></div>
            <table>
                <thead><tr><th>Trạng thái</th><th>Số yêu cầu</th></tr></thead>
                <tbody><?php foreach ($statusCounts as $key => $count): ?>
                    <tr><td><span class="badge badge-status badge-<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabels[$key], ENT_QUOTES, 'UTF-8') ?></span></td><td><?= $count ?></td></tr>
                <?php endforeach; ?></tbody>
            </table>
            </section>
            <section class="card report-chart-card">
                <div class="section-card-heading"><div><h2>Yêu cầu theo trạng thái</h2><p>Theo kỳ báo cáo · Số yêu cầu</p></div></div>
                <div class="chart-container chart-container-compact"><canvas class="report-chart" data-chart="bar" data-series="<?= htmlspecialchars(json_encode($statusSeries, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>" aria-label="Số yêu cầu theo trạng thái" role="img"></canvas></div>
            </section>
        </div>
        <div>
            <section class="table-card">
            <div class="table-card-head"><h2>Theo mức ưu tiên</h2></div>
            <table>
                <thead><tr><th>Mức ưu tiên</th><th>Số yêu cầu</th></tr></thead>
                <tbody><?php foreach ($priorityCounts as $key => $count): ?>
                    <tr><td><span class="badge badge-priority-<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($priorityLabels[$key], ENT_QUOTES, 'UTF-8') ?></span></td><td><?= $count ?></td></tr>
                <?php endforeach; ?></tbody>
            </table>
            </section>
            <section class="card report-chart-card">
                <div class="section-card-heading"><div><h2>Yêu cầu theo mức ưu tiên</h2><p><?= $priorityTotal ?> yêu cầu trong kỳ</p></div></div>
                <div class="chart-container chart-container-compact"><canvas class="report-chart" data-chart="bar" data-series="<?= htmlspecialchars(json_encode($prioritySeries, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>" aria-label="Số yêu cầu theo mức ưu tiên" role="img"></canvas></div>
            </section>
        </div>
    </section>
    <section class="report-highlight"><strong><?= $statusCounts['completed'] ?> yêu cầu hoàn thành trong kỳ</strong><p>Tổng cộng <?= $requestTotal ?> yêu cầu được ghi nhận theo trạng thái.</p></section>
</section>

<?php $reportType = 'index'; ?>
<section class="report-page">
    <?php require __DIR__ . '/_tools.php'; ?>
    <section class="dashboard-grid report-summary">
        <article class="dashboard-card"><span>Tổng doanh thu</span><strong><?= number_format((float) $report['revenue'], 0, ',', '.') ?> ₫</strong></article>
        <article class="dashboard-card"><span>Yêu cầu hoàn thành</span><strong><?= (int) $report['completed'] ?></strong></article>
        <article class="dashboard-card"><span>Giá trị trung bình/đơn</span><strong><?= number_format((float) $report['average_order'], 0, ',', '.') ?> ₫</strong></article>
        <article class="dashboard-card"><span>Điểm đánh giá trung bình</span><strong><?= number_format((float) $report['average_rating'], 2, ',', '.') ?> / 5</strong></article>
    </section>
    <section class="card report-chart-card">
        <div class="section-card-heading"><div><h2>Doanh thu <?= $report['revenue_series']['monthly'] ? 'theo tháng' : 'theo ngày' ?></h2><p><?= htmlspecialchars(date('d/m/Y', strtotime($filters['from'])), ENT_QUOTES, 'UTF-8') ?> – <?= htmlspecialchars(date('d/m/Y', strtotime($filters['to'])), ENT_QUOTES, 'UTF-8') ?> · Đơn vị: đồng</p></div></div>
        <div class="chart-container">
            <canvas class="report-chart" data-chart="bar" data-series="<?= htmlspecialchars(json_encode($report['revenue_series']['items'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>" aria-label="Biểu đồ doanh thu" role="img"></canvas>
        </div>
        <p class="chart-legend"><span></span>Doanh thu dịch vụ</p>
    </section>
    <section class="report-highlight"><strong><?= (int) $report['completed'] ?> yêu cầu hoàn thành trong kỳ</strong><p>Tổng doanh thu <?= number_format((float) $report['revenue'], 0, ',', '.') ?> ₫ · Điểm đánh giá trung bình <?= number_format((float) $report['average_rating'], 2, ',', '.') ?>/5.</p></section>
</section>

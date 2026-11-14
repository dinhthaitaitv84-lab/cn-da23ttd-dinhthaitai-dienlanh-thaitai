<section class="dashboard-grid">
    <article class="dashboard-card">
        <div class="dashboard-stat-copy">
            <span>Tổng yêu cầu</span>
            <strong><?= (int) $stats['requests'] ?></strong>
            <small>Tất cả yêu cầu dịch vụ</small>
        </div>
        <span class="dashboard-stat-icon"><?= icon('request') ?></span>
    </article>
    <article class="dashboard-card dashboard-card-pending">
        <div class="dashboard-stat-copy">
            <span>Chờ xử lý</span>
            <strong><?= (int) $stats['pending'] ?></strong>
            <small>Cần được tiếp nhận</small>
        </div>
        <span class="dashboard-stat-icon"><?= icon('clock') ?></span>
    </article>
    <article class="dashboard-card">
        <div class="dashboard-stat-copy">
            <span>Đã hoàn thành</span>
            <strong><?= (int) $stats['completed'] ?></strong>
            <small>Yêu cầu đã hoàn tất</small>
        </div>
        <span class="dashboard-stat-icon"><?= icon('check') ?></span>
    </article>
    <article class="dashboard-card">
        <div class="dashboard-stat-copy">
            <span>Tổng khách hàng</span>
            <strong><?= (int) $stats['customers'] ?></strong>
            <small>Khách hàng trong hệ thống</small>
        </div>
        <span class="dashboard-stat-icon"><?= icon('users') ?></span>
    </article>
</section>

<section class="dashboard-report-grid">
    <article class="card report-chart-card">
        <div class="dashboard-card-heading">
            <div>
                <h2>Doanh thu</h2>
                <p>7 ngày gần nhất · Đơn vị: đồng</p>
            </div>
            <a href="<?= htmlspecialchars(url('/report/index'), ENT_QUOTES, 'UTF-8') ?>">Xem báo cáo</a>
        </div>
        <div class="chart-container">
            <canvas class="report-chart" data-chart="bar" data-series="<?= htmlspecialchars(json_encode($dashboardRevenue['items'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>" aria-label="Doanh thu bảy ngày gần nhất" role="img"></canvas>
        </div>
        <div class="dashboard-chart-legend"><span></span>Doanh thu dịch vụ</div>
    </article>

    <article class="card dashboard-top-technicians">
        <div class="dashboard-card-heading">
            <div>
                <h2>Kỹ thuật viên nổi bật</h2>
                <p>Top 3 · 7 ngày gần nhất</p>
            </div>
        </div>
        <?php if (!empty($topTechnicians)): ?>
            <ol>
                <?php foreach ($topTechnicians as $index => $technician): ?>
                    <li>
                        <span class="dashboard-rank"><?= sprintf('%02d', $index + 1) ?></span>
                        <div class="dashboard-technician-copy">
                            <strong><?= htmlspecialchars($technician['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <small><?= (int) $technician['completed_count'] ?> việc hoàn thành</small>
                        </div>
                        <strong class="dashboard-technician-revenue"><?= number_format((float) $technician['revenue'], 0, ',', '.') ?> ₫</strong>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php else: ?>
            <p class="empty-state">Chưa có việc hoàn thành trong 7 ngày gần nhất.</p>
        <?php endif; ?>
        <a class="dashboard-card-link" href="<?= htmlspecialchars(url('/technician/index'), ENT_QUOTES, 'UTF-8') ?>">Xem tất cả kỹ thuật viên</a>
    </article>
</section>

<section class="dashboard-action-grid">
    <article class="dashboard-pending-callout">
        <span class="dashboard-callout-label">CẦN XỬ LÝ</span>
        <h2><?= (int) $stats['pending'] ?> yêu cầu đang chờ tiếp nhận</h2>
        <p>Tiếp nhận và phân công kỹ thuật viên để bắt đầu xử lý.</p>
        <a class="button" href="<?= htmlspecialchars(url('/admin/requests?status=pending'), ENT_QUOTES, 'UTF-8') ?>">Xem yêu cầu</a>
    </article>
    <article class="card dashboard-quick-links">
        <h2>Truy cập nhanh</h2>
        <a href="<?= htmlspecialchars(url('/admin/schedule'), ENT_QUOTES, 'UTF-8') ?>"><span><?= icon('calendar') ?>Lịch kỹ thuật viên</span><?= icon('chevron-right') ?></a>
        <a href="<?= htmlspecialchars(url('/report/index'), ENT_QUOTES, 'UTF-8') ?>"><span><?= icon('chart') ?>Báo cáo tổng quan</span><?= icon('chevron-right') ?></a>
    </article>
</section>

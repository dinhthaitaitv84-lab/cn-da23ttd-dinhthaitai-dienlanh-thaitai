<div class="page-head">
    <div><p class="eyebrow">Khu vực kỹ thuật viên</p><h1>Trang chủ</h1></div>
    <a class="button" href="<?= htmlspecialchars(url('/technician/jobs'), ENT_QUOTES, 'UTF-8') ?>">Xem tất cả công việc</a>
</div>
<section class="dashboard-grid technician-dashboard-grid">
    <article class="dashboard-card"><span>Việc mới được giao</span><strong><?= (int) $stats['new_jobs'] ?></strong></article>
    <article class="dashboard-card"><span>Đang xử lý</span><strong><?= (int) $stats['in_progress'] ?></strong></article>
    <article class="dashboard-card"><span>Hoàn thành trong tháng</span><strong><?= (int) $stats['completed_month'] ?></strong></article>
</section>
<section class="technician-recent-section">
    <h2>5 công việc gần nhất</h2>
    <div class="table-card technician-job-list">
        <table>
            <thead><tr><th>Mã</th><th>Tiêu đề</th><th>Dịch vụ</th><th>Trạng thái</th><th>Ưu tiên</th><th>Thời gian hẹn</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($jobs as $job): ?>
                <tr>
                    <td><?= htmlspecialchars($job['code'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($job['title'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($job['service_name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge badge-status badge-<?= htmlspecialchars($job['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabels[$job['status']] ?? $job['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td><span class="badge badge-priority badge-priority-<?= htmlspecialchars($job['priority'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(['low' => 'Thấp', 'normal' => 'Bình thường', 'high' => 'Cao', 'urgent' => 'Khẩn cấp'][$job['priority']] ?? $job['priority'], ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($job['preferred_time'])), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><a class="button button-secondary" href="<?= htmlspecialchars(url('/technician/detail/' . (int) $job['id']), ENT_QUOTES, 'UTF-8') ?>">Chi tiết</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($jobs)): ?><tr><td colspan="7" class="empty-state">Chưa có công việc được giao.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<div class="page-head">
    <div><h1>Việc của tôi</h1><p>Danh sách yêu cầu được phân công cho bạn.</p></div>
    <a class="button button-secondary" href="/DoAn_DienLanh/scr/technician/schedule">Lịch làm việc</a>
</div>
<form class="filter-card" method="get" action="/DoAn_DienLanh/scr/technician/jobs">
    <div class="form-group">
        <label for="status">Trạng thái</label>
        <select id="status" name="status">
            <option value="">Tất cả trạng thái</option>
            <?php foreach ($statuses as $value => $label): ?>
                <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= $selectedStatus === $value ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="search">Tìm theo mã</label>
        <input id="search" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" maxlength="40">
    </div>
    <button type="submit">Lọc</button>
</form>
<div class="table-card technician-job-list">
    <table>
        <thead><tr><th>Mã</th><th>Tiêu đề</th><th>Dịch vụ</th><th>Trạng thái</th><th>Ưu tiên</th><th>Thời gian hẹn</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($jobs as $job): ?>
            <tr>
                <td><?= htmlspecialchars($job['code'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($job['title'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($job['service_name'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><span class="badge badge-status badge-<?= htmlspecialchars($job['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statuses[$job['status']] ?? $job['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
                <td><span class="badge badge-priority badge-priority-<?= htmlspecialchars($job['priority'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(['low' => 'Thấp', 'normal' => 'Bình thường', 'high' => 'Cao', 'urgent' => 'Khẩn cấp'][$job['priority']] ?? $job['priority'], ENT_QUOTES, 'UTF-8') ?></span></td>
                <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($job['preferred_time'])), ENT_QUOTES, 'UTF-8') ?></td>
                <td><a class="button button-secondary" href="/DoAn_DienLanh/scr/technician/detail/<?= (int) $job['id'] ?>">Chi tiết</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($jobs)): ?><tr><td colspan="7" class="empty-state">Không tìm thấy công việc.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../partials/pagination.php'; ?>

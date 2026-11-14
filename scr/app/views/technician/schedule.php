<div class="page-head">
    <div><h1>Lịch làm việc</h1><p>Lịch hẹn và các ca làm việc đã được phân công.</p></div>
    <a class="button button-secondary" href="<?= htmlspecialchars(url('/technician/jobs'), ENT_QUOTES, 'UTF-8') ?>">Việc của tôi</a>
</div>
<form class="filter-card schedule-filter" method="get" action="<?= htmlspecialchars(url('/technician/schedule'), ENT_QUOTES, 'UTF-8') ?>">
    <div class="form-group">
        <label for="date">Ngày</label>
        <input id="date" name="date" type="date" value="<?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?>" required>
    </div>
    <?php require __DIR__ . '/../partials/pagination.php'; ?>
    <div class="form-group">
        <label for="period">Khoảng thời gian</label>
        <select id="period" name="period">
            <option value="day" <?= $period === 'day' ? 'selected' : '' ?>>Ngày</option>
            <option value="week" <?= $period === 'week' ? 'selected' : '' ?>>Tuần</option>
        </select>
    </div>
    <button type="submit">Xem lịch</button>
</form>
<h2 class="schedule-range"><?= htmlspecialchars($start, ENT_QUOTES, 'UTF-8') ?><?= $period === 'week' ? ' – ' . htmlspecialchars($end, ENT_QUOTES, 'UTF-8') : '' ?></h2>
<div class="table-card technician-schedule-list">
    <table>
        <thead><tr><th>Bắt đầu</th><th>Kết thúc</th><th>Mã yêu cầu</th><th>Khách hàng</th><th>Tiêu đề</th><th>Trạng thái</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($events as $event): ?>
            <tr>
                <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($event['start_time'])), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($event['end_time'])), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($event['code'] ?? 'Lịch cá nhân', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($event['customer_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($event['title'] ?? ($event['note'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?php if ($event['status']): ?><span class="badge badge-status badge-<?= htmlspecialchars($event['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabels[$event['status']] ?? $event['status'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?></td>
                <td><?php if ($event['request_id']): ?><a class="button button-secondary" href="<?= htmlspecialchars(url('/technician/detail/' . (int) $event['request_id']), ENT_QUOTES, 'UTF-8') ?>">Chi tiết</a><?php endif; ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($events)): ?><tr><td colspan="7" class="empty-state">Không có lịch trong khoảng thời gian này.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<?php
$days = [];
foreach ($schedules as $schedule) {
    $day = date('Y-m-d', strtotime($schedule['start_time']));
    $days[$day][] = $schedule;
}
?>
<section>
    <div class="page-head"><div><h1>Lịch kỹ thuật viên</h1><p>Theo dõi lịch phân công của đội ngũ theo tuần.</p></div></div>
    <div class="schedule-week-nav">
        <a class="button button-secondary" href="?week=<?= htmlspecialchars($previousWeek, ENT_QUOTES, 'UTF-8') ?>&amp;technician_id=<?= (int) $technicianId ?>">Tuần trước</a>
        <strong><?= htmlspecialchars($weekStart->format('d/m/Y'), ENT_QUOTES, 'UTF-8') ?> – <?= htmlspecialchars($weekEnd->format('d/m/Y'), ENT_QUOTES, 'UTF-8') ?></strong>
        <a class="button button-secondary" href="?week=<?= htmlspecialchars($nextWeek, ENT_QUOTES, 'UTF-8') ?>&amp;technician_id=<?= (int) $technicianId ?>">Tuần sau</a>
    </div>
    <form class="filter-card" method="get" action="<?= htmlspecialchars(url('/admin/schedule'), ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="week" value="<?= htmlspecialchars($weekStart->format('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>">
        <div class="form-group">
            <label for="schedule-technician">Kỹ thuật viên</label>
            <select id="schedule-technician" name="technician_id">
                <option value="0">Tất cả kỹ thuật viên</option>
                <?php foreach ($technicians as $technician): ?>
                    <option value="<?= (int) $technician['id'] ?>" <?= (int) $technicianId === (int) $technician['id'] ? 'selected' : '' ?>><?= htmlspecialchars($technician['full_name'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit">Lọc lịch</button>
    </form>

    <?php foreach ($days as $day => $daySchedules): ?>
        <section class="schedule-day">
            <h2><?= htmlspecialchars(date('l, d/m/Y', strtotime($day)), ENT_QUOTES, 'UTF-8') ?></h2>
            <?php foreach ($daySchedules as $schedule): ?>
                <article class="card schedule-entry">
                    <div class="schedule-entry-time">
                        <?= htmlspecialchars(date('H:i', strtotime($schedule['start_time'])), ENT_QUOTES, 'UTF-8') ?>
                        – <?= htmlspecialchars(date('H:i', strtotime($schedule['end_time'])), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <div>
                        <strong><?= htmlspecialchars($schedule['technician_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <?php if ($schedule['technician_phone']): ?>
                            <span><?= htmlspecialchars($schedule['technician_phone'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                        <?php if ($schedule['request_id']): ?>
                            <p><a href="<?= htmlspecialchars(url('/admin/requestDetail/' . (int) $schedule['request_id']), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($schedule['request_code'], ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars($schedule['request_title'], ENT_QUOTES, 'UTF-8') ?></a></p>
                        <?php else: ?>
                            <p><?= htmlspecialchars($schedule['note'] ?? 'Ca làm việc', ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endforeach; ?>
    <?php if (empty($schedules)): ?><p class="empty-state">Không có lịch trong tuần này.</p><?php endif; ?>
    <?php require __DIR__ . '/../partials/pagination.php'; ?>
</section>

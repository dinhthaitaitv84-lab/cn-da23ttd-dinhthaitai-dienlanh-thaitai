<section class="request-detail">
    <div class="page-head">
        <div><h1>Yêu cầu <?= htmlspecialchars($request['code'], ENT_QUOTES, 'UTF-8') ?></h1><p><?= htmlspecialchars($request['title'], ENT_QUOTES, 'UTF-8') ?></p></div>
        <a class="button button-secondary" href="<?= htmlspecialchars(url('/admin/requests'), ENT_QUOTES, 'UTF-8') ?>">Về danh sách</a>
    </div>
    <div class="request-detail-layout<?= in_array($request['status'], ['completed', 'cancelled'], true) ? ' is-closed' : '' ?>">
    <div class="request-detail-primary">
    <section class="card info-grid-card">
    <div class="request-info-grid info-grid">
        <div><strong>Trạng thái</strong><span class="badge badge-status badge-<?= htmlspecialchars($request['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabels[$request['status']] ?? $request['status'], ENT_QUOTES, 'UTF-8') ?></span></div>
        <div><strong>Mã yêu cầu</strong><span><?= htmlspecialchars($request['code'], ENT_QUOTES, 'UTF-8') ?></span></div>
        <div><strong>Khách hàng</strong><span><?= htmlspecialchars($request['customer_name'], ENT_QUOTES, 'UTF-8') ?></span></div>
        <div><strong>Email / SĐT</strong><span><?= htmlspecialchars($request['customer_email'], ENT_QUOTES, 'UTF-8') ?><?= !empty($request['customer_phone']) ? ' / ' . htmlspecialchars($request['customer_phone'], ENT_QUOTES, 'UTF-8') : '' ?></span></div>
        <div><strong>Dịch vụ</strong><span><?= htmlspecialchars($request['service_name'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($request['category_name'], ENT_QUOTES, 'UTF-8') ?>)</span></div>
        <div><strong>Thiết bị</strong><span><?= htmlspecialchars(trim(($request['device_name'] ?? '') . (($request['device_brand'] ?? '') !== '' ? ' - ' . $request['device_brand'] : '') . (($request['device_model'] ?? '') !== '' ? ' ' . $request['device_model'] : '')) ?: 'Chưa khai báo', ENT_QUOTES, 'UTF-8') ?></span></div>
        <div><strong>Tiêu đề</strong><span><?= htmlspecialchars($request['title'], ENT_QUOTES, 'UTF-8') ?></span></div>
        <div><strong>Địa chỉ</strong><span><?= nl2br(htmlspecialchars($request['address'], ENT_QUOTES, 'UTF-8')) ?></span></div>
        <div><strong>Thời gian mong muốn</strong><span><?= htmlspecialchars(date('d/m/Y H:i', strtotime($request['preferred_time'])), ENT_QUOTES, 'UTF-8') ?></span></div>
        <div><strong>Ngày gửi</strong><span><?= htmlspecialchars(date('d/m/Y H:i', strtotime($request['created_at'])), ENT_QUOTES, 'UTF-8') ?></span></div>
        <div><strong>Kỹ thuật viên</strong><span><?= htmlspecialchars($request['technician_name'] ?? 'Chưa phân công', ENT_QUOTES, 'UTF-8') ?></span></div>
        <div><strong>Mức ưu tiên</strong><span class="badge badge-priority badge-priority-<?= htmlspecialchars($request['priority'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($priorityLabels[$request['priority']] ?? $request['priority'], ENT_QUOTES, 'UTF-8') ?></span></div>
        <?php if (!empty($request['description'])): ?><div class="request-description"><strong>Mô tả</strong><span><?= nl2br(htmlspecialchars($request['description'], ENT_QUOTES, 'UTF-8')) ?></span></div><?php endif; ?>
    </div>
    </section>

    <?php if (!empty($request['images'])): ?>
        <section class="detail-section card">
            <h2>Ảnh yêu cầu</h2>
            <div class="request-gallery">
                <?php foreach ($request['images'] as $image): ?>
                    <button class="gallery-image js-image-modal" type="button" data-image="<?= htmlspecialchars(url('/' . ltrim($image, '/\\')), ENT_QUOTES, 'UTF-8') ?>" aria-label="Phóng to ảnh">
                        <img src="<?= htmlspecialchars(url('/' . ltrim($image, '/\\')), ENT_QUOTES, 'UTF-8') ?>" alt="Ảnh yêu cầu <?= htmlspecialchars($request['code'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
                    </button>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if (!empty($request['report'])): ?>
        <section class="detail-section card quote-section">
            <h2>Biên bản sửa chữa và báo giá</h2>
            <p><strong>Nguyên nhân:</strong> <?= nl2br(htmlspecialchars($request['report']['cause'] ?? '', ENT_QUOTES, 'UTF-8')) ?></p>
            <p><strong>Phương án xử lý:</strong> <?= nl2br(htmlspecialchars($request['report']['solution'] ?? '', ENT_QUOTES, 'UTF-8')) ?></p>
            <p><strong>Tiền công:</strong> <?= number_format((float) $request['report']['labor_cost'], 0, ',', '.') ?> ₫</p>
            <p><strong>Bảo hành:</strong> <?= (int) $request['report']['warranty_months'] ?> tháng</p>
            <div class="table-card quote-table-wrap">
                <table class="quote-table">
                    <thead><tr><th>Linh kiện</th><th>Số lượng</th><th>Đơn giá</th><th>Thành tiền</th></tr></thead>
                    <tbody>
                    <?php foreach ($request['report_parts'] as $part): ?>
                        <tr>
                            <td><?= htmlspecialchars($part['name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars(rtrim(rtrim(number_format((float) $part['qty'], 2, '.', ''), '0'), '.'), ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($part['unit'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= number_format((float) $part['unit_price'], 0, ',', '.') ?> ₫</td>
                            <td><?= number_format((float) $part['qty'] * (float) $part['unit_price'], 0, ',', '.') ?> ₫</td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <?php if (!empty($request['quote'])): ?>
                        <tfoot><tr><th colspan="3">Báo giá (<?= htmlspecialchars($request['quote']['status'], ENT_QUOTES, 'UTF-8') ?>)</th><th><?= number_format((float) $request['quote']['total_amount'], 0, ',', '.') ?> ₫</th></tr></tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </section>
    <?php elseif (!empty($request['quote'])): ?>
        <section class="detail-section card quote-section">
            <h2>Báo giá</h2>
            <p><span class="badge badge-quote-<?= htmlspecialchars($request['quote']['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($request['quote']['status'], ENT_QUOTES, 'UTF-8') ?></span>
                <?= number_format((float) $request['quote']['total_amount'], 0, ',', '.') ?> ₫</p>
        </section>
    <?php endif; ?>

    <?php if (!empty($request['review'])): ?>
        <section class="card admin-review-summary">
            <h2>Đánh giá khách hàng</h2>
            <p class="review-stars" aria-label="<?= (int) $request['review']['rating'] ?> trên 5"><?= (int) $request['review']['rating'] ?>/5</p>
            <p><strong>Khách hàng:</strong> <?= htmlspecialchars($request['review']['reviewer_name'], ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>Kỹ thuật viên:</strong> <?= htmlspecialchars($request['review']['technician_name'] ?? 'Chưa phân công', ENT_QUOTES, 'UTF-8') ?></p>
            <?php if (!empty($request['review']['comment'])): ?><p><?= nl2br(htmlspecialchars($request['review']['comment'], ENT_QUOTES, 'UTF-8')) ?></p><?php endif; ?>
            <time><?= htmlspecialchars(date('d/m/Y H:i', strtotime($request['review']['created_at'])), ENT_QUOTES, 'UTF-8') ?></time>
        </section>
    <?php endif; ?>

    <section class="detail-section card">
        <h2>Lịch sử trạng thái</h2>
        <?php if (!empty($request['logs'])): ?>
            <ol class="request-timeline timeline">
                <?php foreach ($request['logs'] as $log): ?>
                    <li><div class="timeline-content">
                        <span class="badge badge-status badge-<?= htmlspecialchars($log['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabels[$log['status']] ?? $log['status'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php if (!empty($log['note'])): ?><p><?= nl2br(htmlspecialchars($log['note'], ENT_QUOTES, 'UTF-8')) ?></p><?php endif; ?>
                        <time><?= htmlspecialchars(date('d/m/Y H:i', strtotime($log['created_at'])), ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($log['changed_by_name'], ENT_QUOTES, 'UTF-8') ?></time>
                    </div></li>
                <?php endforeach; ?>
            </ol>
        <?php else: ?><p class="empty-state">Chưa có lịch sử trạng thái.</p><?php endif; ?>
    </section>
    </div>
    <aside class="request-detail-sidebar">
    <div class="admin-action-grid">
        <?php if (!in_array($request['status'], ['completed', 'cancelled'], true)): ?>
            <form class="card admin-action-card" method="post" action="<?= htmlspecialchars(url('/admin/requestPriority/' . (int) $request['id']), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                <h2>Đổi mức ưu tiên</h2>
                <div class="form-group">
                    <label for="priority">Mức ưu tiên</label>
                    <select id="priority" name="priority" required>
                        <?php foreach ($priorityLabels as $value => $label): ?>
                            <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= $request['priority'] === $value ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit">Cập nhật ưu tiên</button>
            </form>

            <form class="card admin-action-card" method="post" action="<?= htmlspecialchars(url('/admin/requestAssign/' . (int) $request['id']), ENT_QUOTES, 'UTF-8') ?>" data-schedule-form data-duration="<?= (int) $request['est_minutes'] ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                <h2><?= empty($request['technician_id']) ? 'Phân công kỹ thuật viên' : 'Phân công lại' ?></h2>
                <div class="form-group">
                    <label for="technician_id">Kỹ thuật viên</label>
                    <select id="technician_id" name="technician_id" required>
                        <option value="">Chọn kỹ thuật viên</option>
                        <?php foreach ($technicians as $technician): ?>
                            <option value="<?= (int) $technician['id'] ?>" <?= (int) $request['technician_id'] === (int) $technician['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($technician['full_name'] . ' - ' . $technician['specialty'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label for="start_time">Bắt đầu</label><input id="start_time" name="start_time" type="datetime-local" value="<?= htmlspecialchars($defaultStart, ENT_QUOTES, 'UTF-8') ?>" required></div>
                <div class="form-group"><label for="end_time">Kết thúc</label><input id="end_time" name="end_time" type="datetime-local" value="<?= htmlspecialchars($defaultEnd, ENT_QUOTES, 'UTF-8') ?>" required></div>
                <button type="submit">Lưu phân công</button>
            </form>
        <?php endif; ?>
    </div>
    <?php if (!in_array($request['status'], ['completed', 'cancelled'], true)): ?>
        <form class="card admin-action-card cancel-request-form" method="post" action="<?= htmlspecialchars(url('/admin/requestCancel/' . (int) $request['id']), ENT_QUOTES, 'UTF-8') ?>" data-confirm="Xác nhận hủy yêu cầu này?">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <h2>Hủy yêu cầu</h2>
            <div class="form-group"><label for="reason">Lý do hủy</label><textarea id="reason" name="reason" rows="3" maxlength="1000" required></textarea></div>
            <button class="button-danger" type="submit">Hủy yêu cầu</button>
        </form>
    <?php endif; ?>
    </aside>
    </div>
</section>
<dialog class="image-dialog" aria-label="Ảnh yêu cầu">
    <form method="dialog"><button class="button-secondary" type="submit">Đóng</button></form>
    <img alt="Ảnh yêu cầu được phóng to">
</dialog>

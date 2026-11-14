<?php
$priorityLabels = ['low' => 'Thấp', 'normal' => 'Bình thường', 'high' => 'Cao', 'urgent' => 'Khẩn cấp'];
$readOnly = in_array($job['status'], ['completed', 'cancelled'], true);
$report = $job['report'] ?: ['cause' => '', 'solution' => '', 'labor_cost' => '0', 'warranty_months' => '0'];
$partStocks = [];
foreach ($job['parts'] as $availablePart) {
    $partStocks[(int) $availablePart['id']] = (int) $availablePart['stock_qty'];
}
?>
<section class="technician-job-detail">
    <div class="page-head">
        <div><h1>Công việc <?= htmlspecialchars($job['code'], ENT_QUOTES, 'UTF-8') ?></h1><p><?= htmlspecialchars($job['title'], ENT_QUOTES, 'UTF-8') ?></p></div>
        <a class="button button-secondary" href="/DoAn_DienLanh/scr/technician/jobs">Quay lại</a>
    </div>
    <section class="card info-grid-card">
    <div class="request-info-grid info-grid">
        <div><strong>Trạng thái</strong><span class="badge badge-status badge-<?= htmlspecialchars($job['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabels[$job['status']] ?? $job['status'], ENT_QUOTES, 'UTF-8') ?></span></div>
        <div><strong>Dịch vụ</strong><span><?= htmlspecialchars($job['service_name'], ENT_QUOTES, 'UTF-8') ?></span></div>
        <div><strong>Tiêu đề</strong><span><?= htmlspecialchars($job['title'], ENT_QUOTES, 'UTF-8') ?></span></div>
        <div><strong>Khách hàng</strong><span><?= htmlspecialchars($job['customer_name'], ENT_QUOTES, 'UTF-8') ?></span></div>
        <div><strong>SĐT khách hàng</strong><span><?= htmlspecialchars($job['customer_phone'] ?? '', ENT_QUOTES, 'UTF-8') ?></span></div>
        <div><strong>Địa chỉ</strong><span><?= nl2br(htmlspecialchars($job['address'], ENT_QUOTES, 'UTF-8')) ?></span></div>
        <div><strong>Thiết bị</strong><span><?= htmlspecialchars(trim(($job['device_name'] ?? '') . (($job['device_brand'] ?? '') !== '' ? ' - ' . $job['device_brand'] : '') . (($job['device_model'] ?? '') !== '' ? ' ' . $job['device_model'] : '')) ?: 'Chưa khai báo', ENT_QUOTES, 'UTF-8') ?></span></div>
        <div><strong>Mức ưu tiên</strong><span class="badge badge-priority badge-priority-<?= htmlspecialchars($job['priority'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($priorityLabels[$job['priority']] ?? $job['priority'], ENT_QUOTES, 'UTF-8') ?></span></div>
        <div><strong>Thời gian mong muốn</strong><span><?= htmlspecialchars(date('d/m/Y H:i', strtotime($job['preferred_time'])), ENT_QUOTES, 'UTF-8') ?></span></div>
        <div><strong>Mô tả</strong><span><?= nl2br(htmlspecialchars($job['description'] ?? 'Không có mô tả.', ENT_QUOTES, 'UTF-8')) ?></span></div>
        <?php if ($job['schedule']): ?>
            <div class="request-description"><strong>Lịch hẹn</strong><span><?= htmlspecialchars(date('d/m/Y H:i', strtotime($job['schedule']['start_time'])), ENT_QUOTES, 'UTF-8') ?> – <?= htmlspecialchars(date('d/m/Y H:i', strtotime($job['schedule']['end_time'])), ENT_QUOTES, 'UTF-8') ?></span></div>
        <?php endif; ?>
    </div>
    </section>

    <?php if (!empty($job['images'])): ?>
        <section class="detail-section card">
            <h2>Ảnh yêu cầu</h2>
            <div class="request-gallery">
                <?php foreach ($job['images'] as $image): ?>
                    <button class="gallery-image js-image-modal" type="button" data-image="<?= htmlspecialchars('/DoAn_DienLanh/scr/' . ltrim($image, '/\\'), ENT_QUOTES, 'UTF-8') ?>" aria-label="Phóng to ảnh">
                        <img src="<?= htmlspecialchars('/DoAn_DienLanh/scr/' . ltrim($image, '/\\'), ENT_QUOTES, 'UTF-8') ?>" alt="Ảnh yêu cầu <?= htmlspecialchars($job['code'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
                    </button>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if (!$readOnly && !empty($transitions[$job['status']])): ?>
        <form class="card technician-action-card" method="post" action="/DoAn_DienLanh/scr/technician/updateStatus/<?= (int) $job['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <h2>Cập nhật trạng thái</h2>
            <div class="form-group"><label for="next-status">Chuyển sang</label>
                <select id="next-status" name="status" required>
                    <?php foreach ($transitions[$job['status']] as $value => $label): ?>
                        <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label for="status-note">Ghi chú</label><textarea id="status-note" name="note" rows="3" maxlength="5000"></textarea></div>
            <button type="submit">Cập nhật</button>
        </form>
    <?php endif; ?>

    <?php if (!$readOnly): ?>
        <form class="card technician-action-card repair-report-form" method="post" action="/DoAn_DienLanh/scr/technician/saveReport/<?= (int) $job['id'] ?>" data-labor-cost="<?= htmlspecialchars((string) $report['labor_cost'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <h2>Biên bản sửa chữa</h2>
            <div class="form-group"><label for="cause">Nguyên nhân hư hỏng</label><textarea id="cause" name="cause" rows="3"><?= htmlspecialchars($report['cause'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea></div>
            <div class="form-group"><label for="solution">Phương án xử lý</label><textarea id="solution" name="solution" rows="3"><?= htmlspecialchars($report['solution'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea></div>
            <div class="form-group"><label for="labor_cost">Tiền công (₫)</label><input id="labor_cost" name="labor_cost" type="number" min="0" step="1000" value="<?= htmlspecialchars((string) $report['labor_cost'], ENT_QUOTES, 'UTF-8') ?>" required></div>
            <div class="form-group"><label for="warranty_months">Bảo hành (tháng)</label><input id="warranty_months" name="warranty_months" type="number" min="0" max="65535" step="1" value="<?= (int) $report['warranty_months'] ?>" required></div>
            <div class="form-group">
                <label>Linh kiện thay thế</label>
                <div class="repair-parts-list" data-parts-list>
                    <?php foreach ($job['report_parts'] as $reportPart): ?>
                        <div class="repair-part-row">
                            <select name="part_ids[]" class="repair-part-select" aria-label="Linh kiện" required>
                                <option value="">Chọn linh kiện</option>
                                <?php foreach ($job['parts'] as $part): ?>
                                    <option value="<?= (int) $part['id'] ?>" data-price="<?= (float) $part['price'] ?>" data-stock="<?= (int) $part['stock_qty'] ?>" <?= (int) $reportPart['part_id'] === (int) $part['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($part['name'] . ' (' . $part['sku'] . ') - tồn ' . $part['stock_qty'] . ' ' . $part['unit'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input name="quantities[]" class="repair-part-qty" type="number" min="0.01" step="0.01" max="<?= $partStocks[(int) $reportPart['part_id']] ?? 0 ?>" value="<?= htmlspecialchars(rtrim(rtrim(number_format((float) $reportPart['qty'], 2, '.', ''), '0'), '.'), ENT_QUOTES, 'UTF-8') ?>" aria-label="Số lượng" required>
                            <button class="button-danger js-remove-part" type="button">Xóa</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button class="button-secondary js-add-part" type="button">Thêm linh kiện</button>
            </div>
            <template class="repair-part-template">
                <div class="repair-part-row">
                    <select name="part_ids[]" class="repair-part-select" aria-label="Linh kiện" required>
                        <option value="">Chọn linh kiện</option>
                        <?php foreach ($job['parts'] as $part): ?>
                            <option value="<?= (int) $part['id'] ?>" data-price="<?= (float) $part['price'] ?>" data-stock="<?= (int) $part['stock_qty'] ?>">
                                <?= htmlspecialchars($part['name'] . ' (' . $part['sku'] . ') - tồn ' . $part['stock_qty'] . ' ' . $part['unit'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input name="quantities[]" class="repair-part-qty" type="number" min="0.01" step="0.01" value="1" aria-label="Số lượng" required>
                    <button class="button-danger js-remove-part" type="button">Xóa</button>
                </div>
            </template>
            <p class="quote-preview">Tổng dự kiến: <strong data-quote-total>0 ₫</strong></p>
            <button type="submit">Lưu biên bản</button>
        </form>
    <?php endif; ?>

    <?php if (!$readOnly && in_array($job['status'], ['repairing', 'quoted'], true) && $job['report']): ?>
        <form class="card technician-action-card" method="post" action="/DoAn_DienLanh/scr/technician/createQuote/<?= (int) $job['id'] ?>" data-confirm="Tạo và gửi báo giá cho yêu cầu này?">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <h2>Báo giá</h2>
            <?php if ($job['quote']): ?><p>Báo giá gần nhất: <strong><?= number_format((float) $job['quote']['total_amount'], 0, ',', '.') ?> ₫</strong></p><?php endif; ?>
            <p>Tổng tiền lấy từ tiền công và linh kiện đã lưu trong biên bản.</p>
            <button type="submit"><?= $job['status'] === 'quoted' ? 'Gửi lại báo giá' : 'Tạo báo giá' ?></button>
        </form>
    <?php endif; ?>

    <section class="detail-section card">
        <h2>Timeline trạng thái</h2>
        <?php if ($job['logs']): ?>
            <ol class="request-timeline timeline">
                <?php foreach ($job['logs'] as $log): ?>
                    <li><div class="timeline-content">
                        <span class="badge badge-status badge-<?= htmlspecialchars($log['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabels[$log['status']] ?? $log['status'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php if (!empty($log['note'])): ?><p><?= nl2br(htmlspecialchars($log['note'], ENT_QUOTES, 'UTF-8')) ?></p><?php endif; ?>
                        <time><?= htmlspecialchars(date('d/m/Y H:i', strtotime($log['created_at'])), ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($log['changed_by_name'], ENT_QUOTES, 'UTF-8') ?></time>
                    </div></li>
                <?php endforeach; ?>
            </ol>
        <?php else: ?><p class="empty-state">Chưa có lịch sử trạng thái.</p><?php endif; ?>
    </section>
</section>
<dialog class="image-dialog" aria-label="Ảnh yêu cầu">
    <form method="dialog"><button class="button-secondary" type="submit">Đóng</button></form>
    <img alt="Ảnh yêu cầu được phóng to">
</dialog>

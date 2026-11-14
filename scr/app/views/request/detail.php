<section class="request-detail">
    <header class="request-detail-header">
        <div>
            <h1>Yêu cầu <?= htmlspecialchars($request['code'], ENT_QUOTES, 'UTF-8') ?></h1>
            <p><?= htmlspecialchars($request['title'], ENT_QUOTES, 'UTF-8') ?></p>
            <span class="badge badge-status badge-<?= htmlspecialchars($request['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabels[$request['status']] ?? $request['status'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <a class="button button-outline" href="/DoAn_DienLanh/scr/request/mine">Về danh sách</a>
    </header>

    <div class="request-detail-layout">
        <div class="request-detail-primary">
            <section class="card request-info-card">
                <h2>Thông tin yêu cầu</h2>
                <div class="request-info-grid">
        <div><strong>Dịch vụ</strong><span><?= htmlspecialchars($request['service_name'], ENT_QUOTES, 'UTF-8') ?></span></div>
        <div><strong>Tiêu đề</strong><span><?= htmlspecialchars($request['title'], ENT_QUOTES, 'UTF-8') ?></span></div>
        <div><strong>Thiết bị</strong><span><?= htmlspecialchars(trim(($request['device_name'] ?? '') . (($request['device_brand'] ?? '') !== '' ? ' - ' . $request['device_brand'] : '') . (($request['device_model'] ?? '') !== '' ? ' ' . $request['device_model'] : '')) ?: 'Chưa khai báo', ENT_QUOTES, 'UTF-8') ?></span></div>
        <?php if (!empty($request['category_name'])): ?>
            <div><strong>Nhóm thiết bị</strong><span><?= htmlspecialchars($request['category_name'], ENT_QUOTES, 'UTF-8') ?></span></div>
        <?php endif; ?>
        <div><strong>Địa chỉ</strong><span><?= nl2br(htmlspecialchars($request['address'], ENT_QUOTES, 'UTF-8')) ?></span></div>
        <div><strong>Thời gian mong muốn</strong><span><?= htmlspecialchars(date('d/m/Y H:i', strtotime($request['preferred_time'])), ENT_QUOTES, 'UTF-8') ?></span></div>
        <div><strong>Ngày gửi</strong><span><?= htmlspecialchars(date('d/m/Y H:i', strtotime($request['created_at'])), ENT_QUOTES, 'UTF-8') ?></span></div>
        <?php if (!empty($request['technician_name'])): ?>
            <div><strong>Kỹ thuật viên phụ trách</strong><span><?= htmlspecialchars($request['technician_name'], ENT_QUOTES, 'UTF-8') ?><?= !empty($request['technician_phone']) ? ' - ' . htmlspecialchars($request['technician_phone'], ENT_QUOTES, 'UTF-8') : '' ?></span></div>
        <?php endif; ?>
        <?php if (!empty($request['description'])): ?>
            <div class="request-description"><strong>Mô tả</strong><span><?= nl2br(htmlspecialchars($request['description'], ENT_QUOTES, 'UTF-8')) ?></span></div>
        <?php endif; ?>
                </div>
            </section>

    <?php if (!empty($request['images'])): ?>
        <section class="detail-section card">
            <h2>Ảnh thiết bị</h2>
            <div class="request-gallery">
                <?php foreach ($request['images'] as $image): ?>
                    <button class="gallery-image js-image-modal" type="button" data-image="<?= htmlspecialchars('/DoAn_DienLanh/scr/' . ltrim($image, '/\\'), ENT_QUOTES, 'UTF-8') ?>" aria-label="Phóng to ảnh">
                        <img src="<?= htmlspecialchars('/DoAn_DienLanh/scr/' . ltrim($image, '/\\'), ENT_QUOTES, 'UTF-8') ?>" alt="Ảnh yêu cầu <?= htmlspecialchars($request['code'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
                    </button>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if (!empty($request['quote'])): ?>
        <section class="detail-section card quote-section">
            <h2>Báo giá</h2>
            <p>Trạng thái báo giá:
                <span class="badge badge-quote-<?= htmlspecialchars($request['quote']['status'], ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars(['sent' => 'Đang chờ xác nhận', 'accepted' => 'Đã chấp nhận', 'rejected' => 'Đã từ chối', 'draft' => 'Bản nháp'][$request['quote']['status']] ?? $request['quote']['status'], ENT_QUOTES, 'UTF-8') ?>
                </span>
            </p>
            <?php if (!empty($request['report'])): ?>
                <div class="table-wrap quote-table-wrap">
                    <table class="quote-table">
                        <thead><tr><th>Hạng mục</th><th>Số lượng</th><th>Đơn giá</th><th>Thành tiền</th></tr></thead>
                        <tbody>
                            <tr>
                                <td>Tiền công</td>
                                <td>1</td>
                                <td><?= number_format((float) $request['report']['labor_cost'], 0, ',', '.') ?> ₫</td>
                                <td><?= number_format((float) $request['report']['labor_cost'], 0, ',', '.') ?> ₫</td>
                            </tr>
                            <?php foreach ($request['report_parts'] as $part): ?>
                                <tr>
                                    <td><?= htmlspecialchars($part['name'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars(rtrim(rtrim(number_format((float) $part['qty'], 2, '.', ''), '0'), '.'), ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($part['unit'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= number_format((float) $part['unit_price'], 0, ',', '.') ?> ₫</td>
                                    <td><?= number_format((float) $part['qty'] * (float) $part['unit_price'], 0, ',', '.') ?> ₫</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot><tr><th colspan="3">Tổng tiền</th><th><?= number_format((float) $request['quote']['total_amount'], 0, ',', '.') ?> ₫</th></tr></tfoot>
                    </table>
                </div>
                <?php if (!empty($request['report']['cause'])): ?><p><strong>Nguyên nhân hư hỏng:</strong> <?= nl2br(htmlspecialchars($request['report']['cause'], ENT_QUOTES, 'UTF-8')) ?></p><?php endif; ?>
                <?php if (!empty($request['report']['solution'])): ?><p><strong>Phương án xử lý:</strong> <?= nl2br(htmlspecialchars($request['report']['solution'], ENT_QUOTES, 'UTF-8')) ?></p><?php endif; ?>
                <?php if ((int) $request['report']['warranty_months'] > 0): ?><p><strong>Bảo hành:</strong> <?= (int) $request['report']['warranty_months'] ?> tháng</p><?php endif; ?>
            <?php endif; ?>
            <p class="quote-created-at">Ngày báo giá: <?= htmlspecialchars(date('d/m/Y H:i', strtotime($request['quote']['created_at'])), ENT_QUOTES, 'UTF-8') ?></p>
            <?php if ($request['status'] === 'quoted' && $request['quote']['status'] === 'sent'): ?>
                <div class="quote-actions">
                    <form method="post" action="/DoAn_DienLanh/scr/request/acceptQuote/<?= (int) $request['id'] ?>" data-confirm="Xác nhận chấp nhận báo giá này?">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit">Chấp nhận báo giá</button>
                    </form>
                    <form method="post" action="/DoAn_DienLanh/scr/request/rejectQuote/<?= (int) $request['id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                        <div class="form-group">
                            <label for="quote-reject-reason">Lý do từ chối</label>
                            <textarea id="quote-reject-reason" name="reason" rows="2" maxlength="1000" required></textarea>
                        </div>
                        <button class="button-danger" type="submit">Từ chối báo giá</button>
                    </form>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($request['status'] === 'completed' && $warrantyExpiresAt !== null): ?>
        <section class="card warranty-card">
            <h2>Thông tin bảo hành</h2>
            <p><strong>Mã bảo hành:</strong> <?= htmlspecialchars($warrantyCode, ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>Ngày hết hạn:</strong> <?= htmlspecialchars(date('d/m/Y', strtotime($warrantyExpiresAt)), ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>Thời hạn:</strong> <?= (int) $request['report']['warranty_months'] ?> tháng</p>
        </section>
    <?php endif; ?>

    <?php if (!empty($completionConfirmedAt)): ?>
        <section class="card completion-confirmed-card">
            <h2>Đã xác nhận hoàn thành</h2>
            <p>Quý khách đã xác nhận hoàn thành dịch vụ lúc <?= htmlspecialchars(date('d/m/Y H:i', strtotime($completionConfirmedAt)), ENT_QUOTES, 'UTF-8') ?>.</p>
        </section>
    <?php elseif ($request['status'] === 'completed'): ?>
        <form class="card completion-confirmation-card" method="post" action="/DoAn_DienLanh/scr/request/confirmCompletion/<?= (int) $request['id'] ?>" data-confirm="Xác nhận dịch vụ đã hoàn thành?">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <h2>Xác nhận hoàn thành dịch vụ</h2>
            <p>Vui lòng xác nhận sau khi đã kiểm tra kết quả sửa chữa.</p>
            <button type="submit">Xác nhận hoàn thành</button>
        </form>
    <?php endif; ?>

    <?php if ($request['status'] === 'completed' && !empty($completionConfirmedAt) && empty($request['review'])): ?>
        <form class="card review-form" method="post" action="/DoAn_DienLanh/scr/request/review/<?= (int) $request['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <h2>Đánh giá dịch vụ</h2>
            <fieldset class="rating-fieldset">
                <legend>Đánh giá từ 1 đến 5</legend>
                <div class="star-rating" role="radiogroup" aria-label="Đánh giá từ 1 đến 5 sao">
                    <?php for ($star = 1; $star <= 5; $star++): ?>
                        <input id="rating-<?= $star ?>" type="radio" name="rating" value="<?= $star ?>" required>
                        <label for="rating-<?= $star ?>" data-rating="<?= $star ?>"><?= $star ?> - <?= [1 => 'Rất tệ', 2 => 'Chưa tốt', 3 => 'Bình thường', 4 => 'Tốt', 5 => 'Rất tốt'][$star] ?></label>
                    <?php endfor; ?>
                </div>
                <p class="rating-value" data-rating-value>Chưa chọn</p>
            </fieldset>
            <div class="form-group"><label for="review-comment">Nhận xét</label><textarea id="review-comment" name="comment" rows="3" maxlength="5000"></textarea></div>
            <button type="submit">Gửi đánh giá</button>
        </form>
    <?php elseif (!empty($request['review'])): ?>
        <section class="card submitted-review">
            <h2>Đánh giá của Quý khách</h2>
            <p class="review-stars" aria-label="<?= (int) $request['review']['rating'] ?> trên 5"><?= (int) $request['review']['rating'] ?>/5</p>
            <?php if (!empty($request['review']['comment'])): ?><p><?= nl2br(htmlspecialchars($request['review']['comment'], ENT_QUOTES, 'UTF-8')) ?></p><?php endif; ?>
            <time><?= htmlspecialchars(date('d/m/Y H:i', strtotime($request['review']['created_at'])), ENT_QUOTES, 'UTF-8') ?></time>
        </section>
    <?php endif; ?>

            <section class="detail-section card request-timeline-card">
                <h2>Lịch sử trạng thái</h2>
        <?php if (!empty($request['logs'])): ?>
            <ol class="request-timeline">
                <?php foreach ($request['logs'] as $log): ?>
                    <li>
                        <div class="timeline-content">
                            <span class="badge badge-status badge-<?= htmlspecialchars($log['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabels[$log['status']] ?? $log['status'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php if (!empty($log['note'])): ?><p><?= nl2br(htmlspecialchars($log['note'], ENT_QUOTES, 'UTF-8')) ?></p><?php endif; ?>
                            <time><?= htmlspecialchars(date('d/m/Y H:i', strtotime($log['created_at'])), ENT_QUOTES, 'UTF-8') ?></time>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php else: ?>
            <p class="empty-state">Chưa có lịch sử trạng thái.</p>
        <?php endif; ?>
            </section>

        </div>
        <aside class="card request-management-card">
            <h2>Quản lý yêu cầu</h2>
            <div><span>Mức ưu tiên</span><strong><?= htmlspecialchars($priorityLabels[$request['priority']] ?? $request['priority'], ENT_QUOTES, 'UTF-8') ?></strong></div>
            <div><span>Trạng thái</span><strong><?= htmlspecialchars($statusLabels[$request['status']] ?? $request['status'], ENT_QUOTES, 'UTF-8') ?></strong></div>
            <?php if ($request['status'] === 'pending'): ?>
                <form method="post" action="/DoAn_DienLanh/scr/request/cancel/<?= (int) $request['id'] ?>" data-confirm="Quý khách xác nhận hủy yêu cầu này?">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
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

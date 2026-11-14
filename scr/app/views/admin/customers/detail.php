<section>
    <div class="page-head">
        <div><h1>Khách hàng: <?= htmlspecialchars($customer['full_name'], ENT_QUOTES, 'UTF-8') ?></h1><p>Thông tin tài khoản, thiết bị và lịch sử sửa chữa.</p></div>
        <a class="button button-secondary" href="<?= htmlspecialchars(url('/customer/index'), ENT_QUOTES, 'UTF-8') ?>">Về danh sách</a>
    </div>
    <div class="card customer-profile info-grid">
        <p><strong>Email:</strong> <?= htmlspecialchars($customer['email'], ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>Số điện thoại:</strong> <?= htmlspecialchars($customer['phone'] ?? 'Chưa cập nhật', ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>Địa chỉ:</strong> <?= htmlspecialchars($customer['address'] ?? 'Chưa cập nhật', ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>Ngày đăng ký:</strong> <?= htmlspecialchars(date('d/m/Y', strtotime($customer['created_at'])), ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    <form class="card customer-reset-password" method="post" action="<?= htmlspecialchars(url('/customer/resetPassword/' . (int) $customer['id']), ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
        <h2>Đặt lại mật khẩu khách hàng</h2>
        <div class="form-group">
            <label for="customer-new-password">Mật khẩu mới (ít nhất 8 ký tự)</label>
            <input id="customer-new-password" type="password" name="password" minlength="8" maxlength="255" required>
        </div>
        <button class="button-secondary" type="submit">Đặt lại mật khẩu</button>
    </form>

    <div class="table-card">
        <div class="table-card-head"><h2>Thiết bị</h2></div>
        <table>
            <thead><tr><th>Nhóm</th><th>Thiết bị</th><th>Hãng / Model</th><th>Lịch sử</th></tr></thead>
            <tbody>
            <?php foreach ($customer['devices'] as $device): ?>
                <tr>
                    <td><?= htmlspecialchars($device['category_name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($device['name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars(trim(($device['brand'] ?? '') . ' ' . ($device['model'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><a class="button button-secondary" href="<?= htmlspecialchars(url('/customer/device/' . (int) $device['id']), ENT_QUOTES, 'UTF-8') ?>">Xem lịch sử</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($customer['devices'])): ?><tr><td colspan="4" class="empty-state">Khách hàng chưa đăng ký thiết bị.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="table-card">
        <div class="table-card-head"><h2>Lịch sử sửa chữa</h2></div>
        <table>
            <thead><tr><th>Mã</th><th>Dịch vụ</th><th>Thiết bị</th><th>Trạng thái</th><th>Ngày</th><th>Chi phí</th><th>Bảo hành</th></tr></thead>
            <tbody>
            <?php foreach ($customer['requests'] as $request): ?>
                <?php
                $statusLabels = [
                    'pending' => 'Chờ tiếp nhận', 'received' => 'Đã tiếp nhận',
                    'checking' => 'Đang kiểm tra', 'waiting_parts' => 'Chờ linh kiện',
                    'repairing' => 'Đang sửa chữa', 'quoted' => 'Đã báo giá',
                    'completed' => 'Hoàn thành', 'cancelled' => 'Đã hủy',
                ];
                $warrantyEnd = null;
                if ($request['completed_at'] && (int) $request['warranty_months'] > 0) {
                    $warrantyEnd = strtotime('+' . (int) $request['warranty_months'] . ' months', strtotime($request['completed_at']));
                }
                ?>
                <tr>
                    <td><a href="<?= htmlspecialchars(url('/admin/requestDetail/' . (int) $request['id']), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($request['code'], ENT_QUOTES, 'UTF-8') ?></a></td>
                    <td><?= htmlspecialchars($request['service_name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <?php if ($request['device_id']): ?>
                            <a href="<?= htmlspecialchars(url('/customer/device/' . (int) $request['device_id']), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(trim(($request['device_brand'] ?? '') . ' ' . ($request['device_name'] ?? '') . ' ' . ($request['device_model'] ?? '')), ENT_QUOTES, 'UTF-8') ?></a>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td><span class="badge badge-status badge-<?= htmlspecialchars($request['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabels[$request['status']] ?? $request['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td><?= htmlspecialchars(date('d/m/Y', strtotime($request['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= $request['cost'] !== null ? number_format((float) $request['cost'], 0, ',', '.') . ' ₫' : '—' ?></td>
                    <td>
                        <?php if ($warrantyEnd): ?>
                            <?= $warrantyEnd >= time() ? 'Còn hạn đến ' : 'Hết hạn ' ?><?= htmlspecialchars(date('d/m/Y', $warrantyEnd), ENT_QUOTES, 'UTF-8') ?>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($customer['requests'])): ?><tr><td colspan="7" class="empty-state">Khách hàng chưa có yêu cầu sửa chữa.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php require __DIR__ . '/../../partials/pagination.php'; ?>
</section>

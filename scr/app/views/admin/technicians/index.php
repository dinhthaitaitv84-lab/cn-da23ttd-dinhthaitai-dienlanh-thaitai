<div class="page-head">
    <div><h1>Quản lý kỹ thuật viên</h1><p>Quản lý hồ sơ, trạng thái làm việc và tài khoản của đội ngũ.</p></div>
    <a class="button" href="/DoAn_DienLanh/scr/technician/create">Thêm kỹ thuật viên</a>
</div>
<form class="filter-card technician-search" method="get" action="/DoAn_DienLanh/scr/technician/index">
    <div class="form-group">
        <label for="search">Tìm theo tên</label>
        <div class="search-input"><?= icon('search') ?><input id="search" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" maxlength="100"></div>
    </div>
    <button type="submit">Tìm</button>
</form>
<div class="table-card">
    <div class="table-card-head"><h2>Đội ngũ kỹ thuật viên</h2><span><?= (int) ($total ?? count($technicians)) ?> kỹ thuật viên</span></div>
    <table>
        <thead><tr><th>Kỹ thuật viên / Liên hệ</th><th>Chuyên môn</th><th>Kinh nghiệm</th><th>Làm việc</th><th>Tài khoản</th><th class="text-center">Đang xử lý</th><th>Thao tác</th></tr></thead>
        <tbody>
        <?php foreach ($technicians as $technician): ?>
            <tr>
                <td><strong><?= htmlspecialchars($technician['full_name'], ENT_QUOTES, 'UTF-8') ?></strong><span class="table-subtext"><?= htmlspecialchars($technician['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?></span><span class="table-subtext"><?= htmlspecialchars($technician['email'], ENT_QUOTES, 'UTF-8') ?></span></td>
                <td><?= htmlspecialchars($technician['specialty'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= (int) $technician['experience_years'] ?> năm</td>
                <td><span class="badge badge-tech-<?= htmlspecialchars($technician['technician_status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(['available' => 'Sẵn sàng', 'busy' => 'Bận', 'off' => 'Ngừng'][ $technician['technician_status']] ?? $technician['technician_status'], ENT_QUOTES, 'UTF-8') ?></span></td>
                <td><span class="badge <?= (int) $technician['account_status'] === 1 ? 'badge-account-active' : 'badge-account-locked' ?>"><?= (int) $technician['account_status'] === 1 ? 'Hoạt động' : 'Đã khóa' ?></span></td>
                <td class="text-center"><strong><?= (int) $technician['active_requests'] ?></strong></td>
                <td class="table-actions technician-actions text-actions">
                    <a class="text-action" href="/DoAn_DienLanh/scr/technician/edit/<?= (int) $technician['id'] ?>">Sửa</a>
                    <form method="post" action="/DoAn_DienLanh/scr/technician/accountStatus/<?= (int) $technician['id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="status" value="<?= (int) $technician['account_status'] === 1 ? 0 : 1 ?>">
                        <button class="text-action text-action-danger" type="submit"><?= (int) $technician['account_status'] === 1 ? 'Khóa' : 'Mở' ?></button>
                    </form>
                    <form method="post" action="/DoAn_DienLanh/scr/technician/delete/<?= (int) $technician['id'] ?>" data-confirm="Xóa kỹ thuật viên này? Chỉ xóa được khi chưa được phân công yêu cầu.">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="page" value="<?= (int) $page ?>">
                        <input type="hidden" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                        <button class="text-action text-action-danger" type="submit">Xóa</button>
                    </form>
                    <button class="button button-secondary technician-status-trigger" type="button" aria-expanded="false" aria-controls="technician-status-card"
                        data-technician-status-trigger
                        data-technician-id="<?= (int) $technician['id'] ?>"
                        data-technician-name="<?= htmlspecialchars($technician['full_name'], ENT_QUOTES, 'UTF-8') ?>"
                        data-current-status="<?= htmlspecialchars($technician['technician_status'], ENT_QUOTES, 'UTF-8') ?>">Trạng thái</button>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($technicians)): ?><tr><td colspan="7" class="empty-state">Không tìm thấy kỹ thuật viên.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<section class="card technician-status-card" id="technician-status-card" data-technician-status-panel hidden>
    <div class="technician-status-heading">
        <div>
            <h2>Cập nhật trạng thái · <span data-technician-status-name></span></h2>
            <p>Chọn trạng thái làm việc và lưu thay đổi.</p>
        </div>
        <button class="button button-secondary" type="button" data-close-technician-status>Đóng</button>
    </div>
    <form class="technician-status-form" method="post" action="/DoAn_DienLanh/scr/technician/status/" data-action-prefix="/DoAn_DienLanh/scr/technician/status/">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
        <fieldset class="state-options">
            <legend>Trạng thái làm việc</legend>
            <?php foreach (['available' => 'Sẵn sàng', 'busy' => 'Bận', 'off' => 'Ngừng'] as $value => $label): ?>
                <label class="state-option">
                    <input type="radio" name="status" value="<?= $value ?>" required>
                    <span><?= $label ?></span>
                </label>
            <?php endforeach; ?>
        </fieldset>
        <button type="submit">Lưu trạng thái</button>
    </form>
</section>
<?php require __DIR__ . '/../../partials/pagination.php'; ?>

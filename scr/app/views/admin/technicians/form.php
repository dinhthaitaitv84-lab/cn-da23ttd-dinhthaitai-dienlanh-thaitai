<div class="page-head">
    <div><h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1><p>Cập nhật hồ sơ, chuyên môn và trạng thái làm việc.</p></div>
    <a class="button button-secondary" href="<?= htmlspecialchars(url('/technician/index'), ENT_QUOTES, 'UTF-8') ?>">Về danh sách</a>
</div>
<div class="admin-form-layout">
    <section class="card admin-form">
        <div class="admin-form-title"><h2>Thông tin cơ bản</h2></div>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-group"><label for="full_name">Họ tên</label><input id="full_name" name="full_name" maxlength="150" value="<?= htmlspecialchars($technician['full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required></div>
            <div class="form-group"><label for="email">Email</label><input id="email" name="email" type="email" maxlength="191" value="<?= htmlspecialchars($technician['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required></div>
            <div class="form-group"><label for="phone">SĐT</label><input id="phone" name="phone" maxlength="30" value="<?= htmlspecialchars($technician['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="form-group"><label for="specialty">Chuyên môn</label><input id="specialty" name="specialty" maxlength="150" value="<?= htmlspecialchars($technician['specialty'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required></div>
            <div class="form-group"><label for="experience_years">Kinh nghiệm (năm)</label><input id="experience_years" name="experience_years" type="number" min="0" max="65535" value="<?= htmlspecialchars((string) ($technician['experience_years'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>" required></div>
            <div class="form-group">
                <label for="status">Trạng thái</label>
                <select id="status" name="status" required>
                    <?php foreach (['available' => 'Sẵn sàng', 'busy' => 'Bận', 'off' => 'Ngừng'] as $value => $label): ?>
                        <option value="<?= $value ?>" <?= ($technician['technician_status'] ?? 'available') === $value ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($technician === null): ?>
                <div class="form-group"><label for="password">Mật khẩu (ít nhất 8 ký tự)</label><input id="password" name="password" type="password" minlength="8" maxlength="255" required></div>
            <?php endif; ?>
            <div class="form-actions">
                <button type="submit">Lưu</button>
                <a class="button button-secondary" href="<?= htmlspecialchars(url('/technician/index'), ENT_QUOTES, 'UTF-8') ?>">Hủy</a>
            </div>
        </form>
        <?php if ($technician !== null): ?>
            <form class="reset-password-form" method="post" action="<?= htmlspecialchars(url('/technician/resetPassword/' . (int) $technician['id']), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                <div class="form-group"><label for="password">Đặt lại mật khẩu</label><input id="password" name="password" type="password" minlength="8" maxlength="255" required></div>
                <button class="button-secondary" type="submit">Đặt lại mật khẩu</button>
            </form>
        <?php endif; ?>
    </section>
    <aside class="admin-form-help">
        <h2>Tài khoản kỹ thuật viên</h2>
        <p><?= $technician === null ? 'Tạo hồ sơ và tài khoản cho thành viên mới.' : 'Cập nhật thông tin liên hệ, chuyên môn và kinh nghiệm.' ?></p>
        <p>Đặt mật khẩu tối thiểu 8 ký tự và chọn trạng thái làm việc phù hợp để điều phối lịch.</p>
    </aside>
</div>

<section class="profile-page-content">
    <header class="profile-page-heading">
        <h1>Hồ sơ cá nhân</h1>
        <p>Cập nhật thông tin liên hệ và bảo mật tài khoản của bạn.</p>
    </header>
    <div class="profile-layout">
        <div class="profile-main-column">
            <section class="card profile-card profile-information-card">
                <h2>Thông tin cá nhân</h2>
                <form method="post" action="/DoAn_DienLanh/scr/profile/update">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                    <div class="form-group">
                        <label for="profile-full-name">Họ tên</label>
                        <input id="profile-full-name" name="full_name" maxlength="150" required value="<?= htmlspecialchars($profile['full_name'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="form-group">
                        <label for="profile-phone">Số điện thoại</label>
                        <input id="profile-phone" name="phone" maxlength="30" value="<?= htmlspecialchars($profile['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="form-group profile-field-full">
                        <label for="profile-email">Email (không thể thay đổi)</label>
                        <input id="profile-email" type="email" readonly value="<?= htmlspecialchars($profile['email'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="form-group profile-field-full">
                        <label for="profile-address">Địa chỉ</label>
                        <textarea id="profile-address" name="address" maxlength="500" rows="3"><?= htmlspecialchars($profile['address'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        <small>Địa chỉ cần thuộc <?= htmlspecialchars(str_replace(' và ', ' hoặc ', SERVICE_AREA_TEXT), ENT_QUOTES, 'UTF-8') ?> (ghi rõ tên tỉnh).</small>
                    </div>
                    <div class="profile-form-actions">
                        <button type="submit">Lưu hồ sơ</button>
                    </div>
                </form>
            </section>
            <section class="card profile-card profile-password-card">
                <h2>Đổi mật khẩu</h2>
                <form method="post" action="/DoAn_DienLanh/scr/profile/changePassword">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                    <div class="profile-password-fields">
                        <div class="form-group">
                            <label for="current-password">Mật khẩu hiện tại</label>
                            <input id="current-password" name="current_password" type="password" autocomplete="current-password" required>
                        </div>
                        <div class="form-group">
                            <label for="new-password">Mật khẩu mới (ít nhất 6 ký tự)</label>
                            <input id="new-password" name="password" type="password" minlength="6" maxlength="255" autocomplete="new-password" required>
                        </div>
                        <div class="form-group">
                            <label for="password-confirmation">Xác nhận mật khẩu mới</label>
                            <input id="password-confirmation" name="password_confirmation" type="password" minlength="6" maxlength="255" autocomplete="new-password" required>
                        </div>
                    </div>
                    <small class="profile-password-hint">Mật khẩu mới cần có ít nhất 6 ký tự.</small>
                    <div class="profile-form-actions">
                        <button class="button-secondary" type="submit">Đổi mật khẩu</button>
                    </div>
                </form>
            </section>
        </div>
        <aside class="profile-account-card">
            <h2>Tài khoản của bạn</h2>
            <strong><?= htmlspecialchars($profile['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
            <span class="profile-account-email"><?= htmlspecialchars($profile['email'], ENT_QUOTES, 'UTF-8') ?></span>
            <p class="profile-account-note">Email không thể thay đổi.</p>
            <p class="profile-account-area">Địa chỉ phục vụ cần ghi rõ tỉnh <?= htmlspecialchars(str_replace(' và ', ' hoặc ', SERVICE_AREA_TEXT), ENT_QUOTES, 'UTF-8') ?>.</p>
        </aside>
    </div>
</section>

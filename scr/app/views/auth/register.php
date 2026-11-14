<section class="auth-layout auth-layout-register">
    <aside class="auth-promo auth-promo-register">
        <p class="auth-promo-eyebrow">BẮT ĐẦU CÙNG CHÚNG TÔI</p>
        <h1>Chăm sóc thiết bị<br>tại nhà dễ dàng hơn.</h1>
        <p class="auth-promo-intro">Tạo tài khoản để gửi yêu cầu, lưu thiết bị và theo dõi lịch sử sửa chữa.</p>
        <div class="auth-promo-contact">
            <div>
                <span>Khu vực phục vụ</span>
                <strong><?= htmlspecialchars(SERVICE_AREA_TEXT, ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <div>
                <span>Hotline hỗ trợ</span>
                <strong><?= htmlspecialchars(CONTACT_HOTLINE, ENT_QUOTES, 'UTF-8') ?></strong>
                <small><?= htmlspecialchars(CONTACT_HOURS, ENT_QUOTES, 'UTF-8') ?></small>
            </div>
        </div>
    </aside>

    <section class="card auth-card">
        <h1>Đăng ký tài khoản</h1>
        <p class="auth-description">Điền thông tin để bắt đầu sử dụng dịch vụ.</p>

        <?php if (!empty($error)): ?>
            <div class="flash flash-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="post" action="<?= htmlspecialchars(url('/auth/register'), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="auth-field-grid">
                <div class="form-group">
                    <label for="fullname">Họ tên</label>
                    <input id="fullname" name="fullname" type="text" autocomplete="name" maxlength="150" placeholder="Nhập họ tên" required>
                </div>
                <div class="form-group">
                    <label for="phone">Số điện thoại</label>
                    <input id="phone" name="phone" type="tel" autocomplete="tel" maxlength="30" placeholder="Nhập số điện thoại" required>
                </div>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" autocomplete="email" maxlength="191" placeholder="Nhập địa chỉ email" required>
            </div>
            <div class="form-group">
                <label for="address">Địa chỉ</label>
                <textarea id="address" name="address" autocomplete="street-address" maxlength="500" rows="3" placeholder="Nhập địa chỉ của bạn" required></textarea>
                <small>Ghi rõ tỉnh: <?= htmlspecialchars(str_replace(' và ', ' hoặc ', SERVICE_AREA_TEXT), ENT_QUOTES, 'UTF-8') ?>.</small>
            </div>
            <div class="form-group">
                <label for="password">Mật khẩu</label>
                <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" maxlength="255" placeholder="Tạo mật khẩu" required>
            </div>
            <button type="submit">Đăng ký</button>
        </form>

        <p class="auth-switch">Đã có tài khoản? <a href="<?= htmlspecialchars(url('/auth/login'), ENT_QUOTES, 'UTF-8') ?>">Đăng nhập</a></p>
    </section>
</section>
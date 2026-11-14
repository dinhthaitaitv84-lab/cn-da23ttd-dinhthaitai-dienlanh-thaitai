<section class="auth-layout auth-layout-login">
    <aside class="auth-promo auth-promo-login">
        <p class="auth-promo-eyebrow">ĐIỆN LẠNH TẠI NHÀ</p>
        <h1>Mọi yêu cầu,<br>một nơi theo dõi.</h1>
        <p class="auth-promo-intro">Đăng nhập để gửi yêu cầu và cập nhật tiến độ sửa chữa của bạn.</p>
        <div class="auth-promo-details">
            <div><strong>Gửi yêu cầu sửa chữa</strong><span>Chọn dịch vụ và mô tả tình trạng thiết bị.</span></div>
            <div><strong>Theo dõi tiến độ</strong><span>Xem trạng thái và lịch sử xử lý yêu cầu.</span></div>
            <div><strong>Lưu thiết bị của bạn</strong><span>Dùng lại thông tin cho lần sửa chữa tiếp theo.</span></div>
        </div>
    </aside>

    <section class="card auth-card">
        <h1>Đăng nhập</h1>
        <p class="auth-description">Chào mừng bạn quay lại.</p>

        <?php if (!empty($error)): ?>
            <div class="flash flash-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="post" action="/DoAn_DienLanh/scr/auth/login">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-group">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" autocomplete="email" placeholder="Nhập địa chỉ email" required>
            </div>

            <div class="form-group">
                <label for="password">Mật khẩu</label>
                <input id="password" name="password" type="password" autocomplete="current-password" placeholder="Nhập mật khẩu" required>
            </div>

            <button type="submit">Đăng nhập</button>
        </form>

        <div class="auth-help">
            <strong>Quên mật khẩu?</strong>
            <p>Liên hệ hotline <a href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', CONTACT_HOTLINE), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(CONTACT_HOTLINE, ENT_QUOTES, 'UTF-8') ?></a> để được cấp lại.</p>
        </div>
        <p class="auth-switch">Chưa có tài khoản? <a href="/DoAn_DienLanh/scr/auth/register">Đăng ký</a></p>
    </section>
</section>
    </main>
    <?php if ($showMobileRequestCta): ?>
        <div class="mobile-request-cta">
            <a class="btn-cta" href="<?= htmlspecialchars($mobileRequestUrl, ENT_QUOTES, 'UTF-8') ?>">Gửi yêu cầu sửa chữa</a>
        </div>
    <?php endif; ?>
    <?php $footerCategories = $this->model('CategoryModel')->getActive(); ?>
    <footer class="site-footer">
        <div class="site-footer-grid">
            <section class="site-footer-contact">
                <h2>Thông tin liên hệ</h2>
                <p>Địa chỉ: <?= htmlspecialchars(CONTACT_ADDRESS, ENT_QUOTES, 'UTF-8') ?></p>
                <p>Hotline: <a href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', CONTACT_HOTLINE), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(CONTACT_HOTLINE, ENT_QUOTES, 'UTF-8') ?></a></p>
                <p>Email: <a href="mailto:<?= htmlspecialchars(CONTACT_EMAIL, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(CONTACT_EMAIL, ENT_QUOTES, 'UTF-8') ?></a></p>
                <p>Giờ làm việc: <?= htmlspecialchars(CONTACT_HOURS, ENT_QUOTES, 'UTF-8') ?></p>
                <p>Khu vực phục vụ: <?= htmlspecialchars(SERVICE_AREA_TEXT, ENT_QUOTES, 'UTF-8') ?></p>
            </section>
            <section class="site-footer-services">
                <h2>Dịch vụ</h2>
                <?php foreach ($footerCategories as $footerCategory): ?>
                    <p><a href="/DoAn_DienLanh/scr/service?category_id=<?= (int) $footerCategory['id'] ?>"><?= htmlspecialchars($footerCategory['name'], ENT_QUOTES, 'UTF-8') ?></a></p>
                <?php endforeach; ?>
                <?php if (empty($footerCategories)): ?><p><a href="/DoAn_DienLanh/scr/service">Xem dịch vụ</a></p><?php endif; ?>
            </section>
            <section class="site-footer-account">
                <h2>Tài khoản</h2>
                <?php if ($userRole === 'customer'): ?>
                    <p><a href="/DoAn_DienLanh/scr/request/mine">Yêu cầu của tôi</a></p>
                    <p><a href="/DoAn_DienLanh/scr/device/index">Thiết bị của tôi</a></p>
                    <p><a href="/DoAn_DienLanh/scr/profile/index">Hồ sơ cá nhân</a></p>
                    <p><a href="/DoAn_DienLanh/scr/auth/logout">Đăng xuất</a></p>
                <?php elseif (in_array($userRole, ['admin', 'technician'], true)): ?>
                    <p><a href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8') ?>">Quản trị</a></p>
                    <p><a href="/DoAn_DienLanh/scr/auth/logout">Đăng xuất</a></p>
                <?php else: ?>
                    <p><a href="/DoAn_DienLanh/scr/auth/login">Đăng nhập</a></p>
                    <p><a href="/DoAn_DienLanh/scr/auth/register">Đăng ký</a></p>
                <?php endif; ?>
            </section>
        </div>
        <div class="site-footer-bottom">
            <p class="site-footer-copyright">© <?= date('Y') ?> Điện Lạnh Tại Nhà</p>
            <p>Tiếp nhận yêu cầu trong giờ làm việc</p>
        </div>
    </footer>
<script src="/DoAn_DienLanh/scr/public/js/app.js" defer></script>
<script src="/DoAn_DienLanh/scr/public/js/charts.js" defer></script>
</body>
</html>

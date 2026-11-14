    </main>
    <?php if ($userRole === 'customer'): ?>
        <section class="support-chat-widget"
            data-support-widget
            data-base-url="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>"
            data-csrf-token="<?= htmlspecialchars($this->csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="support-chat-panel" id="support-chat-panel" data-chat-panel hidden>
                <header class="support-widget-heading">
                    <span class="support-widget-mark" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 11.5a7.5 7.5 0 0 1-7.5 7.5H6l-3 2v-6a7.5 7.5 0 1 1 17-3.5Z"/><path d="M8 11h8M8 14h5"/></svg>
                    </span>
                    <span class="support-widget-title">
                        <strong>Chăm sóc khách hàng</strong>
                        <small data-chat-status>Đang kết nối...</small>
                    </span>
                    <button class="support-widget-close" type="button" aria-label="Đóng cửa sổ chat" data-chat-close>×</button>
                </header>
                <div class="support-chat-messages" data-chat-messages aria-live="polite">
                    <p class="support-chat-welcome">Xin chào! Hãy gửi câu hỏi, chúng tôi sẽ phản hồi sớm nhất.</p>
                </div>
                <p class="support-chat-notice" data-chat-closed-notice hidden>Cuộc trò chuyện đã kết thúc.</p>
                <button class="button button-outline support-new-chat" type="button" data-new-chat hidden>Bắt đầu cuộc trò chuyện mới</button>
                <form class="support-chat-compose" data-chat-form>
                    <label class="sr-only" for="support-customer-message">Tin nhắn của bạn</label>
                    <textarea id="support-customer-message" name="message" rows="2" maxlength="2000" placeholder="Nhập tin nhắn..." required></textarea>
                    <button class="button" type="submit" aria-label="Gửi tin nhắn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
                        <span>Gửi</span>
                    </button>
                </form>
                <p class="support-chat-error" data-chat-error role="status" hidden></p>
            </div>
            <button class="support-chat-toggle" type="button" aria-expanded="false" aria-controls="support-chat-panel" data-chat-open>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 11.5a7.5 7.5 0 0 1-7.5 7.5H6l-3 2v-6a7.5 7.5 0 1 1 17-3.5Z"/><path d="M8 11h8M8 14h5"/></svg>
                <span>Chat hỗ trợ</span>
            </button>
        </section>
    <?php endif; ?>
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
                    <p><a href="<?= htmlspecialchars(url('/service?category_id=' . (int) $footerCategory['id']), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($footerCategory['name'], ENT_QUOTES, 'UTF-8') ?></a></p>
                <?php endforeach; ?>
                <?php if (empty($footerCategories)): ?><p><a href="<?= htmlspecialchars(url('/service'), ENT_QUOTES, 'UTF-8') ?>">Xem dịch vụ</a></p><?php endif; ?>
            </section>
            <section class="site-footer-account">
                <h2>Tài khoản</h2>
                <?php if ($userRole === 'customer'): ?>
                    <p><a href="<?= htmlspecialchars(url('/request/mine'), ENT_QUOTES, 'UTF-8') ?>">Yêu cầu của tôi</a></p>
                    <p><a href="<?= htmlspecialchars(url('/device/index'), ENT_QUOTES, 'UTF-8') ?>">Thiết bị của tôi</a></p>
                    <p><a href="<?= htmlspecialchars(url('/profile/index'), ENT_QUOTES, 'UTF-8') ?>">Hồ sơ cá nhân</a></p>
                    <p><a href="<?= htmlspecialchars(url('/auth/logout'), ENT_QUOTES, 'UTF-8') ?>">Đăng xuất</a></p>
                <?php elseif (in_array($userRole, ['admin', 'technician'], true)): ?>
                    <p><a href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8') ?>">Quản trị</a></p>
                    <p><a href="<?= htmlspecialchars(url('/auth/logout'), ENT_QUOTES, 'UTF-8') ?>">Đăng xuất</a></p>
                <?php else: ?>
                    <p><a href="<?= htmlspecialchars(url('/auth/login'), ENT_QUOTES, 'UTF-8') ?>">Đăng nhập</a></p>
                    <p><a href="<?= htmlspecialchars(url('/auth/register'), ENT_QUOTES, 'UTF-8') ?>">Đăng ký</a></p>
                <?php endif; ?>
            </section>
        </div>
        <div class="site-footer-bottom">
            <p class="site-footer-copyright">© <?= date('Y') ?> Điện Lạnh Tại Nhà</p>
            <p>Tiếp nhận yêu cầu trong giờ làm việc</p>
        </div>
    </footer>
<script src="<?= htmlspecialchars(asset_url('public/js/app.js'), ENT_QUOTES, 'UTF-8') ?>" defer></script>
<script src="<?= htmlspecialchars(asset_url('public/js/charts.js'), ENT_QUOTES, 'UTF-8') ?>" defer></script>
<script src="<?= htmlspecialchars(asset_url('public/js/support-chat.js'), ENT_QUOTES, 'UTF-8') ?>" defer></script>
</body>
</html>

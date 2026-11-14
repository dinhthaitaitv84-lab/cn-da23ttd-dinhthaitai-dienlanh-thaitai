<?php
$categoryServiceNames = [];
foreach ($categoryServices as $categoryService) {
    $categoryId = (int) $categoryService['category_id'];
    if (!isset($categoryServiceNames[$categoryId])) {
        $categoryServiceNames[$categoryId] = [];
    }
    if (count($categoryServiceNames[$categoryId]) < 2) {
        $categoryServiceNames[$categoryId][] = $categoryService['name'];
    }
}
$role = $_SESSION['user']['role'] ?? null;
$isLoggedIn = !empty($_SESSION['user']['id']);
$isCustomer = ($role === 'customer');
$isAdmin = ($role === 'admin');
$isTechnician = ($role === 'technician');
$isStaff = $isAdmin || $isTechnician;
$baseUrl = defined('BASE_URL') ? BASE_URL : '/DoAn_DienLanh/scr';
$requestUrl = $isStaff
    ? $baseUrl . ($isAdmin ? '/admin/dashboard' : '/technician/dashboard')
    : ($isLoggedIn && $isCustomer
        ? $baseUrl . '/request/create'
        : $baseUrl . '/auth/login');
$requestLabel = $isStaff ? 'Vào trang quản trị' : 'Gửi yêu cầu sửa chữa';
$requestButtonClass = $isStaff ? 'button' : 'btn-cta';
$serviceUrl = $baseUrl . '/service';
$shortCustomerName = static function ($name) {
    $parts = preg_split('/\s+/u', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY);
    if (!$parts) {
        return 'Khách hàng';
    }
    if (count($parts) === 1) {
        return $parts[0];
    }
    return $parts[0] . ' ' . mb_substr($parts[1], 0, 1, 'UTF-8') . '.';
};
?>
<section class="home-hero">
    <div class="home-hero-copy">
        <p class="eyebrow">ĐIỆN LẠNH TẠI NHÀ</p>
        <h1>Dịch vụ sửa chữa điện, điện lạnh tại nhà</h1>
        <p>Tiếp nhận yêu cầu, cập nhật tiến độ và thông báo giá trực tuyến.</p>
        <div class="home-hero-actions">
            <a class="<?= $requestButtonClass ?>" href="<?= htmlspecialchars($requestUrl, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($requestLabel, ENT_QUOTES, 'UTF-8') ?></a>
            <a class="button button-outline" href="<?= htmlspecialchars($serviceUrl, ENT_QUOTES, 'UTF-8') ?>">Xem dịch vụ</a>
        </div>
        <p class="home-service-area">Phục vụ tại <?= htmlspecialchars(SERVICE_AREA_TEXT, ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    <aside class="home-contact-card" aria-labelledby="home-contact-heading">
        <h2 id="home-contact-heading">Thông tin tiếp nhận</h2>
        <dl>
            <div><dt>Giờ làm việc</dt><dd><?= htmlspecialchars(CONTACT_HOURS, ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Khu vực phục vụ</dt><dd><?= htmlspecialchars(SERVICE_AREA_TEXT, ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Hotline</dt><dd><a href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', CONTACT_HOTLINE), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(CONTACT_HOTLINE, ENT_QUOTES, 'UTF-8') ?></a></dd></div>
            <div><dt>Email</dt><dd><a href="mailto:<?= htmlspecialchars(CONTACT_EMAIL, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(CONTACT_EMAIL, ENT_QUOTES, 'UTF-8') ?></a></dd></div>
            <div><dt>Thời gian phản hồi</dt><dd>Trong giờ làm việc</dd></div>
        </dl>
        <p>Hiện chỉ nhận yêu cầu tại <?= htmlspecialchars(SERVICE_AREA_TEXT, ENT_QUOTES, 'UTF-8') ?>.</p>
    </aside>
</section>

<section class="home-statistics" aria-label="Thống kê dịch vụ">
    <article class="home-stat-card"><span>Nhóm dịch vụ</span><strong><?= (int) ($statistics['category_count'] ?? 0) ?></strong></article>
    <article class="home-stat-card"><span>Dịch vụ đang hiển thị</span><strong><?= (int) ($statistics['service_count'] ?? 0) ?></strong></article>
    <article class="home-stat-card"><span>Yêu cầu hoàn thành</span><strong><?= (int) ($statistics['completed_count'] ?? 0) ?></strong></article>
    <article class="home-stat-card">
        <span>Điểm đánh giá trung bình</span>
        <strong><?= $statistics['average_rating'] === null ? 'Chưa có' : number_format((float) $statistics['average_rating'], 1, ',', '.') . '/5' ?></strong>
    </article>
</section>

<section class="home-section">
    <div class="section-header-row">
        <div class="section-heading">
            <h2>Nhóm dịch vụ</h2>
            <p>Khám phá các nhóm thiết bị và dịch vụ sẵn có.</p>
        </div>
        <a class="section-header-link" href="<?= htmlspecialchars($serviceUrl, ENT_QUOTES, 'UTF-8') ?>">Xem tất cả dịch vụ</a>
    </div>
    <div class="home-card-grid">
        <?php foreach ($categories as $category): ?>
            <article class="card home-category-card">
                <h3><?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                <?php if (trim((string) ($category['description'] ?? '')) !== ''): ?>
                    <p><?= htmlspecialchars($category['description'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
                <?php if (!empty($categoryServiceNames[(int) $category['id']])): ?>
                    <ul class="home-category-services">
                        <?php foreach ($categoryServiceNames[(int) $category['id']] as $categoryServiceName): ?>
                            <li><?= htmlspecialchars($categoryServiceName, ENT_QUOTES, 'UTF-8') ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <div class="home-category-meta">
                    <span><?= (int) $category['service_count'] ?> dịch vụ</span>
                    <a href="/DoAn_DienLanh/scr/service?category_id=<?= (int) $category['id'] ?>">Xem dịch vụ</a>
                </div>
            </article>
        <?php endforeach; ?>
        <?php if (empty($categories)): ?>
            <p class="empty-state">Chưa có nhóm dịch vụ đang hiển thị.</p>
        <?php endif; ?>
        <aside class="home-consult-card">
            <h3>Cần tư vấn dịch vụ?</h3>
            <p>Liên hệ với chúng tôi để được hỗ trợ chọn dịch vụ phù hợp.</p>
            <a href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', CONTACT_HOTLINE), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(CONTACT_HOTLINE, ENT_QUOTES, 'UTF-8') ?></a>
            <small><?= htmlspecialchars(CONTACT_HOURS, ENT_QUOTES, 'UTF-8') ?></small>
        </aside>
    </div>
</section>

<section class="home-section">
    <div class="section-heading">
        <h2>Dịch vụ thường được yêu cầu</h2>
        <p>Một số dịch vụ được khách hàng quan tâm gần đây.</p>
    </div>
    <div class="home-card-grid">
        <?php foreach ($popularServices as $service): ?>
            <article class="card home-service-card">
                <p class="home-service-category"><?= htmlspecialchars($service['category_name'], ENT_QUOTES, 'UTF-8') ?></p>
                <h3><?= htmlspecialchars($service['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                <?php if (trim((string) ($service['description'] ?? '')) !== ''): ?>
                    <p class="home-service-description"><?= htmlspecialchars($service['description'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
                <p class="home-service-price">Từ <?= number_format((float) $service['base_price'], 0, ',', '.') ?> đ</p>
                <a class="button button-outline" href="<?= $isLoggedIn && $isCustomer
                    ? '/DoAn_DienLanh/scr/request/create?service_id=' . (int) $service['id']
                    : '/DoAn_DienLanh/scr/auth/login' ?>"><?= $isLoggedIn && $isCustomer ? 'Tạo yêu cầu' : 'Đăng nhập để gửi yêu cầu' ?></a>
            </article>
        <?php endforeach; ?>
        <?php if (empty($popularServices)): ?>
            <p class="empty-state">Chưa có dịch vụ đang hiển thị.</p>
        <?php endif; ?>
    </div>
</section>

<section class="home-section process-section">
    <div class="section-heading">
        <h2>Quy trình tiếp nhận</h2>
        <p>Các bước rõ ràng từ lúc gửi yêu cầu đến khi hoàn tất.</p>
    </div>
    <div class="process-grid">
        <article class="process-step"><span>01</span><h3>Gửi yêu cầu</h3><p>Gửi yêu cầu kèm mô tả và hình ảnh thiết bị.</p></article>
        <article class="process-step"><span>02</span><h3>Tiếp nhận và phân công</h3><p>Tiếp nhận, phân loại và phân công kỹ thuật viên.</p></article>
        <article class="process-step"><span>03</span><h3>Kiểm tra và sửa chữa</h3><p>Kỹ thuật viên kiểm tra, báo giá và sửa chữa.</p></article>
        <article class="process-step"><span>04</span><h3>Xác nhận kết quả</h3><p>Xác nhận hoàn thành và gửi đánh giá.</p></article>
    </div>
</section>

<section class="home-section">
    <div class="section-heading">
        <h2>Cam kết dịch vụ</h2>
        <p>Thông tin chi phí, bảo hành và tiến độ được trao đổi minh bạch.</p>
    </div>
    <div class="home-commitment-grid">
        <article class="card home-commitment-card"><h3>Báo giá trước khi sửa chữa</h3><p>Chi phí được thông tin trước khi tiến hành sửa chữa.</p></article>
        <article class="card home-commitment-card"><h3>Bảo hành theo biên bản</h3><p>Thời hạn bảo hành được ghi trong biên bản sửa chữa.</p></article>
        <article class="card home-commitment-card"><h3>Theo dõi tiến độ</h3><p>Quý khách có thể xem trạng thái yêu cầu trực tuyến.</p></article>
    </div>
</section>

<?php if (!empty($recentReviews)): ?>
    <section class="home-section">
        <div class="section-heading">
            <h2>Đánh giá gần đây</h2>
            <p>Chia sẻ từ khách hàng sau khi sử dụng dịch vụ.</p>
        </div>
        <div class="home-card-grid home-review-grid">
            <?php foreach ($recentReviews as $review): ?>
                <article class="card home-review-card">
                    <div class="home-review-heading">
                        <strong><?= htmlspecialchars($shortCustomerName($review['customer_name']), ENT_QUOTES, 'UTF-8') ?></strong>
                        <span class="home-review-rating"><?= (int) $review['rating'] ?>/5</span>
                    </div>
                    <?php if (trim((string) ($review['comment'] ?? '')) !== ''): ?>
                        <p><?= nl2br(htmlspecialchars($review['comment'], ENT_QUOTES, 'UTF-8')) ?></p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<section class="home-cta">
    <div>
        <h2>Sẵn sàng đặt lịch sửa chữa?</h2>
        <p>Đăng nhập để gửi yêu cầu và theo dõi tiến độ sửa chữa.</p>
    </div>
    <a class="button button-light" href="<?= htmlspecialchars($requestUrl, ENT_QUOTES, 'UTF-8') ?>">Gửi yêu cầu sửa chữa</a>
</section>

<section class="service-page">
    <div class="section-heading">
        <h1>Dịch vụ sửa chữa tại nhà</h1>
        <p>Chọn nhóm thiết bị hoặc tìm tên dịch vụ phù hợp.</p>
    </div>
    <?php
    $groupedServices = [];
    foreach ($services as $service) {
        $groupedServices[(int) $service['category_id']][] = $service;
    }
    $currentUserRole = $_SESSION['user']['role'] ?? '';
    $isStaff = in_array($currentUserRole, ['admin', 'technician'], true);
    $requestUrl = $currentUserRole === 'customer'
        ? '/DoAn_DienLanh/scr/request/create'
        : '/DoAn_DienLanh/scr/auth/login';
    ?>
    <div class="service-toolbar">
        <nav class="category-pills" aria-label="Nhóm dịch vụ">
            <a class="category-pill <?= $categoryId === 0 ? 'is-active' : '' ?>"
                href="/DoAn_DienLanh/scr/service?category_id=0&amp;search=<?= rawurlencode($search) ?>">Tất cả</a>
            <?php foreach ($categories as $category): ?>
                <?php $categoryCount = (int) ($categoryCounts[(int) $category['id']] ?? 0); ?>
                <a class="category-pill <?= $categoryId === (int) $category['id'] ? 'is-active' : '' ?>"
                    href="/DoAn_DienLanh/scr/service?category_id=<?= (int) $category['id'] ?>&amp;search=<?= rawurlencode($search) ?>">
                    <?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?> (<?= $categoryCount ?>)
                </a>
            <?php endforeach; ?>
        </nav>
        <form class="service-search" method="get" action="/DoAn_DienLanh/scr/service">
            <input type="hidden" name="category_id" value="<?= (int) $categoryId ?>">
            <label class="sr-only" for="search">Tìm theo tên dịch vụ</label>
            <input id="search" name="search" placeholder="Tìm dịch vụ..." value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit">Tìm</button>
        </form>
    </div>
    <?php if ($currentUserRole === 'customer'): ?>
        <p class="service-customer-note">Chọn dịch vụ phù hợp và gửi yêu cầu. Bạn có thể dùng thiết bị đã lưu.</p>
    <?php endif; ?>

    <?php foreach ($categories as $category): ?>
        <?php $categoryServices = $groupedServices[(int) $category['id']] ?? []; ?>
        <?php if (!empty($categoryServices)): ?>
        <section class="service-category" id="category-<?= (int) $category['id'] ?>">
            <div class="service-category-heading">
                <h2><?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?></h2>
                <?php if (trim((string) ($category['description'] ?? '')) !== ''): ?>
                    <p><?= htmlspecialchars($category['description'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
            </div>
            <div class="service-grid">
                <?php foreach ($categoryServices as $service): ?>
                    <article class="card service-card">
                        <h3><?= htmlspecialchars($service['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <p class="service-description"><?= htmlspecialchars($service['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                        <div class="service-meta">
                            <div class="service-meta-item">
                                <span>Giá tham khảo</span>
                                <strong>Từ <?= number_format((float) $service['base_price'], 0, ',', '.') ?> đ</strong>
                            </div>
                            <div class="service-meta-item">
                                <span>Thời gian dự kiến</span>
                                <strong><?= (int) $service['est_minutes'] ?> phút</strong>
                            </div>
                            <a class="button button-outline" href="/DoAn_DienLanh/scr/request/create?service_id=<?= (int) $service['id'] ?>">Tạo yêu cầu</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    <?php endforeach; ?>
    <?php if (empty($services)): ?>
        <p class="empty-state card">Không tìm thấy dịch vụ phù hợp.</p>
    <?php endif; ?>
    <?php require __DIR__ . '/../partials/pagination.php'; ?>
    <?php if (!$isStaff): ?>
        <section class="service-cta">
            <div>
                <h2>Sẵn sàng đặt lịch sửa chữa?</h2>
                <p>Đăng nhập để gửi yêu cầu và theo dõi tiến độ sửa chữa.</p>
            </div>
            <a class="btn-cta" href="<?= htmlspecialchars($requestUrl, ENT_QUOTES, 'UTF-8') ?>">Gửi yêu cầu sửa chữa</a>
        </section>
    <?php endif; ?>
</section>

<section>
    <div class="page-head"><div><h1>Quản lý khách hàng</h1><p>Tra cứu hồ sơ, yêu cầu và tình trạng tài khoản khách hàng.</p></div></div>
    <form class="filter-card" method="get" action="/DoAn_DienLanh/scr/customer/index">
        <div class="form-group">
            <label for="customer-search">Tìm tên, SĐT hoặc email</label>
            <input id="customer-search" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <button type="submit">Tìm kiếm</button>
    </form>

    <div class="table-card">
        <div class="table-card-head"><h2>Danh sách khách hàng</h2><span><?= (int) ($total ?? count($customers)) ?> khách hàng</span></div>
        <table>
            <thead><tr><th>Khách hàng</th><th>SĐT</th><th>Số yêu cầu</th><th>Tổng chi tiêu</th><th>Ngày đăng ký</th><th>Tài khoản</th><th>Thao tác</th></tr></thead>
            <tbody>
            <?php foreach ($customers as $customer): ?>
                <tr>
                    <td><a class="table-primary-link" href="/DoAn_DienLanh/scr/customer/detail/<?= (int) $customer['id'] ?>"><?= htmlspecialchars($customer['full_name'], ENT_QUOTES, 'UTF-8') ?></a><span class="table-subtext"><?= htmlspecialchars($customer['email'], ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td><?= htmlspecialchars($customer['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= (int) $customer['request_count'] ?></td>
                    <td><strong><?= number_format((float) $customer['total_spent'], 0, ',', '.') ?> ₫</strong></td>
                    <td><?= htmlspecialchars(date('d/m/Y', strtotime($customer['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge <?= (int) $customer['status'] === 1 ? 'badge-success' : 'badge-muted' ?>"><?= (int) $customer['status'] === 1 ? 'Hoạt động' : 'Đã khóa' ?></span></td>
                    <td class="table-actions text-actions">
                        <a class="text-action" href="/DoAn_DienLanh/scr/customer/detail/<?= (int) $customer['id'] ?>">Chi tiết</a>
                        <form method="post" action="/DoAn_DienLanh/scr/customer/status/<?= (int) $customer['id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="status" value="<?= (int) $customer['status'] === 1 ? '0' : '1' ?>">
                            <button class="text-action <?= (int) $customer['status'] === 1 ? 'text-action-danger' : '' ?>" type="submit"><?= (int) $customer['status'] === 1 ? 'Khóa' : 'Mở' ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($customers)): ?><tr><td colspan="7" class="empty-state">Không tìm thấy khách hàng.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php require __DIR__ . '/../../partials/pagination.php'; ?>
</section>

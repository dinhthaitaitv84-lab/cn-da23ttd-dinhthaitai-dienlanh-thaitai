<div class="page-head">
    <div><h1>Quản lý dịch vụ</h1><p>Quản lý danh sách dịch vụ, giá tham khảo và thời lượng.</p></div>
    <a class="button" href="<?= htmlspecialchars(url('/admin/serviceCreate'), ENT_QUOTES, 'UTF-8') ?>">Thêm dịch vụ</a>
</div>
<form class="filter-card" method="get" action="<?= htmlspecialchars(url('/admin/services'), ENT_QUOTES, 'UTF-8') ?>">
    <div class="form-group">
        <label for="category_id">Lọc theo nhóm dịch vụ</label>
        <select id="category_id" name="category_id">
            <option value="0">Tất cả nhóm</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= (int) $category['id'] ?>" <?= $categoryId === (int) $category['id'] ? 'selected' : '' ?>><?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="search">Tìm theo tên</label>
        <input id="search" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <button type="submit">Tìm</button>
</form>
<div class="table-card">
    <div class="table-card-head"><h2>Danh sách dịch vụ</h2><span><?= (int) ($total ?? count($services)) ?> dịch vụ</span></div>
    <table>
        <thead><tr><th>Tên dịch vụ</th><th>Nhóm dịch vụ</th><th>Giá cơ bản</th><th>Thời lượng</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
        <tbody>
        <?php foreach ($services as $service): ?>
            <tr>
                <td><?= htmlspecialchars($service['name'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($service['category_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= number_format((float) $service['base_price'], 0, ',', '.') ?> ₫</td>
                <td><?= (int) $service['est_minutes'] ?> phút</td>
                <td><span class="badge <?= (int) $service['status'] === 1 ? 'badge-success' : 'badge-muted' ?>"><?= (int) $service['status'] === 1 ? 'Hiển thị' : 'Đang ẩn' ?></span></td>
                <td class="table-actions text-actions">
                    <a class="text-action" href="<?= htmlspecialchars(url('/admin/serviceEdit/' . (int) $service['id']), ENT_QUOTES, 'UTF-8') ?>">Sửa</a>
                    <form method="post" action="<?= htmlspecialchars(url('/admin/serviceToggle/' . (int) $service['id']), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                        <button class="text-action text-action-neutral" type="submit">Ẩn/Hiện</button>
                    </form>
                    <form method="post" action="<?= htmlspecialchars(url('/admin/serviceDelete/' . (int) $service['id']), ENT_QUOTES, 'UTF-8') ?>" data-confirm="Xóa dịch vụ này?">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="page" value="<?= (int) $page ?>">
                        <input type="hidden" name="category_id" value="<?= (int) $categoryId ?>">
                        <input type="hidden" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                        <button class="text-action text-action-danger" type="submit">Xóa</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($services)): ?><tr><td colspan="6" class="empty-state">Không tìm thấy dịch vụ.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../partials/pagination.php'; ?>

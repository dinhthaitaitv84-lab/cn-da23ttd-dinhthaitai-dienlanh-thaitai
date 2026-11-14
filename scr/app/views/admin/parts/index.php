<section>
    <div class="page-head">
        <div><h1>Quản lý linh kiện</h1><p>Quản lý danh sách linh kiện, giá bán và số lượng tồn kho.</p></div>
        <a class="button" href="/DoAn_DienLanh/scr/part/create">Thêm linh kiện</a>
    </div>

    <form class="filter-card" method="get" action="/DoAn_DienLanh/scr/part/index">
        <div class="form-group">
            <label for="part-search">Tìm tên hoặc SKU</label>
            <input id="part-search" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="form-group form-check">
            <label><input type="checkbox" name="low_stock" value="1" <?= $lowStock ? 'checked' : '' ?>> Sắp hết hàng (tồn kho ≤ 5)</label>
        </div>
        <button type="submit">Lọc</button>
    </form>

    <div class="table-card">
        <div class="table-card-head"><h2>Danh sách linh kiện</h2><span><?= (int) ($total ?? count($parts)) ?> linh kiện</span></div>
        <table>
            <thead>
            <tr><th>Tên</th><th>SKU</th><th>Đơn vị</th><th>Giá</th><th>Tồn kho</th><th>Trạng thái</th><th>Thao tác</th></tr>
            </thead>
            <tbody>
            <?php foreach ($parts as $part): ?>
                <tr class="<?= (int) $part['stock_qty'] <= 5 ? 'inventory-low-stock-row' : '' ?>">
                    <td><?= htmlspecialchars($part['name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($part['sku'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($part['unit'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= number_format((float) $part['price'], 0, ',', '.') ?> ₫</td>
                    <td><strong><?= (int) $part['stock_qty'] ?></strong></td>
                    <td><span class="badge <?= (int) $part['status'] === 1 ? 'badge-success' : 'badge-muted' ?>"><?= (int) $part['status'] === 1 ? 'Hiển thị' : 'Đang ẩn' ?></span></td>
                    <td>
                        <div class="table-actions text-actions">
                            <a class="text-action" href="/DoAn_DienLanh/scr/part/edit/<?= (int) $part['id'] ?>">Sửa</a>
                            <form method="post" action="/DoAn_DienLanh/scr/part/stock/<?= (int) $part['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                                <label class="sr-only" for="stock-<?= (int) $part['id'] ?>">Số lượng nhập kho</label>
                                <input class="inline-number" id="stock-<?= (int) $part['id'] ?>" type="number" name="quantity" min="1" step="1" required>
                                <button class="text-action text-action-neutral" type="submit">Nhập kho</button>
                            </form>
                            <form method="post" action="/DoAn_DienLanh/scr/part/status/<?= (int) $part['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="status" value="<?= (int) $part['status'] === 1 ? '0' : '1' ?>">
                                <button class="text-action text-action-neutral" type="submit"><?= (int) $part['status'] === 1 ? 'Ẩn' : 'Hiện' ?></button>
                            </form>
                            <form method="post" action="/DoAn_DienLanh/scr/part/delete/<?= (int) $part['id'] ?>" data-confirm="Xác nhận xóa linh kiện này?">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="page" value="<?= (int) $page ?>">
                                <input type="hidden" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="low_stock" value="<?= $lowStock ? '1' : '0' ?>">
                                <button class="text-action text-action-danger" type="submit">Xóa</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($parts)): ?>
                <tr><td colspan="7" class="empty-state">Không tìm thấy linh kiện.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php require __DIR__ . '/../../partials/pagination.php'; ?>
</section>

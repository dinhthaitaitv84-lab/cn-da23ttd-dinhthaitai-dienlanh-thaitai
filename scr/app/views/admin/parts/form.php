<?php $editing = is_array($part); ?>
<div class="page-head">
    <div><h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1><p>Cập nhật thông tin, giá và tồn kho linh kiện.</p></div>
    <a class="button button-secondary" href="/DoAn_DienLanh/scr/part/index">Về danh sách</a>
</div>
<div class="admin-form-layout">
    <section class="card admin-form">
        <div class="admin-form-title"><h2>Thông tin linh kiện</h2></div>
        <form method="post" action="<?= $editing ? '/DoAn_DienLanh/scr/part/edit/' . (int) $part['id'] : '/DoAn_DienLanh/scr/part/create' ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-group">
                <label for="part-name">Tên linh kiện</label>
                <input id="part-name" name="name" maxlength="180" required value="<?= htmlspecialchars($part['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label for="part-sku">Mã SKU</label>
                <input id="part-sku" name="sku" maxlength="80" required value="<?= htmlspecialchars($part['sku'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label for="part-unit">Đơn vị</label>
                <input id="part-unit" name="unit" maxlength="40" required value="<?= htmlspecialchars($part['unit'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label for="part-price">Giá (₫)</label>
                <input id="part-price" type="number" name="price" min="0" step="1" required value="<?= htmlspecialchars((string) ($part['price'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label for="part-stock">Tồn kho</label>
                <input id="part-stock" type="number" name="stock_qty" min="0" step="1" required value="<?= (int) ($part['stock_qty'] ?? 0) ?>">
            </div>
            <div class="form-group">
                <label for="part-status">Trạng thái</label>
                <select id="part-status" name="status">
                    <option value="1" <?= !isset($part['status']) || (int) $part['status'] === 1 ? 'selected' : '' ?>>Đang bán</option>
                    <option value="0" <?= isset($part['status']) && (int) $part['status'] === 0 ? 'selected' : '' ?>>Đã ẩn</option>
                </select>
            </div>
            <div class="form-actions">
                <button type="submit">Lưu</button>
                <a class="button button-secondary" href="/DoAn_DienLanh/scr/part/index">Hủy</a>
            </div>
        </form>
    </section>
    <aside class="admin-form-help">
        <h2>Quản lý kho linh kiện</h2>
        <p>Thông tin linh kiện được dùng trong báo giá và theo dõi tồn kho.</p>
        <p>Cập nhật chính xác SKU, đơn vị tính và số lượng để tiện kiểm soát nhập kho.</p>
    </aside>
</div>

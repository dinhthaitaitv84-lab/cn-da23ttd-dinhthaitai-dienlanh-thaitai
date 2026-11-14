<div class="page-head">
    <div><h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1><p>Cập nhật thông tin và thời lượng dịch vụ.</p></div>
    <a class="button button-secondary" href="/DoAn_DienLanh/scr/admin/services">Về danh sách</a>
</div>
<div class="admin-form-layout">
    <section class="card admin-form">
        <div class="admin-form-title"><h2>Thông tin cơ bản</h2></div>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-group">
                <label for="name">Tên dịch vụ</label>
                <input id="name" name="name" required value="<?= htmlspecialchars($service['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label for="category_id">Nhóm dịch vụ</label>
                <select id="category_id" name="category_id" required>
                    <option value="">Chọn nhóm dịch vụ</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category['id'] ?>" <?= (int) ($service['category_id'] ?? 0) === (int) $category['id'] ? 'selected' : '' ?>><?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group form-full">
                <label for="description">Mô tả</label>
                <textarea id="description" name="description" rows="4"><?= htmlspecialchars($service['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
            <div class="form-group">
                <label for="base_price">Giá cơ bản (₫)</label>
                <input id="base_price" name="base_price" type="number" min="0" step="1000" required value="<?= htmlspecialchars((string) ($service['base_price'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label for="est_minutes">Thời lượng ước tính (phút)</label>
                <input id="est_minutes" name="est_minutes" type="number" min="1" step="1" required value="<?= htmlspecialchars((string) ($service['est_minutes'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-actions">
                <button type="submit">Lưu</button>
                <a class="button button-secondary" href="/DoAn_DienLanh/scr/admin/services">Hủy</a>
            </div>
        </form>
    </section>
    <aside class="admin-form-help">
        <h2>Thông tin dịch vụ</h2>
        <p>Mô tả rõ phạm vi sửa chữa để khách hàng hiểu dịch vụ trước khi gửi yêu cầu.</p>
        <p>Giá và thời lượng là thông tin tham khảo; chi phí chính thức được xác nhận sau khi kiểm tra thiết bị.</p>
    </aside>
</div>

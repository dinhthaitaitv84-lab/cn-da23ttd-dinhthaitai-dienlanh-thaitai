<div class="page-head">
    <div><h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1><p>Cập nhật thông tin nhóm dịch vụ.</p></div>
    <a class="button button-secondary" href="/DoAn_DienLanh/scr/admin/categories">Về danh sách</a>
</div>
<div class="admin-form-layout">
    <section class="card admin-form">
        <div class="admin-form-title"><h2>Thông tin cơ bản</h2></div>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-group">
                <label for="name">Tên nhóm</label>
                <input id="name" name="name" required value="<?= htmlspecialchars($category['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group form-full">
                <label for="description">Mô tả</label>
                <textarea id="description" name="description" rows="4"><?= htmlspecialchars($category['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
            <div class="form-actions">
                <button type="submit">Lưu</button>
                <a class="button button-secondary" href="/DoAn_DienLanh/scr/admin/categories">Hủy</a>
            </div>
        </form>
    </section>
    <aside class="admin-form-help">
        <h2>Nhóm dịch vụ</h2>
        <p>Nhóm dịch vụ giúp khách hàng tìm đúng dịch vụ theo loại thiết bị.</p>
        <p><?= empty($category) ? 'Đặt tên ngắn gọn, dễ hiểu và mô tả các thiết bị phù hợp.' : 'Thay đổi tên hoặc mô tả sẽ cập nhật thông tin hiển thị trên website.' ?></p>
    </aside>
</div>

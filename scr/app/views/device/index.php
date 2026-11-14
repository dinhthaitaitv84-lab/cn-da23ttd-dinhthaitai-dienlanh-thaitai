<section>
    <div class="admin-heading">
        <h1>Thiết bị</h1>
    </div>
    <p class="page-description">Quản lý các thiết bị đã lưu để gửi yêu cầu nhanh hơn.</p>

    <section class="card device-form-card">
        <h2>Thêm thiết bị</h2>
        <form method="post" action="<?= htmlspecialchars(url('/device/store'), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="device-form-grid">
                <div class="form-group">
                    <label for="new-category-id">Nhóm thiết bị</label>
                    <select id="new-category-id" name="category_id" required>
                        <option value="">Chọn nhóm</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= (int) $category['id'] ?>"><?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label for="new-device-name">Tên thiết bị</label><input id="new-device-name" name="name" maxlength="150" required></div>
                <div class="form-group"><label for="new-device-brand">Hãng</label><input id="new-device-brand" name="brand" maxlength="100"></div>
                <div class="form-group"><label for="new-device-model">Model</label><input id="new-device-model" name="model" maxlength="100"></div>
                <div class="form-group device-note"><label for="new-device-note">Ghi chú</label><textarea id="new-device-note" name="note" rows="2"></textarea></div>
            </div>
            <button type="submit">Thêm thiết bị</button>
        </form>
    </section>

    <div class="device-list">
        <?php foreach ($devices as $device): ?>
            <section class="card device-card">
                <form method="post" action="<?= htmlspecialchars(url('/device/update/' . (int) $device['id']), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                    <div class="device-form-grid">
                        <div class="form-group">
                            <label for="category-<?= (int) $device['id'] ?>">Nhóm thiết bị</label>
                            <select id="category-<?= (int) $device['id'] ?>" name="category_id" required>
                                <option value="">Chọn nhóm thiết bị</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= (int) $category['id'] ?>" <?= (int) $device['category_id'] === (int) $category['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group"><label for="name-<?= (int) $device['id'] ?>">Tên thiết bị</label><input id="name-<?= (int) $device['id'] ?>" name="name" value="<?= htmlspecialchars($device['name'], ENT_QUOTES, 'UTF-8') ?>" maxlength="150" required></div>
                        <div class="form-group"><label for="brand-<?= (int) $device['id'] ?>">Hãng</label><input id="brand-<?= (int) $device['id'] ?>" name="brand" value="<?= htmlspecialchars($device['brand'] ?? '', ENT_QUOTES, 'UTF-8') ?>" maxlength="100"></div>
                        <div class="form-group"><label for="model-<?= (int) $device['id'] ?>">Model</label><input id="model-<?= (int) $device['id'] ?>" name="model" value="<?= htmlspecialchars($device['model'] ?? '', ENT_QUOTES, 'UTF-8') ?>" maxlength="100"></div>
                        <div class="form-group device-note"><label for="note-<?= (int) $device['id'] ?>">Ghi chú</label><textarea id="note-<?= (int) $device['id'] ?>" name="note" rows="2"><?= htmlspecialchars($device['note'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea></div>
                    </div>
                    <button type="submit">Lưu thay đổi</button>
                </form>
                <div class="device-actions">
                        <a class="button button-secondary" href="<?= htmlspecialchars(url('/device/detail/' . (int) $device['id']), ENT_QUOTES, 'UTF-8') ?>">Lịch sử sửa chữa</a>
                        <form method="post" action="<?= htmlspecialchars(url('/device/delete/' . (int) $device['id']), ENT_QUOTES, 'UTF-8') ?>" data-confirm="Quý khách xác nhận xóa thiết bị này?">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="page" value="<?= (int) $page ?>">
                            <button class="button-danger" type="submit">Xóa</button>
                        </form>
                </div>
            </section>
        <?php endforeach; ?>
        <?php if (empty($devices)): ?>
            <p class="empty-state">Quý khách chưa lưu thiết bị nào.</p>
        <?php endif; ?>
    </div>
    <?php require __DIR__ . '/../partials/pagination.php'; ?>
</section>

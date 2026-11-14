<div class="page-head">
    <div><h1>Nhóm dịch vụ</h1><p>Quản lý các nhóm thiết bị và dịch vụ đang cung cấp.</p></div>
    <a class="button" href="/DoAn_DienLanh/scr/admin/categoryCreate">Thêm nhóm dịch vụ</a>
</div>
<div class="table-card">
    <div class="table-card-head"><h2>Danh sách nhóm dịch vụ</h2><span><?= (int) ($total ?? count($categories)) ?> nhóm dịch vụ</span></div>
    <table>
        <thead><tr><th>Tên nhóm</th><th>Mô tả</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
        <tbody>
        <?php foreach ($categories as $category): ?>
            <tr>
                <td><?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($category['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                <td><span class="badge <?= (int) $category['status'] === 1 ? 'badge-success' : 'badge-muted' ?>"><?= (int) $category['status'] === 1 ? 'Hiển thị' : 'Đang ẩn' ?></span></td>
                <td class="table-actions text-actions">
                    <a class="text-action" href="/DoAn_DienLanh/scr/admin/categoryEdit/<?= (int) $category['id'] ?>">Sửa</a>
                    <form method="post" action="/DoAn_DienLanh/scr/admin/categoryToggle/<?= (int) $category['id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                        <button class="text-action text-action-neutral" type="submit">Ẩn/Hiện</button>
                    </form>
                    <form method="post" action="/DoAn_DienLanh/scr/admin/categoryDelete/<?= (int) $category['id'] ?>" data-confirm="Xóa nhóm dịch vụ này?">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="page" value="<?= (int) $page ?>">
                        <button class="text-action text-action-danger" type="submit">Xóa</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($categories)): ?><tr><td colspan="4" class="empty-state">Chưa có nhóm dịch vụ.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../partials/pagination.php'; ?>

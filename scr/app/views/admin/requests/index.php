<div class="page-head">
    <div><h1>Điều phối yêu cầu</h1><p>Theo dõi trạng thái, mức ưu tiên và lịch phân công.</p></div>
</div>
<form class="filter-card" method="get" action="<?= htmlspecialchars(url('/admin/requests'), ENT_QUOTES, 'UTF-8') ?>">
    <div class="form-group">
        <label for="request-search">Tìm mã / khách hàng</label>
        <input id="request-search" name="search" value="<?= htmlspecialchars($filters['search'], ENT_QUOTES, 'UTF-8') ?>" maxlength="100">
    </div>
    <div class="form-group">
        <label for="request-status">Trạng thái</label>
        <select id="request-status" name="status">
            <option value="">Tất cả</option>
            <?php foreach ($statuses as $value => $label): ?>
                <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= $filters['status'] === $value ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="request-priority">Ưu tiên</label>
        <select id="request-priority" name="priority">
            <option value="">Tất cả</option>
            <?php foreach ($priorities as $value => $label): ?>
                <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= $filters['priority'] === $value ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="request-category">Nhóm dịch vụ</label>
        <select id="request-category" name="category_id">
            <option value="0">Tất cả</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= (int) $category['id'] ?>" <?= (int) $filters['category_id'] === (int) $category['id'] ? 'selected' : '' ?>><?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="request-technician">Kỹ thuật viên</label>
        <select id="request-technician" name="technician_id">
            <option value="0">Tất cả</option>
            <?php foreach ($technicians as $technician): ?>
                <option value="<?= (int) $technician['id'] ?>" <?= (int) $filters['technician_id'] === (int) $technician['id'] ? 'selected' : '' ?>><?= htmlspecialchars($technician['full_name'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit">Lọc</button>
</form>
<div class="table-card">
    <div class="table-card-head"><h2>Danh sách yêu cầu</h2><span><?= (int) ($total ?? count($requests)) ?> yêu cầu</span></div>
    <table>
        <thead><tr><th>Mã</th><th>Khách hàng</th><th>Dịch vụ</th><th>Ưu tiên</th><th>Trạng thái</th><th>Kỹ thuật viên</th><th>Ngày gửi</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($requests as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['code'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($item['customer_name'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($item['service_name'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><span class="badge badge-priority badge-priority-<?= htmlspecialchars($item['priority'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($priorities[$item['priority']] ?? $item['priority'], ENT_QUOTES, 'UTF-8') ?></span></td>
                <td><span class="badge badge-status badge-<?= htmlspecialchars($item['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statuses[$item['status']] ?? $item['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
                <td><?= htmlspecialchars($item['technician_name'] ?? 'Chưa phân công', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($item['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                <td><a class="button button-secondary" href="<?= htmlspecialchars(url('/admin/requestDetail/' . (int) $item['id']), ENT_QUOTES, 'UTF-8') ?>">Chi tiết</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($requests)): ?><tr><td colspan="8" class="empty-state">Không tìm thấy yêu cầu.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../../partials/pagination.php'; ?>

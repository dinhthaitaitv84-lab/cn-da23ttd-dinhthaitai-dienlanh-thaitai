<section>
    <div class="admin-heading">
        <h1>Lịch sử thiết bị</h1>
        <?php if (($_SESSION['user']['role'] ?? '') === 'customer'): ?>
            <a class="button button-light" href="<?= htmlspecialchars(url('/device/index'), ENT_QUOTES, 'UTF-8') ?>">Thiết bị</a>
        <?php else: ?>
            <a class="button button-light" href="<?= htmlspecialchars(url('/customer/detail/' . (int) $device['customer_id']), ENT_QUOTES, 'UTF-8') ?>">Quay lại khách hàng</a>
        <?php endif; ?>
    </div>
    <p class="page-description">Theo dõi các lần sửa chữa và thông tin bảo hành của thiết bị.</p>
    <div class="card customer-profile">
        <p><strong>Thiết bị:</strong> <?= htmlspecialchars($device['name'], ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>Nhóm:</strong> <?= htmlspecialchars($device['category_name'], ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>Hãng:</strong> <?= htmlspecialchars($device['brand'] ?? '—', ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>Model:</strong> <?= htmlspecialchars($device['model'] ?? '—', ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>Ghi chú:</strong> <?= nl2br(htmlspecialchars($device['note'] ?? '—', ENT_QUOTES, 'UTF-8')) ?></p>
    </div>

    <h2>Các lần sửa chữa</h2>
    <?php foreach ($device['repairs'] as $repair): ?>
        <?php
        $repairParts = [];
        $partsTotal = 0;
        foreach ($device['repair_parts'] as $repairPart) {
            if ((int) $repairPart['request_id'] === (int) $repair['id']) {
                $repairParts[] = $repairPart;
                $partsTotal += (float) $repairPart['qty'] * (float) $repairPart['unit_price'];
            }
        }
        $completionDate = $repair['completed_at'] ?: $repair['created_at'];
        $warrantyEnd = (int) $repair['warranty_months'] > 0
            ? strtotime('+' . (int) $repair['warranty_months'] . ' months', strtotime($completionDate))
            : null;
        $totalCost = $repair['total_cost'] !== null
            ? (float) $repair['total_cost']
            : (float) ($repair['labor_cost'] ?? 0) + $partsTotal;
        $statusLabels = [
            'pending' => 'Chờ tiếp nhận', 'received' => 'Đã tiếp nhận',
            'checking' => 'Đang kiểm tra', 'waiting_parts' => 'Chờ linh kiện',
            'repairing' => 'Đang sửa chữa', 'quoted' => 'Đã báo giá',
            'completed' => 'Hoàn thành', 'cancelled' => 'Đã hủy',
        ];
        ?>
        <article class="card repair-history-card">
            <div class="admin-heading">
                <h3><a href="<?= htmlspecialchars(url('/request/detail/' . (int) $repair['id']), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($repair['code'], ENT_QUOTES, 'UTF-8') ?></a></h3>
                <span class="badge badge-status badge-<?= htmlspecialchars($repair['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabels[$repair['status']] ?? $repair['status'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <p><strong>Ngày:</strong> <?= htmlspecialchars(date('d/m/Y H:i', strtotime($completionDate)), ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>Dịch vụ:</strong> <?= htmlspecialchars($repair['service_name'], ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>Nguyên nhân:</strong> <?= nl2br(htmlspecialchars($repair['cause'] ?? 'Chưa ghi nhận', ENT_QUOTES, 'UTF-8')) ?></p>
            <p><strong>Phương án:</strong> <?= nl2br(htmlspecialchars($repair['solution'] ?? 'Chưa ghi nhận', ENT_QUOTES, 'UTF-8')) ?></p>
            <?php if ($repairParts): ?>
                <h4>Linh kiện thay thế</h4>
                <ul>
                    <?php foreach ($repairParts as $repairPart): ?>
                        <li><?= htmlspecialchars($repairPart['name'], ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars(rtrim(rtrim(number_format((float) $repairPart['qty'], 2, '.', ''), '0'), '.'), ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($repairPart['unit'], ENT_QUOTES, 'UTF-8') ?> x <?= number_format((float) $repairPart['unit_price'], 0, ',', '.') ?> ₫</li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p><strong>Linh kiện thay thế:</strong> Không có</p>
            <?php endif; ?>
            <p><strong>Chi phí:</strong> <?= number_format($totalCost, 0, ',', '.') ?> ₫</p>
            <p><strong>Bảo hành:</strong>
                <?php if ($warrantyEnd): ?>
                    <?= $warrantyEnd >= time() ? 'Còn hạn đến ' : 'Hết hạn ' ?><?= htmlspecialchars(date('d/m/Y', $warrantyEnd), ENT_QUOTES, 'UTF-8') ?>
                <?php else: ?>Không áp dụng<?php endif; ?>
            </p>
        </article>
    <?php endforeach; ?>
    <?php if (empty($device['repairs'])): ?><p class="empty-state">Thiết bị chưa có lịch sử sửa chữa.</p><?php endif; ?>
    <?php require __DIR__ . '/../partials/pagination.php'; ?>
</section>

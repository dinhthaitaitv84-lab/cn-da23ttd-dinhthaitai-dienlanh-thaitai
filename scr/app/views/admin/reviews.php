<div class="page-head">
    <div><h1>Đánh giá khách hàng</h1><p>Theo dõi phản hồi và điểm đánh giá sau khi hoàn tất dịch vụ.</p></div>
</div>
<form class="filter-card" method="get" action="/DoAn_DienLanh/scr/admin/reviews">
    <div class="form-group">
        <label for="rating">Số sao</label>
        <select id="rating" name="rating">
            <option value="0">Tất cả</option>
            <?php for ($stars = 5; $stars >= 1; $stars--): ?>
                <option value="<?= $stars ?>" <?= $rating === $stars ? 'selected' : '' ?>><?= $stars ?> sao</option>
            <?php endfor; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="technician_id">Kỹ thuật viên</label>
        <select id="technician_id" name="technician_id">
            <option value="0">Tất cả</option>
            <?php foreach ($technicians as $technician): ?>
                <option value="<?= (int) $technician['id'] ?>" <?= $technicianId === (int) $technician['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($technician['full_name'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit">Lọc</button>
</form>
<div class="table-card">
    <div class="table-card-head"><h2>Đánh giá gần đây</h2><span><?= (int) ($total ?? count($reviews)) ?> đánh giá</span></div>
    <table>
        <thead><tr><th>Ngày</th><th>Yêu cầu</th><th>Khách hàng</th><th>Kỹ thuật viên</th><th>Đánh giá</th><th>Nội dung</th></tr></thead>
        <tbody>
        <?php foreach ($reviews as $review): ?>
            <tr>
                <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($review['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                <td><a href="/DoAn_DienLanh/scr/admin/requestDetail/<?= (int) $review['request_id'] ?>"><?= htmlspecialchars($review['code'], ENT_QUOTES, 'UTF-8') ?></a></td>
                <td><?= htmlspecialchars($review['customer_name'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($review['technician_name'] ?? 'Chưa phân công', ENT_QUOTES, 'UTF-8') ?></td>
                <td><span class="review-stars" aria-label="<?= (int) $review['rating'] ?> trên 5"><?= (int) $review['rating'] ?>/5</span></td>
                <td><?= nl2br(htmlspecialchars($review['comment'] ?? '', ENT_QUOTES, 'UTF-8')) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($reviews)): ?><tr><td colspan="6" class="empty-state">Chưa có đánh giá phù hợp.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../partials/pagination.php'; ?>

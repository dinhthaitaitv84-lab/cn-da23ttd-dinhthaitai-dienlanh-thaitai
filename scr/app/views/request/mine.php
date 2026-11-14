<section class="requests-page">
    <div class="page-heading">
        <h1>Yêu cầu của tôi</h1>
        <p>Theo dõi trạng thái và cập nhật mới nhất của các yêu cầu dịch vụ.</p>
    </div>
    <section class="card request-list-card">
        <div class="request-list-heading">
            <h2>Danh sách yêu cầu</h2>
            <a class="button" href="/DoAn_DienLanh/scr/request/create">Gửi yêu cầu</a>
        </div>
        <form class="filter-form" method="get" action="/DoAn_DienLanh/scr/request/mine">
            <div class="form-group">
                <label for="status">Trạng thái</label>
                <select id="status" name="status">
                    <option value="">Tất cả trạng thái</option>
                    <?php foreach ($statuses as $value => $label): ?>
                        <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= $selectedStatus === $value ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="search">Tìm theo mã</label>
                <input id="search" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" maxlength="40">
            </div>
            <button type="submit">Lọc</button>
        </form>
        <div class="table-wrap request-table-wrap">
            <table>
                <thead>
                    <tr><th>Mã yêu cầu</th><th>Dịch vụ</th><th>Ngày gửi</th><th>Trạng thái</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $request): ?>
                        <tr>
                            <td data-label="Mã yêu cầu"><strong class="request-code"><?= htmlspecialchars($request['code'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                            <td data-label="Dịch vụ"><?= htmlspecialchars($request['service_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td data-label="Ngày gửi"><?= htmlspecialchars(date('d/m/Y H:i', strtotime($request['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                            <td data-label="Trạng thái"><span class="badge badge-status badge-<?= htmlspecialchars($request['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statuses[$request['status']] ?? $request['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td data-label="Chi tiết"><a class="request-detail-link" href="/DoAn_DienLanh/scr/request/detail/<?= (int) $request['id'] ?>">Chi tiết</a></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($requests)): ?><tr><td colspan="5" class="empty-state">Không tìm thấy yêu cầu nào.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php require __DIR__ . '/../partials/pagination.php'; ?>
    </section>
</section>

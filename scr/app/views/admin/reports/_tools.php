<div class="page-head report-page-head">
    <div>
        <h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
        <p>Kỳ báo cáo: <?= htmlspecialchars(date('d/m/Y', strtotime($filters['from'])), ENT_QUOTES, 'UTF-8') ?> – <?= htmlspecialchars(date('d/m/Y', strtotime($filters['to'])), ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    <div class="page-head-actions">
        <a class="button button-secondary" href="/DoAn_DienLanh/scr/report/export/<?= htmlspecialchars($reportType, ENT_QUOTES, 'UTF-8') ?>?from=<?= rawurlencode($filters['from']) ?>&amp;to=<?= rawurlencode($filters['to']) ?>">Xuất CSV</a>
        <button class="button button-secondary" type="button" data-print-report>In báo cáo</button>
    </div>
</div>
<nav class="report-tabs" aria-label="Các báo cáo">
    <a class="<?= $reportType === 'index' ? 'active' : '' ?>" href="/DoAn_DienLanh/scr/report/index">Tổng quan</a>
    <a class="<?= $reportType === 'services' ? 'active' : '' ?>" href="/DoAn_DienLanh/scr/report/services">Dịch vụ</a>
    <a class="<?= $reportType === 'technicians' ? 'active' : '' ?>" href="/DoAn_DienLanh/scr/report/technicians">Kỹ thuật viên</a>
    <a class="<?= $reportType === 'status' ? 'active' : '' ?>" href="/DoAn_DienLanh/scr/report/status">Trạng thái</a>
    <a class="<?= $reportType === 'parts' ? 'active' : '' ?>" href="/DoAn_DienLanh/scr/report/parts">Linh kiện</a>
</nav>
<form class="filter-card report-filters" method="get" action="/DoAn_DienLanh/scr/report/<?= htmlspecialchars($reportType, ENT_QUOTES, 'UTF-8') ?>">
    <div class="form-group">
        <label for="report-from">Từ ngày</label>
        <input id="report-from" type="date" name="from" value="<?= htmlspecialchars($filters['from'], ENT_QUOTES, 'UTF-8') ?>" required>
    </div>
    <div class="form-group">
        <label for="report-to">Đến ngày</label>
        <input id="report-to" type="date" name="to" value="<?= htmlspecialchars($filters['to'], ENT_QUOTES, 'UTF-8') ?>" required>
    </div>
    <div class="report-filter-actions">
        <button type="submit">Lọc</button>
        <button class="button-secondary" type="button" data-report-quick="today">Hôm nay</button>
        <button class="button-secondary" type="button" data-report-quick="7days">7 ngày</button>
        <button class="button-secondary" type="button" data-report-quick="month">Tháng này</button>
        <button class="button-secondary" type="button" data-report-quick="year">Năm nay</button>
    </div>
</form>

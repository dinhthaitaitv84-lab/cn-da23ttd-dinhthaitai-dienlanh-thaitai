<?php
require_once __DIR__ . '/../partials/icons.php';
$currentUser = $_SESSION['user'] ?? [];
$userName = trim((string) ($currentUser['full_name'] ?? ''));
if ($userName === '' && (int) ($currentUser['id'] ?? 0) > 0) {
    $profile = $this->model('UserModel')->findProfile((int) $currentUser['id']);
    if ($profile) {
        $userName = trim((string) ($profile['full_name'] ?? ''));
        $_SESSION['user']['full_name'] = $userName;
        if (!empty($profile['email'])) {
            $currentUser['email'] = $profile['email'];
            $_SESSION['user']['email'] = $profile['email'];
        }
    }
}
$userEmail = trim((string) ($currentUser['email'] ?? ''));
$avatarSource = $userName !== '' ? $userName : $userEmail;
$avatarInitial = $avatarSource !== ''
    ? mb_strtoupper(mb_substr($avatarSource, 0, 1, 'UTF-8'), 'UTF-8')
    : '';
if ($userName === '') {
    $userName = $userEmail;
}
$role = $currentUser['role'] ?? '';
$roleLabel = $role === 'admin' ? 'Quản trị viên' : 'Kỹ thuật viên';
$baseUrl = defined('BASE_URL') ? BASE_URL : '/DoAn_DienLanh/scr';
$dashboardUrl = $role === 'admin'
    ? '/DoAn_DienLanh/scr/admin/dashboard'
    : '/DoAn_DienLanh/scr/technician/dashboard';
$homeUrl = $baseUrl . '/home';
$flashMessages = $this->getFlash();
$currentRoute = trim((string) ($_GET['url'] ?? ''), '/');
$routeSegments = explode('/', $currentRoute);
$routePage = $routeSegments[1] ?? '';
$activeSidebarRoute = $currentRoute;
$routeAliases = [
    'admin/requestDetail' => 'admin/requests',
    'admin/categoryCreate' => 'admin/categories',
    'admin/categoryEdit' => 'admin/categories',
    'admin/serviceCreate' => 'admin/services',
    'admin/serviceEdit' => 'admin/services',
    'admin/requestCreate' => 'admin/requests',
    'admin/requestEdit' => 'admin/requests',
    'part/create' => 'part/index',
    'part/edit' => 'part/index',
    'technician/create' => 'technician/index',
    'technician/edit' => 'technician/index',
    'technician/detail' => 'technician/jobs',
    'customer/detail' => 'customer/index',
];
foreach ($routeAliases as $routePrefix => $navRoute) {
    if ($currentRoute === $routePrefix || strpos($currentRoute, $routePrefix . '/') === 0) {
        $activeSidebarRoute = $navRoute;
        break;
    }
}
$routeTitles = [
    'dashboard' => 'Tổng quan',
    'requests' => 'Yêu cầu',
    'requestDetail' => 'Chi tiết yêu cầu',
    'schedule' => 'Lịch kỹ thuật viên',
    'reviews' => 'Đánh giá',
    'categories' => 'Nhóm dịch vụ',
    'services' => 'Dịch vụ',
    'technicians' => 'Kỹ thuật viên',
    'customers' => 'Khách hàng',
    'index' => 'Tổng quan',
    'parts' => 'Linh kiện',
];
$pageTitle = $pageTitle ?? ($title ?? ($routeTitles[$routePage] ?? 'Quản trị hệ thống'));
if ($currentRoute === 'admin/dashboard' || $pageTitle === 'Dashboard') {
    $pageTitle = 'Tổng quan';
}
$breadcrumb = $breadcrumb ?? $pageTitle;
$pageDescriptions = [
    'admin/dashboard' => 'Theo dõi hoạt động và các yêu cầu dịch vụ.',
    'admin/requests' => 'Tiếp nhận, theo dõi và điều phối yêu cầu dịch vụ.',
    'admin/schedule' => 'Theo dõi lịch làm việc và phân công kỹ thuật viên.',
    'admin/reviews' => 'Theo dõi đánh giá và phản hồi của khách hàng.',
    'admin/categories' => 'Quản lý các nhóm dịch vụ hiện có.',
    'admin/services' => 'Quản lý danh sách dịch vụ và thông tin dịch vụ.',
    'report/index' => 'Tổng hợp doanh thu và hiệu quả hoạt động.',
    'technician/dashboard' => 'Theo dõi công việc và lịch làm việc của bạn.',
];
$descriptionRoute = implode('/', array_slice($routeSegments, 0, 2));
$pageDesc = $pageDesc ?? ($pageDescriptions[$descriptionRoute] ?? '');
$pendingRequestCount = isset($stats['pending'])
    ? (int) $stats['pending']
    : ($role === 'admin' ? $this->model('AdminRequestModel')->countAll(['status' => 'pending']) : 0);
$sidebarGroups = $role === 'admin'
    ? [
        ['KHÔNG GIAN QUẢN TRỊ', [
            ['Tổng quan', '/DoAn_DienLanh/scr/admin/dashboard', 'grid'],
        ]],
        ['VẬN HÀNH', [
            ['Yêu cầu', '/DoAn_DienLanh/scr/admin/requests', 'request', $pendingRequestCount],
            ['Lịch kỹ thuật viên', '/DoAn_DienLanh/scr/admin/schedule', 'calendar'],
            ['Đánh giá', '/DoAn_DienLanh/scr/admin/reviews', 'star'],
        ]],
        ['DANH MỤC', [
            ['Nhóm dịch vụ', '/DoAn_DienLanh/scr/admin/categories', 'folder'],
            ['Dịch vụ', '/DoAn_DienLanh/scr/admin/services', 'tool'],
            ['Linh kiện', '/DoAn_DienLanh/scr/part/index', 'cube'],
        ]],
        ['NHÂN SỰ & KHÁCH HÀNG', [
            ['Kỹ thuật viên', '/DoAn_DienLanh/scr/technician/index', 'users'],
            ['Khách hàng', '/DoAn_DienLanh/scr/customer/index', 'user'],
        ]],
        ['BÁO CÁO', [
            ['Tổng quan', '/DoAn_DienLanh/scr/report/index', 'chart'],
            ['Dịch vụ', '/DoAn_DienLanh/scr/report/services', 'tool'],
            ['Kỹ thuật viên', '/DoAn_DienLanh/scr/report/technicians', 'users'],
            ['Trạng thái', '/DoAn_DienLanh/scr/report/status', 'chart'],
            ['Linh kiện', '/DoAn_DienLanh/scr/report/parts', 'cube'],
        ]],
    ]
    : [
        ['CÔNG VIỆC', [
            ['Tổng quan', '/DoAn_DienLanh/scr/technician/dashboard', 'grid'],
            ['Việc của tôi', '/DoAn_DienLanh/scr/technician/jobs', 'request'],
            ['Lịch làm việc', '/DoAn_DienLanh/scr/technician/schedule', 'calendar'],
        ]],
    ];
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/DoAn_DienLanh/scr/public/css/style.css">
</head>
<body class="admin-layout <?= $role === 'technician' ? 'technician-layout' : '' ?>">
    <button class="admin-sidebar-overlay" type="button" aria-label="Đóng menu"></button>
    <aside class="admin-sidebar" id="admin-sidebar" aria-label="Điều hướng quản trị">
        <a class="admin-sidebar-brand" href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8') ?>">
            <span class="admin-brand-mark"><?= icon('snow') ?></span>
            <span class="admin-brand-copy"><strong>Điện Lạnh</strong><small>TẠI NHÀ</small></span>
        </a>
        <nav class="admin-sidebar-nav">
            <?php foreach ($sidebarGroups as [$groupTitle, $items]): ?>
                <section class="admin-nav-group">
                    <h2><?= htmlspecialchars($groupTitle, ENT_QUOTES, 'UTF-8') ?></h2>
                    <?php foreach ($items as $item): ?>
                        <?php list($label, $url, $iconName) = $item; ?>
                        <?php
                        $navPath = (string) parse_url($url, PHP_URL_PATH);
                        $navRoute = preg_replace('#^.*/scr/#', '', $navPath);
                        $isNavActive = $activeSidebarRoute === $navRoute
                            || strpos($activeSidebarRoute, $navRoute . '/') === 0;
                        ?>
                        <a class="admin-nav-link <?= $isNavActive ? 'is-active' : '' ?>" href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>"<?= $isNavActive ? ' aria-current="page"' : '' ?>>
                            <?= icon($iconName) ?>
                            <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                            <?php if ($label === 'Yêu cầu'): ?><span class="admin-nav-count"><?= (int) ($item[3] ?? 0) ?></span><?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </section>
            <?php endforeach; ?>
        </nav>
        <a class="admin-sidebar-home" href="<?= htmlspecialchars($homeUrl, ENT_QUOTES, 'UTF-8') ?>">
            <span>Xem trang chủ</span><?= icon('arrow') ?>
        </a>
    </aside>
    <div class="admin-shell">
        <header class="admin-topbar">
            <button class="admin-menu-toggle" type="button" aria-label="Menu" aria-expanded="false" aria-controls="admin-sidebar">
                Menu
            </button>
            <div class="admin-breadcrumb">
                <a href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8') ?>"><?= $role === 'admin' ? 'Quản trị' : 'Kỹ thuật viên' ?></a>
                <?= icon('chevron-right') ?>
                <strong><?= htmlspecialchars($breadcrumb, ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <button class="button-outline admin-topbar-button" type="button" data-history-back data-fallback="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8') ?>">Quay lại</button>
            <div class="admin-user-menu">
                <button class="admin-user-trigger" type="button" aria-label="Mở menu tài khoản" aria-expanded="false" aria-controls="admin-user-dropdown">
                    <span class="admin-user-avatar"><?= htmlspecialchars($avatarInitial, ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="admin-user-trigger-details">
                        <strong><?= htmlspecialchars((string) $userName, ENT_QUOTES, 'UTF-8') ?></strong>
                        <small><?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') ?></small>
                    </span>
                    <?= icon('chevron-down') ?>
                </button>
                <div class="admin-user-dropdown" id="admin-user-dropdown" hidden>
                    <div class="admin-user-details">
                        <div class="admin-user-dropdown-name"><?= htmlspecialchars((string) $userName, ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="admin-user-dropdown-role"><?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="admin-user-dropdown-email"><?= htmlspecialchars($userEmail, ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div class="admin-user-dropdown-links">
                        <a href="/DoAn_DienLanh/scr/profile/index">Hồ sơ</a>
                        <a href="/DoAn_DienLanh/scr/auth/logout">Đăng xuất</a>
                    </div>
                </div>
            </div>
        </header>
        <main class="admin-main">
            <?php if ($currentRoute === 'admin/dashboard'): ?>
                <div class="page-heading page-head">
                    <h1><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></h1>
                    <p><?= htmlspecialchars($pageDesc, ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            <?php endif; ?>
            <?php foreach ($flashMessages as $type => $message): ?>
                <div class="flash flash-<?= htmlspecialchars((string) $type, ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endforeach; ?>

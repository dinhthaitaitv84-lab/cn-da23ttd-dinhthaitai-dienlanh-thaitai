<?php
require_once __DIR__ . '/../partials/icons.php';
$pageTitle = $title ?? 'Dịch vụ điện lạnh tại nhà';
$currentUser = $_SESSION['user'] ?? null;
$baseUrl = defined('BASE_URL') ? BASE_URL : '/DoAn_DienLanh/scr';
$userRole = $currentUser['role'] ?? '';
$dashboardUrl = $userRole === 'admin'
    ? $baseUrl . '/admin/dashboard'
    : $baseUrl . '/technician/dashboard';
$currentRoute = trim((string) ($_GET['url'] ?? 'home'), '/');
$routeParts = explode('/', $currentRoute);
$currentPage = strtolower(explode('/', $currentRoute)[0] ?: 'home');
$isAuthPage = in_array($currentRoute, ['auth/login', 'auth/register'], true);
$isProfilePage = $currentPage === 'profile';
$isHomePage = $currentPage === 'home';
$showMobileRequestCta = in_array($currentPage, ['home', 'service'], true)
    && ($currentUser === null || $userRole === 'customer');
$mobileRequestUrl = $userRole === 'customer'
    ? $baseUrl . '/request/create'
    : $baseUrl . '/auth/login';
$flashMessages = $this->getFlash();
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/DoAn_DienLanh/scr/public/css/style.css">
</head>
<body class="<?= trim(($showMobileRequestCta ? 'has-mobile-request-cta ' : '') . ($isAuthPage ? 'auth-page ' : '') . ($isProfilePage ? 'profile-page ' : '') . ($isHomePage ? 'home-page' : '')) ?>">
    <header class="site-header">
        <nav class="navbar" aria-label="Điều hướng chính">
            <a class="brand" href="<?= htmlspecialchars($baseUrl . '/home', ENT_QUOTES, 'UTF-8') ?>">
                <span class="brand-mark"><?= icon('snow') ?></span>
                <span class="brand-copy"><strong>Điện Lạnh Tại Nhà</strong><small>Dịch vụ điện, điện lạnh</small></span>
            </a>
            <button class="nav-toggle" type="button" aria-label="Menu" aria-expanded="false" aria-controls="primary-navigation">
                Menu
            </button>
            <div class="nav-links" id="primary-navigation">
                <?php if (in_array($userRole, ['admin', 'technician'], true)): ?>
                    <a class="<?= $currentPage === 'home' ? 'is-active' : '' ?>" href="<?= htmlspecialchars($baseUrl . '/home', ENT_QUOTES, 'UTF-8') ?>">Trang chủ</a>
                    <a class="<?= $currentPage === 'service' ? 'is-active' : '' ?>" href="<?= htmlspecialchars($baseUrl . '/service', ENT_QUOTES, 'UTF-8') ?>">Dịch vụ</a>
                    <a class="<?= in_array($currentPage, ['admin', 'technician'], true) ? 'is-active' : '' ?>" href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8') ?>">Quản trị</a>
                    <a class="nav-account-link" href="<?= htmlspecialchars($baseUrl . '/auth/logout', ENT_QUOTES, 'UTF-8') ?>">Đăng xuất</a>
                <?php elseif ($userRole === 'customer'): ?>
                    <a class="<?= $currentPage === 'home' ? 'is-active' : '' ?>" href="<?= htmlspecialchars($baseUrl . '/home', ENT_QUOTES, 'UTF-8') ?>">Trang chủ</a>
                    <a class="<?= $currentPage === 'service' ? 'is-active' : '' ?>" href="<?= htmlspecialchars($baseUrl . '/service', ENT_QUOTES, 'UTF-8') ?>">Dịch vụ</a>
                    <a class="nav-request-link <?= $currentPage === 'request' && ($routeParts[1] ?? '') === 'create' ? 'is-active' : '' ?>" href="<?= htmlspecialchars($baseUrl . '/request/create', ENT_QUOTES, 'UTF-8') ?>">Gửi yêu cầu</a>
                    <a class="<?= $currentPage === 'request' && ($routeParts[1] ?? '') !== 'create' ? 'is-active' : '' ?>" href="<?= htmlspecialchars($baseUrl . '/request/mine', ENT_QUOTES, 'UTF-8') ?>">Yêu cầu</a>
                    <a class="<?= in_array($currentPage, ['device'], true) ? 'is-active' : '' ?>" href="<?= htmlspecialchars($baseUrl . '/device/index', ENT_QUOTES, 'UTF-8') ?>">Thiết bị</a>
                    <a class="<?= $currentPage === 'profile' ? 'is-active' : '' ?>" href="<?= htmlspecialchars($baseUrl . '/profile/index', ENT_QUOTES, 'UTF-8') ?>">Hồ sơ</a>
                    <a class="nav-account-link" href="<?= htmlspecialchars($baseUrl . '/auth/logout', ENT_QUOTES, 'UTF-8') ?>">Đăng xuất</a>
                <?php else: ?>
                    <a class="<?= $currentPage === 'home' ? 'is-active' : '' ?>" href="<?= htmlspecialchars($baseUrl . '/home', ENT_QUOTES, 'UTF-8') ?>">Trang chủ</a>
                    <a class="<?= $currentPage === 'service' ? 'is-active' : '' ?>" href="<?= htmlspecialchars($baseUrl . '/service', ENT_QUOTES, 'UTF-8') ?>">Dịch vụ</a>
                    <a class="nav-request-link <?= $currentPage === 'auth' ? 'is-active' : '' ?>" href="<?= htmlspecialchars($baseUrl . '/auth/login', ENT_QUOTES, 'UTF-8') ?>">Gửi yêu cầu</a>
                    <a class="nav-account-link <?= $currentPage === 'auth' && ($routeParts[1] ?? '') === 'login' ? 'is-active' : '' ?>" href="<?= htmlspecialchars($baseUrl . '/auth/login', ENT_QUOTES, 'UTF-8') ?>">Đăng nhập</a>
                    <a class="nav-account-link <?= $currentPage === 'auth' && ($routeParts[1] ?? '') === 'register' ? 'is-active' : '' ?>" href="<?= htmlspecialchars($baseUrl . '/auth/register', ENT_QUOTES, 'UTF-8') ?>">Đăng ký</a>
                <?php endif; ?>
            </div>
        </nav>
        <?php if ($currentPage === 'home'): ?>
            <div class="site-notice-bar">
                <div class="site-notice-inner">
                    <strong>DỊCH VỤ TẠI NHÀ</strong>
                    <span>Sửa chữa điện, điện lạnh tại nhà trong <?= htmlspecialchars(SERVICE_AREA_TEXT, ENT_QUOTES, 'UTF-8') ?>.</span>
                    <span class="site-notice-hours">Hỗ trợ: <?= htmlspecialchars(CONTACT_HOURS, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            </div>
        <?php endif; ?>
    </header>
    <main class="container">
        <?php foreach ($flashMessages as $type => $message): ?>
            <div class="flash flash-<?= htmlspecialchars((string) $type, ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endforeach; ?>

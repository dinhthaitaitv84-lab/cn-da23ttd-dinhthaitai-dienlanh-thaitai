<?php
define('APP_ENV', getenv('APP_ENV') === 'production' ? 'production' : 'development');
$baseUrl = '/' . trim(getenv('APP_BASE_URL') ?: '/DoAn_DienLanh/scr', '/');
define('BASE_URL', $baseUrl === '/' ? '' : $baseUrl);
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'dienlanh_db');
define('DB_USER', getenv('DB_USER') !== false ? getenv('DB_USER') : 'root');
define('DB_PASSWORD', getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '');
define('CONTACT_HOTLINE', getenv('CONTACT_HOTLINE') ?: '0355 527 497');
define('CONTACT_EMAIL', getenv('CONTACT_EMAIL') ?: 'lienhe@dienlanhtainha.vn');
define('CONTACT_ADDRESS', getenv('CONTACT_ADDRESS') ?: '123 Đường Nguyễn Đáng, Phường 7, TP. Trà Vinh, tỉnh Trà Vinh');
define('CONTACT_HOURS', getenv('CONTACT_HOURS') ?: '07:30 - 20:00 hằng ngày');
define('SERVICE_AREA_TEXT', 'Trà Vinh, Vĩnh Long và Bến Tre');
define('SERVICE_AREA_KEYWORDS', ['trà vinh', 'tra vinh', 'vĩnh long', 'vinh long', 'bến tre', 'ben tre']);

if (APP_ENV === 'production') {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

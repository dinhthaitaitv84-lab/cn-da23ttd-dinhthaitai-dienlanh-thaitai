
<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/controllers/Controller.php';

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
$forwardedProto = strtolower(trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0]));
$isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
    || $forwardedProto === 'https';
$basePath = parse_url(BASE_URL, PHP_URL_PATH);
session_name('DienLanhSession');
ini_set('session.cookie_secure', $isHttps ? '1' : '0');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => is_string($basePath) && $basePath !== '' ? rtrim($basePath, '/') . '/' : '/',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();
ob_start();

ini_set('log_errors', '1');
set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    if (in_array($severity, [E_WARNING, E_USER_WARNING, E_RECOVERABLE_ERROR, E_USER_ERROR], true)) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
    return false;
});

set_exception_handler(function ($exception) {
    restore_error_handler();
    app_log(sprintf(
        "%s in %s:%d\n%s",
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine(),
        $exception->getTraceAsString()
    ));

    if (!headers_sent()) {
        http_response_code(500);
    }
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    try {
        (new Controller())->view('errors/500', ['title' => 'Lỗi hệ thống']);
    } catch (Throwable $renderException) {
        echo '<!doctype html><html lang="vi"><meta charset="utf-8"><title>Lỗi hệ thống</title><h1>Đã xảy ra lỗi</h1><p>Vui lòng thử lại sau.</p></html>';
    }
});

require_once __DIR__ . '/config/database.php';

if (!empty($_SESSION['user'])) {
    (new Controller())->requireLogin();
}

$rawUrl = $_GET['url'] ?? 'home';
if (!is_string($rawUrl)) {
    http_response_code(404);
    (new Controller())->view('errors/404', ['title' => 'Không tìm thấy trang']);
    exit;
}
$rawUrl = trim($rawUrl, '/');
$segments = $rawUrl === '' ? ['home'] : explode('/', $rawUrl);
foreach ($segments as $segment) {
    if ($segment === '' || !preg_match('/^[A-Za-z0-9_-]+$/', $segment)) {
        http_response_code(404);
        (new Controller())->view('errors/404', ['title' => 'Không tìm thấy trang']);
        exit;
    }
}

$controllerName = ucfirst($segments[0]) . 'Controller';
$controllerFile = __DIR__ . '/app/controllers/' . $controllerName . '.php';
if (!is_file($controllerFile)) {
    http_response_code(404);
    (new Controller())->view('errors/404', ['title' => 'Không tìm thấy trang']);
    exit;
}

require_once $controllerFile;
$controller = new $controllerName();
$action = $segments[1] ?? 'index';
if (!method_exists($controller, $action)) {
    http_response_code(404);
    (new Controller())->view('errors/404', ['title' => 'Không tìm thấy trang']);
    exit;
}
$reflection = new ReflectionMethod($controller, $action);
if (!$reflection->isPublic()
    || $reflection->isStatic()
    || $reflection->getDeclaringClass()->getName() !== $controllerName
    || strncmp($action, '__', 2) === 0) {
    http_response_code(404);
    (new Controller())->view('errors/404', ['title' => 'Không tìm thấy trang']);
    exit;
}

$arguments = array_slice($segments, 2, 4);
call_user_func_array([$controller, $action], $arguments);
?>
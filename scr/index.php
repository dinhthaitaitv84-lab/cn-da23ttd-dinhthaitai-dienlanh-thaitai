
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
ini_set('session.cookie_secure', !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? '1' : '0');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => BASE_URL . '/',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
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
    $logDirectory = __DIR__ . DIRECTORY_SEPARATOR . 'logs';
    if (!is_dir($logDirectory) && !@mkdir($logDirectory, 0750, true) && !is_dir($logDirectory)) {
        error_log('Unable to create application error log directory.');
    } else {
        $message = sprintf(
            "[%s] %s in %s:%d\n%s\n",
            date('Y-m-d H:i:s'),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );
        if (error_log($message, 3, $logDirectory . DIRECTORY_SEPARATOR . 'error.log') === false) {
            error_log('Unable to write application error log.');
        }
    }

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
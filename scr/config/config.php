<?php
$environment = getenv('APP_ENV') ?: 'development';
$environment = in_array($environment, ['development', 'production'], true)
    ? $environment
    : 'development';

$configuration = [
    'APP_ENV' => $environment,
    'DB_HOST' => getenv('DB_HOST') ?: 'localhost',
    'DB_NAME' => getenv('DB_NAME') ?: 'dienlanh_db',
    'DB_USER' => getenv('DB_USER') !== false ? getenv('DB_USER') : 'root',
    'DB_PASS' => getenv('DB_PASS') !== false ? getenv('DB_PASS') : '',
    'DB_PORT' => getenv('DB_PORT') !== false ? (int) getenv('DB_PORT') : 3306,
    'BASE_URL' => getenv('BASE_URL') ?: getenv('APP_BASE_URL') ?: '',
    'TIMEZONE' => getenv('TIMEZONE') ?: 'Asia/Ho_Chi_Minh',
];

$localConfigurationFile = __DIR__ . DIRECTORY_SEPARATOR . 'config.local.php';
if (is_file($localConfigurationFile)) {
    $localConfiguration = require $localConfigurationFile;
    if (is_array($localConfiguration)) {
        $configuration = array_replace($configuration, $localConfiguration);
    }
}
if (!in_array($configuration['APP_ENV'], ['development', 'production'], true)) {
    $configuration['APP_ENV'] = 'development';
}

$protocol = 'http';
$forwardedProtocol = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
if (is_string($forwardedProtocol) && in_array(strtolower(trim(explode(',', $forwardedProtocol)[0])), ['http', 'https'], true)) {
    $protocol = strtolower(trim(explode(',', $forwardedProtocol)[0]));
} elseif (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
    $protocol = 'https';
}

$host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');
if (!is_string($host) || !preg_match('/^[A-Za-z0-9.-]+(?::\d{1,5})?$/', $host)) {
    $host = 'localhost';
}

$scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
$scriptDirectory = str_replace('\\', '/', dirname((string) $scriptName));
$scriptDirectory = $scriptDirectory === '/' || $scriptDirectory === '.' ? '' : rtrim($scriptDirectory, '/');
$configuredBaseUrl = trim((string) $configuration['BASE_URL']);
if ($configuredBaseUrl === '') {
    $configuredBaseUrl = $protocol . '://' . $host . $scriptDirectory;
} elseif (strpos($configuredBaseUrl, '//') === 0) {
    $configuredBaseUrl = $protocol . ':' . $configuredBaseUrl;
} elseif (strpos($configuredBaseUrl, '/') === 0) {
    $configuredBaseUrl = $protocol . '://' . $host . rtrim($configuredBaseUrl, '/');
} else {
    $configuredBaseUrl = rtrim($configuredBaseUrl, '/');
}

foreach ($configuration as $name => $value) {
    if ($name === 'BASE_URL') {
        $value = $configuredBaseUrl;
    }
    if ($name === 'DB_PORT' && ((int) $value < 1 || (int) $value > 65535)) {
        $value = 3306;
    }
    if (!defined($name)) {
        define($name, $value);
    }
}

define('CONTACT_HOTLINE', getenv('CONTACT_HOTLINE') ?: '0355 527 497');
define('CONTACT_EMAIL', getenv('CONTACT_EMAIL') ?: 'lienhe@dienlanhtainha.vn');
define('CONTACT_ADDRESS', getenv('CONTACT_ADDRESS') ?: '123 Đường Nguyễn Đáng, Phường 7, TP. Trà Vinh, tỉnh Trà Vinh');
define('CONTACT_HOURS', getenv('CONTACT_HOURS') ?: '07:30 - 20:00 hằng ngày');
define('SERVICE_AREA_TEXT', 'Trà Vinh, Vĩnh Long và Bến Tre');
define('SERVICE_AREA_KEYWORDS', ['trà vinh', 'tra vinh', 'vĩnh long', 'vinh long', 'bến tre', 'ben tre']);

date_default_timezone_set(TIMEZONE);
ini_set('display_errors', APP_ENV === 'production' ? '0' : '1');
ini_set('display_startup_errors', APP_ENV === 'production' ? '0' : '1');
ini_set('log_errors', '1');
if (APP_ENV === 'production') {
    $productionLogDirectory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'logs';
    if ((!is_dir($productionLogDirectory)
            && !@mkdir($productionLogDirectory, 0750, true)
            && !is_dir($productionLogDirectory))
        || !is_writable($productionLogDirectory)) {
        error_log('Application log directory is not writable; using the server error log.');
    } else {
        $productionLogFile = $productionLogDirectory . DIRECTORY_SEPARATOR . 'error.log';
        if (!is_file($productionLogFile) || is_writable($productionLogFile)) {
            ini_set('error_log', $productionLogFile);
        } else {
            error_log('Application log file is not writable; using the server error log.');
        }
    }
}
error_reporting(E_ALL);

function url($path = '')
{
    $path = (string) $path;
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

function asset_url($path)
{
    $relativePath = ltrim((string) $path, '/\\');
    $filePath = dirname(__DIR__) . DIRECTORY_SEPARATOR
        . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
    $version = is_file($filePath) ? (string) filemtime($filePath) : '1';
    return url('/' . $relativePath) . '?v=' . rawurlencode($version);
}

function app_log($message)
{
    $entry = sprintf("[%s] %s\n", date('Y-m-d H:i:s'), (string) $message);
    $logDirectory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'logs';
    if (!is_dir($logDirectory) && !@mkdir($logDirectory, 0750, true) && !is_dir($logDirectory)) {
        error_log(trim($entry));
        return;
    }

    $logFile = $logDirectory . DIRECTORY_SEPARATOR . 'error.log';
    if (!is_writable($logDirectory) || (is_file($logFile) && !is_writable($logFile))
        || error_log($entry, 3, $logFile) === false) {
        error_log(trim($entry));
    }
}

function ini_size_bytes($setting)
{
    $value = trim((string) ini_get($setting));
    if ($value === '' || $value === '0') {
        return 0;
    }

    $unit = strtolower(substr($value, -1));
    $number = (float) $value;
    if ($unit === 'g') {
        $number *= 1024;
        $unit = 'm';
    }
    if ($unit === 'm') {
        $number *= 1024;
        $unit = 'k';
    }
    if ($unit === 'k') {
        $number *= 1024;
    }
    return (int) $number;
}

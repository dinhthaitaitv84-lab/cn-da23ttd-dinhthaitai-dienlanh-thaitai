<?php
require_once dirname(__DIR__) . '/helpers/Paginator.php';

class Controller {
    // Gọi Model
    public function model($model) {
        if (!preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $model)) {
            throw new InvalidArgumentException('Tên model không hợp lệ.');
        }
        require_once dirname(__DIR__) . '/models/' . $model . '.php';
        return new $model();
    }

    // Gọi View và truyền dữ liệu
    public function view($path, $data = []) {
        if (!preg_match('/^[A-Za-z0-9_\/-]+$/', $path)) {
            throw new InvalidArgumentException('Đường dẫn view không hợp lệ.');
        }
        $viewFile = dirname(__DIR__) . '/views/' . $path . '.php';
        if (!is_file($viewFile)) {
            echo "Lỗi: Không tìm thấy view $path";
            return;
        }

        extract($data, EXTR_SKIP);
        $publicViewPrefixes = ['home/', 'service/', 'auth/', 'errors/'];
        $adminViewPrefixes = ['admin/', 'technician/', 'report/'];
        $isPublicView = false;
        foreach ($publicViewPrefixes as $prefix) {
            if (strpos($path, $prefix) === 0) {
                $isPublicView = true;
                break;
            }
        }
        $adminLayout = false;
        if (!$isPublicView) {
            foreach ($adminViewPrefixes as $prefix) {
                if (strpos($path, $prefix) === 0) {
                    $adminLayout = true;
                    break;
                }
            }
        }
        $layout = $adminLayout ? 'admin_' : '';
        require dirname(__DIR__) . '/views/layouts/' . $layout . 'header.php';
        require $viewFile;
        require dirname(__DIR__) . '/views/layouts/' . $layout . 'footer.php';
    }

    public function requireLogin() {
        if (empty($_SESSION['user'])) {
            $this->redirect('/DoAn_DienLanh/scr/auth/login');
        }

        $userId = (int) ($_SESSION['user']['id'] ?? 0);
        if ($userId < 1 || !$this->model('UserModel')->isActive($userId)) {
            $_SESSION = [];
            session_regenerate_id(true);
            $this->setFlash('error', 'Tài khoản đã bị khóa. Vui lòng liên hệ quản trị viên.');
            $this->redirect('/DoAn_DienLanh/scr/auth/login');
        }
    }

    public function requireRole($roles) {
        $this->requireLogin();
        $roles = (array) $roles;
        $role = $_SESSION['user']['role'] ?? '';

        if (!in_array($role, $roles, true)) {
            http_response_code(403);
            $this->view('errors/403', ['title' => 'Không có quyền truy cập']);
            exit;
        }
    }

    public function redirect($url) {
        header('Location: ' . $url);
        exit;
    }

    public function setFlash($type, $message) {
        if (!isset($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
            $_SESSION['flash'] = [];
        }

        $_SESSION['flash'][$type] = $message;
    }

    public function getFlash($type = null) {
        if (!isset($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
            return $type === null ? [] : null;
        }

        if ($type !== null) {
            if (!array_key_exists($type, $_SESSION['flash'])) {
                return null;
            }

            $message = $_SESSION['flash'][$type];
            unset($_SESSION['flash'][$type]);
            return $message;
        }

        $messages = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $messages;
    }

    public function csrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public function verifyCsrf() {
        $token = $_POST['csrf_token'] ?? '';
        return is_string($token)
            && isset($_SESSION['csrf_token'])
            && hash_equals($_SESSION['csrf_token'], $token);
    }

    protected function queryString($key, $maximumLength = 200)
    {
        $value = $_GET[$key] ?? '';
        return is_string($value) ? mb_substr(trim($value), 0, $maximumLength) : '';
    }

    protected function isAddressInServiceArea($address)
    {
        $lowerAddress = mb_strtolower($address, 'UTF-8');
        $normalizedAddress = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $lowerAddress);
        if ($normalizedAddress === false) {
            throw new RuntimeException('Không thể chuẩn hóa địa chỉ.');
        }

        foreach (SERVICE_AREA_KEYWORDS as $keyword) {
            $lowerKeyword = mb_strtolower($keyword, 'UTF-8');
            $normalizedKeyword = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $lowerKeyword);
            if ($normalizedKeyword === false) {
                throw new RuntimeException('Không thể chuẩn hóa từ khóa địa bàn.');
            }
            if (mb_strpos($lowerAddress, $lowerKeyword, 0, 'UTF-8') !== false
                || strpos($normalizedAddress, $normalizedKeyword) !== false) {
                return true;
            }
        }

        return false;
    }

    protected function queryInt($key, $minimum = 0)
    {
        $value = $_GET[$key] ?? null;
        if (is_int($value)) {
            return max($minimum, $value);
        }
        if (!is_string($value) || !preg_match('/^-?\d+$/', $value)) {
            return $minimum;
        }
        return max($minimum, (int) $value);
    }

    protected function paginationData($total)
    {
        $pagination = paginate($total, 10, $this->queryInt('page', 1));
        $baseQuery = [];
        foreach ($_GET as $key => $value) {
            if ($key !== 'page' && is_scalar($value)) {
                $baseQuery[$key] = (string) $value;
            }
        }
        return array_merge($pagination, [
            'total' => max(0, (int) $total),
            'baseQuery' => $baseQuery,
        ]);
    }

    protected function paginationReturnUrl($path)
    {
        $pageValue = $_POST['page'] ?? 1;
        $page = is_scalar($pageValue) && preg_match('/^\d+$/', (string) $pageValue)
            ? max(1, (int) $pageValue)
            : 1;
        $query = ['page' => $page];
        foreach (['search', 'low_stock', 'category_id', 'status', 'priority', 'technician_id', 'rating', 'from', 'to', 'week', 'date', 'period'] as $key) {
            $value = $_POST[$key] ?? null;
            if (is_scalar($value)) {
                $query[$key] = (string) $value;
            }
        }
        return $path . '?' . http_build_query($query);
    }

    public function uploadImage($file) {
        if (!is_array($file) || !isset($file['error'], $file['tmp_name'], $file['size'])) {
            throw new InvalidArgumentException('Thông tin tệp tải lên không hợp lệ.');
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Tải ảnh lên thất bại.');
        }

        if (!is_uploaded_file($file['tmp_name']) || $file['size'] > 2 * 1024 * 1024) {
            throw new RuntimeException('Ảnh không hợp lệ hoặc vượt quá 2MB.');
        }

        $fileInfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $fileInfo->file($file['tmp_name']);
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        if (!isset($extensions[$mimeType])) {
            throw new RuntimeException('Chỉ chấp nhận ảnh JPG, PNG hoặc WEBP.');
        }

        $uploadDirectory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads';
        if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
            throw new RuntimeException('Không thể tạo thư mục lưu ảnh.');
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mimeType];
        $destination = $uploadDirectory . DIRECTORY_SEPARATOR . $filename;
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new RuntimeException('Không thể lưu ảnh đã tải lên.');
        }

        return 'public/uploads/' . $filename;
    }
}
?>
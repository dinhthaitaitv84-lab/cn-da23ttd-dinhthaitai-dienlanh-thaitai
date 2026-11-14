<?php
class AuthController extends Controller
{
    public function login()
    {
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->verifyCsrf()) {
                $error = 'Yêu cầu không hợp lệ hoặc phiên làm việc đã hết hạn.';
            } else {
                $emailInput = $_POST['email'] ?? '';
                $password = $_POST['password'] ?? '';
                if (!is_string($emailInput) || !is_string($password)) {
                    $email = '';
                    $error = 'Email hoặc mật khẩu không chính xác.';
                } else {
                    $email = mb_strtolower(trim($emailInput));
                    $lock = $this->loginLock($email);
                    if ($lock > time()) {
                        $error = 'Đăng nhập tạm khóa. Vui lòng thử lại sau ' . (int) ceil(($lock - time()) / 60) . ' phút.';
                    } else {
                        $user = $this->model('UserModel')->login($email, $password);
                        if ($user !== false) {
                            unset($_SESSION['login_attempts'], $_SESSION['csrf_token']);
                            session_regenerate_id(true);
                            $_SESSION['user'] = $user;
                            $role = $user['role'] ?? '';
                            $destination = $role === 'admin'
                                ? url('/admin/dashboard')
                                : ($role === 'technician' ? url('/technician/dashboard') : url('/home'));
                            $this->redirect($destination);
                        }

                        $error = 'Email hoặc mật khẩu không chính xác.';
                        $this->recordLoginFailure($email);
                    }
                }
            }
        }

        $this->view('auth/login', ['error' => $error]);
    }

    private function loginLock($email)
    {
        $key = hash('sha256', $email);
        $attempt = $_SESSION['login_attempts'][$key] ?? [];
        return is_array($attempt) ? (int) ($attempt['locked_until'] ?? 0) : 0;
    }

    private function recordLoginFailure($email)
    {
        $now = time();
        $key = hash('sha256', $email);
        $attempt = $_SESSION['login_attempts'][$key] ?? ['failures' => []];
        if (!is_array($attempt)) {
            $attempt = ['failures' => []];
        }
        $failures = array_filter(
            (array) ($attempt['failures'] ?? []),
            function ($timestamp) use ($now) {
                return (int) $timestamp > $now - 600;
            }
        );
        $failures[] = $now;
        $_SESSION['login_attempts'][$key] = [
            'failures' => array_values($failures),
            'locked_until' => count($failures) >= 5 ? $now + 600 : 0,
        ];
    }

    public function register()
    {
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->verifyCsrf()) {
                $error = 'Yêu cầu không hợp lệ hoặc phiên làm việc đã hết hạn.';
            } else {
                $fullNameInput = $_POST['full_name'] ?? $_POST['fullname'] ?? null;
                $emailInput = $_POST['email'] ?? null;
                $password = $_POST['password'] ?? null;
                $phoneInput = $_POST['phone'] ?? null;
                $addressInput = $_POST['address'] ?? null;

                if (!is_string($fullNameInput) || !is_string($emailInput)
                    || !is_string($password) || !is_string($phoneInput) || !is_string($addressInput)) {
                    $error = 'Vui lòng kiểm tra thông tin đăng ký.';
                } else {
                    $fullName = trim($fullNameInput);
                    $email = mb_strtolower(trim($emailInput));
                    $phone = trim($phoneInput);
                    $address = trim($addressInput);

                    if ($fullName === '' || mb_strlen($fullName) > 150
                        || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 191
                        || strlen($password) < 8 || strlen($password) > 255
                        || $phone === '' || mb_strlen($phone) > 30
                        || $address === '' || mb_strlen($address) > 500) {
                        $error = 'Vui lòng kiểm tra họ tên, email, mật khẩu, số điện thoại và địa chỉ.';
                    } else {
                        $user = $this->model('UserModel')->register(
                            $fullName,
                            $email,
                            $password,
                            $phone,
                            $address
                        );

                        if ($user !== false) {
                            unset($_SESSION['csrf_token']);
                            session_regenerate_id(true);
                            $_SESSION['user'] = $user;
                            $this->redirect(url('/home'));
                        }

                        $error = 'Không thể tạo tài khoản. Email có thể đã được sử dụng.';
                    }
                }
            }
        }

        $this->view('auth/register', ['error' => $error]);
    }

    public function logout()
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
            session_destroy();
        }
        header('Location: ' . url('/home'));
        exit;
    }
}
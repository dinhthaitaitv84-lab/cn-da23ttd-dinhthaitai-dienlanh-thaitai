<?php
class ProfileController extends Controller
{
    private function authorize()
    {
        $this->requireLogin();
    }

    public function index()
    {
        $this->authorize();
        $user = $this->model('UserModel')->findProfile((int) $_SESSION['user']['id']);
        if (!$user) {
            $this->redirect(url('/auth/logout'));
        }
        $this->view('profile/index', ['title' => 'Hồ sơ cá nhân', 'profile' => $user]);
    }

    public function update()
    {
        $this->authorize();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf()) {
            $this->setFlash('error', 'Yêu cầu không hợp lệ hoặc phiên làm việc đã hết hạn.');
            $this->redirect(url('/profile/index'));
        }
        $fullName = $this->postString('full_name');
        $phone = $this->postString('phone');
        $address = $this->postString('address');
        if ($fullName === null || $phone === null || $address === null
            || trim($fullName) === '' || mb_strlen(trim($fullName)) > 150
            || mb_strlen(trim($phone)) > 30 || mb_strlen(trim($address)) > 500) {
            $this->setFlash('error', 'Vui lòng kiểm tra họ tên, số điện thoại và địa chỉ.');
            $this->redirect(url('/profile/index'));
        }

        $address = trim($address);
        if ($address !== '' && !$this->isAddressInServiceArea($address)) {
            $this->setFlash('error', 'Địa chỉ nằm ngoài khu vực phục vụ. Hiện chỉ nhận yêu cầu tại ' . SERVICE_AREA_TEXT . '.');
            $this->redirect(url('/profile/index'));
        }

        $userId = (int) $_SESSION['user']['id'];
        if (!$this->model('UserModel')->updateProfile(
            $userId,
            trim($fullName),
            trim($phone),
            $address
        )) {
            $this->setFlash('error', 'Không thể cập nhật hồ sơ.');
            $this->redirect(url('/profile/index'));
        }
        $_SESSION['user']['fullname'] = trim($fullName);
        $_SESSION['user']['full_name'] = trim($fullName);
        $_SESSION['user']['phone'] = trim($phone);
        $_SESSION['user']['address'] = $address;
        $this->setFlash('success', 'Đã cập nhật hồ sơ.');
        $this->redirect(url('/profile/index'));
    }

    public function changePassword()
    {
        $this->authorize();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf()) {
            $this->setFlash('error', 'Yêu cầu không hợp lệ hoặc phiên làm việc đã hết hạn.');
            $this->redirect(url('/profile/index'));
        }
        $currentPassword = $this->postString('current_password');
        $password = $this->postString('password');
        $confirmation = $this->postString('password_confirmation');
        if ($currentPassword === null || $password === null || $confirmation === null
            || strlen($password) < 6 || strlen($password) > 255
            || !hash_equals($password, $confirmation)) {
            $this->setFlash('error', 'Mật khẩu mới phải có ít nhất 6 ký tự và hai lần nhập phải khớp.');
            $this->redirect(url('/profile/index'));
        }

        if (!$this->model('UserModel')->changePassword(
            (int) $_SESSION['user']['id'],
            $currentPassword,
            $password
        )) {
            $this->setFlash('error', 'Mật khẩu hiện tại không chính xác.');
            $this->redirect(url('/profile/index'));
        }
        session_regenerate_id(true);
        $this->setFlash('success', 'Đã đổi mật khẩu.');
        $this->redirect(url('/profile/index'));
    }

    private function postString($key)
    {
        $value = $_POST[$key] ?? '';
        return is_string($value) ? $value : null;
    }
}

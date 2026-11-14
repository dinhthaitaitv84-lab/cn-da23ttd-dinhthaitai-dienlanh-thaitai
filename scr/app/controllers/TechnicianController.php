<?php
class TechnicianController extends Controller
{
    private function forwardWork($action, $arguments = [])
    {
        require_once __DIR__ . '/TechnicianWorkController.php';
        $controller = new TechnicianWorkController();
        call_user_func_array([$controller, $action], $arguments);
    }

    public function dashboard()
    {
        $this->forwardWork('dashboard');
    }

    public function jobs()
    {
        $this->forwardWork('jobs');
    }

    public function detail($id = 0)
    {
        $this->forwardWork('detail', [$id]);
    }

    public function updateStatus($id = 0)
    {
        $this->forwardWork('updateStatus', [$id]);
    }

    public function saveReport($id = 0)
    {
        $this->forwardWork('saveReport', [$id]);
    }

    public function createQuote($id = 0)
    {
        $this->forwardWork('createQuote', [$id]);
    }

    public function schedule()
    {
        $this->forwardWork('schedule');
    }

    private function authorize()
    {
        $this->requireRole('admin');
    }

    private function verifyPost()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf()) {
            $this->setFlash('error', 'Yêu cầu không hợp lệ hoặc phiên làm việc đã hết hạn.');
            $this->redirect(url('/technician/index'));
        }
    }

    public function index()
    {
        $this->authorize();
        $search = $this->queryString('search', 100);
        $model = $this->model('TechnicianModel');
        $pagination = $this->paginationData($model->countAll($search));
        $this->view('admin/technicians/index', [
            'title' => 'Quản lý kỹ thuật viên',
            'technicians' => $model->getAll($search, $pagination['perPage'], $pagination['offset']),
            'search' => $search,
        ] + $pagination);
    }

    public function create()
    {
        $this->authorize();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyPost();
            $data = $this->technicianInput(true);
            if ($data === false) {
                $this->setFlash('error', 'Vui lòng kiểm tra thông tin và mật khẩu (ít nhất 8 ký tự).');
                $this->redirect(url('/technician/create'));
            }
            $result = $this->model('TechnicianModel')->create($data);
            if ($result === 'duplicate') {
                $this->setFlash('error', 'Email đã được sử dụng.');
                $this->redirect(url('/technician/create'));
            }
            $this->setFlash('success', 'Đã thêm kỹ thuật viên.');
            $this->redirect(url('/technician/index'));
        }
        $this->view('admin/technicians/form', [
            'title' => 'Thêm kỹ thuật viên',
            'technician' => null,
        ]);
    }

    public function edit($id = 0)
    {
        $this->authorize();
        $model = $this->model('TechnicianModel');
        $technician = $model->find((int) $id);
        if (!$technician) {
            $this->setFlash('error', 'Không tìm thấy kỹ thuật viên.');
            $this->redirect(url('/technician/index'));
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyPost();
            $data = $this->technicianInput(false);
            if ($data === false) {
                $this->setFlash('error', 'Vui lòng kiểm tra thông tin kỹ thuật viên.');
                $this->redirect(url('/technician/edit/' . (int) $id));
            }
            $result = $model->update((int) $id, $data);
            if ($result === 'duplicate') {
                $this->setFlash('error', 'Email đã được sử dụng.');
            } elseif ($result === 'missing') {
                $this->setFlash('error', 'Không tìm thấy kỹ thuật viên.');
            } else {
                $this->setFlash('success', 'Đã cập nhật thông tin kỹ thuật viên.');
            }
            $this->redirect(url('/technician/index'));
        }
        $this->view('admin/technicians/form', [
            'title' => 'Sửa kỹ thuật viên',
            'technician' => $technician,
        ]);
    }

    public function status($id = 0)
    {
        $this->authorize();
        $this->verifyPost();
        $status = $_POST['status'] ?? '';
        if (!in_array($status, ['available', 'busy', 'off'], true)) {
            $this->setFlash('error', 'Trạng thái kỹ thuật viên không hợp lệ.');
        } elseif ($this->model('TechnicianModel')->setStatus((int) $id, $status)) {
            $this->setFlash('success', 'Đã cập nhật trạng thái kỹ thuật viên.');
        } else {
            $technician = $this->model('TechnicianModel')->find((int) $id);
            $this->setFlash($technician && $technician['technician_status'] === $status ? 'success' : 'error',
                $technician && $technician['technician_status'] === $status
                    ? 'Trạng thái kỹ thuật viên không thay đổi.'
                    : 'Không tìm thấy kỹ thuật viên.');
        }
        $this->redirect(url('/technician/index'));
    }

    public function accountStatus($id = 0)
    {
        $this->authorize();
        $this->verifyPost();
        $statusInput = $_POST['status'] ?? '';
        if (!is_string($statusInput) || !in_array($statusInput, ['0', '1'], true)) {
            $this->setFlash('error', 'Trạng thái tài khoản không hợp lệ.');
        } elseif ($this->model('TechnicianModel')->setAccountStatus((int) $id, (int) $statusInput)) {
            $status = (int) $statusInput;
            $this->setFlash('success', $status === 1 ? 'Đã mở khóa tài khoản.' : 'Đã khóa tài khoản.');
        } else {
            $status = (int) $statusInput;
            $technician = $this->model('TechnicianModel')->find((int) $id);
            $sameStatus = $technician && (int) $technician['account_status'] === $status;
            $this->setFlash($sameStatus ? 'success' : 'error',
                $sameStatus ? 'Trạng thái tài khoản không thay đổi.' : 'Không tìm thấy kỹ thuật viên.');
        }
        $this->redirect(url('/technician/index'));
    }

    public function resetPassword($id = 0)
    {
        $this->authorize();
        $this->verifyPost();
        $password = $_POST['password'] ?? '';
        if (!is_string($password) || strlen($password) < 8 || strlen($password) > 255) {
            $this->setFlash('error', 'Mật khẩu phải có từ 8 đến 255 ký tự.');
        } elseif ($this->model('TechnicianModel')->resetPassword((int) $id, $password)) {
            $this->setFlash('success', 'Đã đặt lại mật khẩu.');
        } else {
            $this->setFlash($this->model('TechnicianModel')->find((int) $id) ? 'success' : 'error',
                'Không tìm thấy kỹ thuật viên hoặc mật khẩu không thay đổi.');
        }
        $this->redirect(url('/technician/index'));
    }

    public function delete($id = 0)
    {
        $this->authorize();
        $this->verifyPost();
        $result = $this->model('TechnicianModel')->delete((int) $id);
        if ($result === 'deleted') {
            $this->setFlash('success', 'Đã xóa kỹ thuật viên.');
        } elseif ($result === 'assigned') {
            $this->setFlash('error', 'Không thể xóa kỹ thuật viên đã được phân công yêu cầu.');
        } elseif ($result === 'logged') {
            $this->setFlash('error', 'Không thể xóa kỹ thuật viên đã được ghi nhận trong lịch sử yêu cầu.');
        } else {
            $this->setFlash('error', 'Không tìm thấy kỹ thuật viên.');
        }
        $this->redirect(url($this->paginationReturnUrl('/technician/index')));
    }

    private function technicianInput($includePassword)
    {
        $data = [
            'full_name' => trim($_POST['full_name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'specialty' => trim($_POST['specialty'] ?? ''),
            'experience_years' => filter_var($_POST['experience_years'] ?? null, FILTER_VALIDATE_INT),
            'status' => $_POST['status'] ?? '',
        ];
        if ($includePassword) {
            $data['password'] = $_POST['password'] ?? '';
        }
        if ($data['full_name'] === '' || mb_strlen($data['full_name']) > 150
            || !filter_var($data['email'], FILTER_VALIDATE_EMAIL) || strlen($data['email']) > 191
            || mb_strlen($data['phone']) > 30 || mb_strlen($data['specialty']) > 150
            || $data['experience_years'] === false || $data['experience_years'] < 0
            || $data['experience_years'] > 65535
            || !in_array($data['status'], ['available', 'busy', 'off'], true)) {
            return false;
        }
        if ($includePassword && (!is_string($data['password']) || strlen($data['password']) < 8 || strlen($data['password']) > 255)) {
            return false;
        }
        return $data;
    }
}

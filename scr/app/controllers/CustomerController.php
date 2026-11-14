<?php
class CustomerController extends Controller
{
    private function authorize()
    {
        $this->requireRole('admin');
    }

    public function index()
    {
        $this->authorize();
        $search = $this->queryString('search', 100);
        $model = $this->model('CustomerModel');
        $pagination = $this->paginationData($model->countAll($search));
        $this->view('admin/customers/index', [
            'title' => 'Quản lý khách hàng',
            'customers' => $model->getAll($search, $pagination['perPage'], $pagination['offset']),
            'search' => $search,
        ] + $pagination);
    }

    public function status($id = 0)
    {
        $this->authorize();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf()) {
            $this->setFlash('error', 'Yêu cầu không hợp lệ hoặc phiên làm việc đã hết hạn.');
            $this->redirect('/DoAn_DienLanh/scr/customer/index');
        }

        $statusInput = $_POST['status'] ?? '';
        if (!is_string($statusInput) || !in_array($statusInput, ['0', '1'], true)) {
            $this->setFlash('error', 'Trạng thái tài khoản không hợp lệ.');
        } elseif ($this->model('CustomerModel')->setStatus((int) $id, (int) $statusInput)) {
            $this->setFlash('success', $statusInput === '1' ? 'Đã mở khóa khách hàng.' : 'Đã khóa khách hàng.');
        } else {
            $this->setFlash('error', 'Không tìm thấy khách hàng.');
        }
        $this->redirect('/DoAn_DienLanh/scr/customer/index');
    }

    public function resetPassword($id = 0)
    {
        $this->authorize();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf()) {
            $this->setFlash('error', 'Yêu cầu không hợp lệ hoặc phiên làm việc đã hết hạn.');
            $this->redirect('/DoAn_DienLanh/scr/customer/index');
        }
        $password = $_POST['password'] ?? '';
        if (!is_string($password) || strlen($password) < 8 || strlen($password) > 255) {
            $this->setFlash('error', 'Mật khẩu phải có từ 8 đến 255 ký tự.');
        } elseif ($this->model('CustomerModel')->resetPassword((int) $id, $password)) {
            $this->setFlash('success', 'Đã đặt lại mật khẩu khách hàng.');
        } else {
            $this->setFlash('error', 'Không tìm thấy khách hàng.');
        }
        $this->redirect('/DoAn_DienLanh/scr/customer/detail/' . (int) $id);
    }

    public function detail($id = 0)
    {
        $this->authorize();
        $model = $this->model('CustomerModel');
        $pagination = $this->paginationData($model->countRequests((int) $id));
        $customer = $model->find((int) $id, $pagination['perPage'], $pagination['offset']);
        if (!$customer) {
            $this->setFlash('error', 'Không tìm thấy khách hàng.');
            $this->redirect('/DoAn_DienLanh/scr/customer/index');
        }
        $this->view('admin/customers/detail', [
            'title' => 'Khách hàng ' . $customer['full_name'],
            'customer' => $customer,
        ] + $pagination);
    }

    public function device($id = 0)
    {
        $this->authorize();
        $model = $this->model('CustomerModel');
        $pagination = $this->paginationData($model->countDeviceRepairs((int) $id));
        $device = $model->deviceHistory((int) $id, null, $pagination['perPage'], $pagination['offset']);
        if (!$device) {
            $this->setFlash('error', 'Không tìm thấy thiết bị.');
            $this->redirect('/DoAn_DienLanh/scr/customer/index');
        }
        $this->view('device/history', [
            'title' => 'Lịch sử thiết bị ' . $device['name'],
            'device' => $device,
        ] + $pagination);
    }
}

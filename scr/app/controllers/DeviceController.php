<?php
class DeviceController extends Controller
{
    public function index()
    {
        $this->requireRole('customer');
        $model = $this->model('DeviceModel');
        $pagination = $this->paginationData($model->countByCustomer((int) $_SESSION['user']['id']));
        $this->view('device/index', [
            'title' => 'Thiết bị',
            'devices' => $model->getByCustomer((int) $_SESSION['user']['id'], $pagination['perPage'], $pagination['offset']),
            'categories' => $this->model('CategoryModel')->getActive(),
        ] + $pagination);
    }

    public function detail($id = 0)
    {
        $this->requireRole('customer');
        $model = $this->model('CustomerModel');
        $total = $model->countDeviceRepairs((int) $id, (int) $_SESSION['user']['id']);
        $pagination = $this->paginationData($total);
        $device = $model->deviceHistory((int) $id, (int) $_SESSION['user']['id'], $pagination['perPage'], $pagination['offset']);
        if (!$device) {
            $this->setFlash('error', 'Không tìm thấy thiết bị hoặc Quý khách không có quyền xem thiết bị này.');
            $this->redirect('/DoAn_DienLanh/scr/device/index');
        }
        $this->view('device/history', [
            'title' => 'Lịch sử thiết bị ' . $device['name'],
            'device' => $device,
        ] + $pagination);
    }

    public function store()
    {
        $this->requireRole('customer');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf()) {
            $this->setFlash('error', 'Yêu cầu không hợp lệ hoặc phiên làm việc đã hết hạn.');
            $this->redirect('/DoAn_DienLanh/scr/device/index');
        }

        $data = $this->deviceInput();
        if (!$this->validDeviceInput($data)) {
            $this->setFlash('error', 'Vui lòng kiểm tra nhóm, tên, hãng, model và ghi chú thiết bị.');
            $this->redirect('/DoAn_DienLanh/scr/device/index');
        }

        if (!$this->model('DeviceModel')->create((int) $_SESSION['user']['id'], $data)) {
            $this->setFlash('error', 'Nhóm thiết bị không hợp lệ hoặc đã ngừng hoạt động.');
            $this->redirect('/DoAn_DienLanh/scr/device/index');
        }
        $this->setFlash('success', 'Đã thêm thiết bị.');
        $this->redirect('/DoAn_DienLanh/scr/device/index');
    }

    public function update($id = 0)
    {
        $this->requireRole('customer');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf()) {
            $this->setFlash('error', 'Yêu cầu không hợp lệ hoặc phiên làm việc đã hết hạn.');
            $this->redirect('/DoAn_DienLanh/scr/device/index');
        }

        $data = $this->deviceInput();
        if (!$this->validDeviceInput($data)) {
            $this->setFlash('error', 'Vui lòng kiểm tra nhóm, tên, hãng, model và ghi chú thiết bị.');
            $this->redirect('/DoAn_DienLanh/scr/device/index');
        }

        if (!$this->model('DeviceModel')->update((int) $id, (int) $_SESSION['user']['id'], $data)) {
            $this->setFlash('error', 'Không thể cập nhật thiết bị. Vui lòng kiểm tra quyền sở hữu và nhóm thiết bị.');
            $this->redirect('/DoAn_DienLanh/scr/device/index');
        }
        $this->setFlash('success', 'Đã cập nhật thiết bị.');
        $this->redirect('/DoAn_DienLanh/scr/device/index');
    }

    public function delete($id = 0)
    {
        $this->requireRole('customer');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf()) {
            $this->setFlash('error', 'Yêu cầu không hợp lệ hoặc phiên làm việc đã hết hạn.');
            $this->redirect('/DoAn_DienLanh/scr/device/index');
        }

        $result = $this->model('DeviceModel')->delete((int) $id, (int) $_SESSION['user']['id']);
        if ($result === 'deleted') {
            $this->setFlash('success', 'Đã xóa thiết bị.');
        } elseif ($result === 'in_use') {
            $this->setFlash('error', 'Không thể xóa thiết bị đã được gắn với yêu cầu dịch vụ.');
        } else {
            $this->setFlash('error', 'Không tìm thấy thiết bị hoặc Quý khách không có quyền xóa thiết bị này.');
        }
        $this->redirect($this->paginationReturnUrl('/DoAn_DienLanh/scr/device/index'));
    }

    private function deviceInput()
    {
        return [
            'category_id' => max(0, (int) ($_POST['category_id'] ?? 0)),
            'name' => trim($_POST['name'] ?? ''),
            'brand' => trim($_POST['brand'] ?? ''),
            'model' => trim($_POST['model'] ?? ''),
            'note' => trim($_POST['note'] ?? ''),
        ];
    }

    private function validDeviceInput($data)
    {
        return $data['category_id'] > 0
            && $data['name'] !== ''
            && mb_strlen($data['name']) <= 150
            && mb_strlen($data['brand']) <= 100
            && mb_strlen($data['model']) <= 100
            && strlen($data['note']) <= 65535;
    }
}

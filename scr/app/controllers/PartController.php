<?php
class PartController extends Controller
{
    private function authorize()
    {
        $this->requireRole('admin');
    }

    private function verifyPost()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf()) {
            $this->setFlash('error', 'Yêu cầu không hợp lệ hoặc phiên làm việc đã hết hạn.');
            $this->redirect(url('/part/index'));
        }
    }

    public function index()
    {
        $this->authorize();
        $search = $this->queryString('search', 100);
        $lowStock = ($_GET['low_stock'] ?? '') === '1';
        $model = $this->model('PartModel');
        $pagination = $this->paginationData($model->countAll($search, $lowStock));
        $this->view('admin/parts/index', [
            'title' => 'Quản lý linh kiện',
            'parts' => $model->getAll($search, $lowStock, $pagination['perPage'], $pagination['offset']),
            'search' => $search,
            'lowStock' => $lowStock,
        ] + $pagination);
    }

    public function create()
    {
        $this->authorize();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyPost();
            $data = $this->partInput();
            if ($data === false) {
                $this->setFlash('error', 'Vui lòng kiểm tra tên, SKU, đơn vị, giá và tồn kho.');
            } else {
                $result = $this->model('PartModel')->create($data);
                $this->setFlash($result === 'created' ? 'success' : 'error',
                    $result === 'created' ? 'Đã thêm linh kiện.' : 'Mã SKU đã được sử dụng.');
            }
            $this->redirect(url('/part/index'));
        }
        $this->view('admin/parts/form', ['title' => 'Thêm linh kiện', 'part' => null]);
    }

    public function edit($id = 0)
    {
        $this->authorize();
        $model = $this->model('PartModel');
        $part = $model->find((int) $id);
        if (!$part) {
            $this->setFlash('error', 'Không tìm thấy linh kiện.');
            $this->redirect(url('/part/index'));
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyPost();
            $data = $this->partInput();
            if ($data === false) {
                $this->setFlash('error', 'Vui lòng kiểm tra tên, SKU, đơn vị, giá và tồn kho.');
            } else {
                $result = $model->update((int) $id, $data);
                $this->setFlash($result === 'updated' ? 'success' : 'error',
                    $result === 'updated' ? 'Đã cập nhật linh kiện.' : 'SKU đã được dùng hoặc không tìm thấy linh kiện.');
            }
            $this->redirect(url('/part/index'));
        }
        $this->view('admin/parts/form', ['title' => 'Sửa linh kiện', 'part' => $part]);
    }

    public function status($id = 0)
    {
        $this->authorize();
        $this->verifyPost();
        $statusInput = $_POST['status'] ?? '';
        if (!is_string($statusInput) || !in_array($statusInput, ['0', '1'], true)) {
            $this->setFlash('error', 'Trạng thái không hợp lệ.');
        } elseif ($this->model('PartModel')->setStatus((int) $id, (int) $statusInput)) {
            $this->setFlash('success', 'Đã cập nhật trạng thái linh kiện.');
        } else {
            $this->setFlash('error', 'Không tìm thấy linh kiện.');
        }
        $this->redirect(url('/part/index'));
    }

    public function stock($id = 0)
    {
        $this->authorize();
        $this->verifyPost();
        $input = $_POST['quantity'] ?? '';
        if (!is_string($input) || !ctype_digit($input) || (int) $input < 1) {
            $this->setFlash('error', 'Số lượng nhập kho phải là số nguyên dương.');
        } else {
            $result = $this->model('PartModel')->addStock((int) $id, (int) $input);
            $messages = [
                'updated' => ['success', 'Đã nhập thêm kho.'],
                'overflow' => ['error', 'Số lượng vượt quá giới hạn tồn kho.'],
                'missing' => ['error', 'Không tìm thấy linh kiện.'],
            ];
            $message = $messages[$result];
            $this->setFlash($message[0], $message[1]);
        }
        $this->redirect(url('/part/index'));
    }

    public function delete($id = 0)
    {
        $this->authorize();
        $this->verifyPost();
        $result = $this->model('PartModel')->delete((int) $id);
        if ($result === 'deleted') {
            $this->setFlash('success', 'Đã xóa linh kiện.');
        } elseif ($result === 'used') {
            $this->setFlash('error', 'Không thể xóa linh kiện đã được sử dụng trong biên bản sửa chữa.');
        } else {
            $this->setFlash('error', 'Không tìm thấy linh kiện.');
        }
        $this->redirect(url($this->paginationReturnUrl('/part/index')));
    }

    private function partInput()
    {
        $nameInput = $_POST['name'] ?? '';
        $skuInput = $_POST['sku'] ?? '';
        $unitInput = $_POST['unit'] ?? '';
        if (!is_string($nameInput) || !is_string($skuInput) || !is_string($unitInput)) {
            return false;
        }
        $name = trim($nameInput);
        $sku = trim($skuInput);
        $unit = trim($unitInput);
        $price = $_POST['price'] ?? '';
        $stock = $_POST['stock_qty'] ?? '';
        $status = $_POST['status'] ?? '1';
        if (!is_string($price) || !is_numeric($price) || (float) $price < 0
            || (float) $price > 999999999999
            || !is_string($stock) || !ctype_digit($stock) || (float) $stock > 4294967295
            || !is_string($status) || !in_array($status, ['0', '1'], true)
            || $name === '' || mb_strlen($name) > 180
            || $sku === '' || mb_strlen($sku) > 80
            || $unit === '' || mb_strlen($unit) > 40) {
            return false;
        }
        return [
            'name' => $name,
            'sku' => $sku,
            'unit' => $unit,
            'price' => (float) $price,
            'stock_qty' => (int) $stock,
            'status' => (int) $status,
        ];
    }
}

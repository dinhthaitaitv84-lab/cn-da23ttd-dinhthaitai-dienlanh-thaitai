<?php
class AdminController extends Controller
{
    private function authorize()
    {
        $this->requireRole('admin');
    }

    private function verifyPost()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf()) {
            $this->setFlash('error', 'Yêu cầu không hợp lệ hoặc phiên làm việc đã hết hạn.');
            $this->redirect('/DoAn_DienLanh/scr/admin/dashboard');
        }
    }

    public function dashboard()
    {
        $this->authorize();
        $database = new Database();
        $connection = $database->getConnection();
        if (!($connection instanceof PDO)) {
            throw new RuntimeException('Không thể kết nối cơ sở dữ liệu để tải dashboard.');
        }

        $statement = $connection->prepare(
            "SELECT COUNT(*) AS total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed
             FROM requests"
        );
        $statement->execute();
        $requests = $statement->fetch(PDO::FETCH_ASSOC);
        $stats = [
            'requests' => (int) ($requests['total'] ?? 0),
            'pending' => (int) ($requests['pending'] ?? 0),
            'completed' => (int) ($requests['completed'] ?? 0),
        ];

        $statement = $connection->prepare("SELECT COUNT(*) FROM users WHERE role = 'customer'");
        $statement->execute();
        $stats['customers'] = (int) $statement->fetchColumn();

        $today = new DateTimeImmutable('today');
        $start = $today->modify('-6 days')->format('Y-m-d');
        $end = $today->format('Y-m-d');
        $reportModel = $this->model('ReportModel');
        $this->view('admin/dashboard', [
            'title' => 'Dashboard',
            'stats' => $stats,
            'dashboardRevenue' => $reportModel->dashboardRevenue($start, $end),
            'topTechnicians' => $reportModel->topTechnicians($start, $end),
        ]);
    }

    public function requests()
    {
        $this->authorize();
        $statuses = [
            'pending' => 'Chờ tiếp nhận',
            'received' => 'Đã tiếp nhận',
            'checking' => 'Đang kiểm tra',
            'waiting_parts' => 'Chờ linh kiện',
            'repairing' => 'Đang sửa chữa',
            'quoted' => 'Đã báo giá',
            'completed' => 'Hoàn thành',
            'cancelled' => 'Đã hủy',
        ];
        $priorities = ['low' => 'Thấp', 'normal' => 'Bình thường', 'high' => 'Cao', 'urgent' => 'Khẩn cấp'];
        $filters = [
            'status' => $this->queryString('status', 30),
            'priority' => $this->queryString('priority', 30),
            'category_id' => $this->queryInt('category_id'),
            'technician_id' => $this->queryInt('technician_id'),
            'search' => $this->queryString('search', 100),
        ];
        if (!array_key_exists($filters['status'], $statuses)) {
            $filters['status'] = '';
        }
        if (!array_key_exists($filters['priority'], $priorities)) {
            $filters['priority'] = '';
        }
        $model = $this->model('AdminRequestModel');
        $pagination = $this->paginationData($model->countAll($filters));
        $this->view('admin/requests/index', [
            'title' => 'Quản lý yêu cầu',
            'requests' => $model->getAll($filters, $pagination['perPage'], $pagination['offset']),
            'filters' => $filters,
            'statuses' => $statuses,
            'priorities' => $priorities,
            'categories' => $this->model('CategoryModel')->getAll(),
            'technicians' => $this->model('TechnicianModel')->getForFilter(),
        ] + $pagination);
    }

    public function requestDetail($id = 0)
    {
        $this->authorize();
        $request = $this->model('AdminRequestModel')->find((int) $id);
        if (!$request) {
            $this->setFlash('error', 'Không tìm thấy yêu cầu.');
            $this->redirect('/DoAn_DienLanh/scr/admin/requests');
        }
        $preferred = strtotime($request['preferred_time']);
        $this->view('admin/requests/detail', [
            'title' => 'Yêu cầu ' . $request['code'],
            'request' => $request,
            'technicians' => $this->model('TechnicianModel')->getAssignable(),
            'defaultStart' => date('Y-m-d\TH:i', $preferred),
            'defaultEnd' => date('Y-m-d\TH:i', $preferred + ((int) $request['est_minutes'] * 60)),
            'statusLabels' => [
                'pending' => 'Chờ tiếp nhận', 'received' => 'Đã tiếp nhận',
                'checking' => 'Đang kiểm tra', 'waiting_parts' => 'Chờ linh kiện',
                'repairing' => 'Đang sửa chữa', 'quoted' => 'Đã báo giá',
                'completed' => 'Hoàn thành', 'cancelled' => 'Đã hủy',
            ],
            'priorityLabels' => ['low' => 'Thấp', 'normal' => 'Bình thường', 'high' => 'Cao', 'urgent' => 'Khẩn cấp'],
        ]);
    }

    public function reviews()
    {
        $this->authorize();
        $rating = min(5, $this->queryInt('rating'));
        $technicianId = $this->queryInt('technician_id');
        $model = $this->model('AdminRequestModel');
        $pagination = $this->paginationData($model->countReviews($rating, $technicianId));
        $this->view('admin/reviews', [
            'title' => 'Đánh giá khách hàng',
            'reviews' => $model->getReviews($rating, $technicianId, $pagination['perPage'], $pagination['offset']),
            'rating' => $rating,
            'technicianId' => $technicianId,
            'technicians' => $this->model('TechnicianModel')->getForFilter(),
        ] + $pagination);
    }

    public function schedule()
    {
        $this->authorize();
        $weekInput = $_GET['week'] ?? '';
        $date = is_string($weekInput) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $weekInput)
            ? DateTimeImmutable::createFromFormat('!Y-m-d', $weekInput)
            : false;
        if ($date && $date->format('Y-m-d') !== $weekInput) {
            $date = false;
        }
        $monday = ($date ?: new DateTimeImmutable('today'))->modify('monday this week');
        $weekStart = $monday->format('Y-m-d 00:00:00');
        $weekEnd = $monday->modify('+1 week')->format('Y-m-d 00:00:00');
        $technicianId = $this->queryInt('technician_id');
        $model = $this->model('AdminScheduleModel');
        $pagination = $this->paginationData($model->countWeek($weekStart, $weekEnd, $technicianId));
        $schedules = $model->getWeek($weekStart, $weekEnd, $technicianId, $pagination['perPage'], $pagination['offset']);
        $this->view('admin/schedule', [
            'title' => 'Lịch kỹ thuật viên',
            'schedules' => $schedules,
            'technicians' => $this->model('TechnicianModel')->getForFilter(),
            'technicianId' => $technicianId,
            'weekStart' => $monday,
            'weekEnd' => $monday->modify('+6 days'),
            'previousWeek' => $monday->modify('-1 week')->format('Y-m-d'),
            'nextWeek' => $monday->modify('+1 week')->format('Y-m-d'),
        ] + $pagination);
    }

    public function requestPriority($id = 0)
    {
        $this->authorize();
        $this->verifyPost();
        $priority = $_POST['priority'] ?? '';
        if (!in_array($priority, ['low', 'normal', 'high', 'urgent'], true)) {
            $this->setFlash('error', 'Mức ưu tiên không hợp lệ.');
        } elseif (!$this->model('AdminRequestModel')->updatePriority((int) $id, $priority)) {
            $this->setFlash('error', 'Không tìm thấy yêu cầu hoặc mức ưu tiên không thay đổi.');
        } else {
            $this->setFlash('success', 'Đã cập nhật mức ưu tiên.');
        }
        $this->redirect('/DoAn_DienLanh/scr/admin/requestDetail/' . (int) $id);
    }

    public function requestAssign($id = 0)
    {
        $this->authorize();
        $this->verifyPost();
        $technicianId = max(0, (int) ($_POST['technician_id'] ?? 0));
        $startTime = trim($_POST['start_time'] ?? '');
        $endTime = trim($_POST['end_time'] ?? '');
        $result = $this->model('AdminRequestModel')->assign(
            (int) $id,
            $technicianId,
            $startTime,
            $endTime,
            (int) $_SESSION['user']['id']
        );
        $messages = [
            'assigned' => ['success', 'Đã phân công kỹ thuật viên và cập nhật lịch.'],
            'overlap' => ['error', 'Kỹ thuật viên đã có lịch trùng trong khung giờ này.'],
            'technician' => ['error', 'Kỹ thuật viên không khả dụng hoặc tài khoản đã bị khóa.'],
            'time' => ['error', 'Khung giờ phân công không hợp lệ.'],
            'closed' => ['error', 'Không thể phân công yêu cầu đã hoàn thành hoặc đã hủy.'],
            'missing' => ['error', 'Không tìm thấy yêu cầu.'],
        ];
        $message = $messages[$result] ?? ['error', 'Không thể phân công yêu cầu.'];
        $this->setFlash($message[0], $message[1]);
        $this->redirect('/DoAn_DienLanh/scr/admin/requestDetail/' . (int) $id);
    }

    public function requestCancel($id = 0)
    {
        $this->authorize();
        $this->verifyPost();
        $reason = trim($_POST['reason'] ?? '');
        if ($reason === '' || mb_strlen($reason) > 1000) {
            $this->setFlash('error', 'Vui lòng nhập lý do hủy (tối đa 1000 ký tự).');
            $this->redirect('/DoAn_DienLanh/scr/admin/requestDetail/' . (int) $id);
        }
        $result = $this->model('AdminRequestModel')->cancel(
            (int) $id,
            $reason,
            (int) $_SESSION['user']['id']
        );
        if ($result === 'cancelled') {
            $this->setFlash('success', 'Đã hủy yêu cầu.');
        } else {
            $this->setFlash('error', $result === 'closed'
                ? 'Yêu cầu đã hoàn thành hoặc đã hủy.'
                : 'Không tìm thấy yêu cầu.');
        }
        $this->redirect('/DoAn_DienLanh/scr/admin/requestDetail/' . (int) $id);
    }

    public function categories()
    {
        $this->authorize();
        $model = $this->model('CategoryModel');
        $pagination = $this->paginationData($model->countAll());
        $this->view('admin/categories', [
            'title' => 'Nhóm dịch vụ',
            'categories' => $model->getAll($pagination['perPage'], $pagination['offset']),
        ] + $pagination);
    }

    public function categoryCreate()
    {
        $this->authorize();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyPost();
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            if ($name === '') {
                $this->setFlash('error', 'Vui lòng nhập tên nhóm dịch vụ.');
            } else {
                $this->model('CategoryModel')->create($name, $description);
                $this->setFlash('success', 'Đã thêm nhóm dịch vụ.');
            }
            $this->redirect('/DoAn_DienLanh/scr/admin/categories');
        }
        $this->view('admin/category-form', ['title' => 'Thêm nhóm dịch vụ', 'category' => null]);
    }

    public function categoryEdit($id = 0)
    {
        $this->authorize();
        $model = $this->model('CategoryModel');
        $category = $model->find((int) $id);
        if (!$category) {
            $this->setFlash('error', 'Không tìm thấy nhóm dịch vụ.');
            $this->redirect('/DoAn_DienLanh/scr/admin/categories');
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyPost();
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            if ($name === '') {
                $this->setFlash('error', 'Vui lòng nhập tên nhóm dịch vụ.');
            } else {
                $model->update((int) $id, $name, $description);
                $this->setFlash('success', 'Đã cập nhật nhóm dịch vụ.');
            }
            $this->redirect('/DoAn_DienLanh/scr/admin/categories');
        }
        $this->view('admin/category-form', ['title' => 'Sửa nhóm dịch vụ', 'category' => $category]);
    }

    public function categoryToggle($id = 0)
    {
        $this->authorize();
        $this->verifyPost();
        $this->model('CategoryModel')->toggle((int) $id);
        $this->setFlash('success', 'Đã cập nhật trạng thái nhóm dịch vụ.');
        $this->redirect('/DoAn_DienLanh/scr/admin/categories');
    }

    public function categoryDelete($id = 0)
    {
        $this->authorize();
        $this->verifyPost();
        $deleted = $this->model('CategoryModel')->delete((int) $id);
        $this->setFlash($deleted ? 'success' : 'error', $deleted
            ? 'Đã xóa nhóm dịch vụ.'
            : 'Không thể xóa nhóm đang có dịch vụ.');
        $this->redirect($this->paginationReturnUrl('/DoAn_DienLanh/scr/admin/categories'));
    }

    public function services()
    {
        $this->authorize();
        $categoryId = $this->queryInt('category_id');
        $search = $this->queryString('search', 100);
        $model = $this->model('ServiceModel');
        $total = $model->countAll($categoryId, $search);
        $pagination = $this->paginationData($total);

        $this->view('admin/services', [
            'title' => 'Dịch vụ',
            'services' => $model->getAll($categoryId, $search, $pagination['perPage'], $pagination['offset']),
            'categories' => $this->model('CategoryModel')->getAll(),
            'categoryId' => $categoryId,
            'search' => $search,
        ] + $pagination);
    }

    public function serviceCreate()
    {
        $this->authorize();
        $model = $this->model('ServiceModel');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyPost();
            $data = $this->serviceData();
            if ($data === false) {
                $this->setFlash('error', 'Vui lòng kiểm tra các thông tin dịch vụ.');
                $this->redirect('/DoAn_DienLanh/scr/admin/serviceCreate');
            }
            $model->create($data);
            $this->setFlash('success', 'Đã thêm dịch vụ.');
            $this->redirect('/DoAn_DienLanh/scr/admin/services');
        }
        $this->view('admin/service-form', [
            'title' => 'Thêm dịch vụ',
            'service' => null,
            'categories' => $this->model('CategoryModel')->getAll(),
        ]);
    }

    public function serviceEdit($id = 0)
    {
        $this->authorize();
        $model = $this->model('ServiceModel');
        $service = $model->find((int) $id);
        if (!$service) {
            $this->setFlash('error', 'Không tìm thấy dịch vụ.');
            $this->redirect('/DoAn_DienLanh/scr/admin/services');
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyPost();
            $data = $this->serviceData();
            if ($data === false) {
                $this->setFlash('error', 'Vui lòng kiểm tra các thông tin dịch vụ.');
                $this->redirect('/DoAn_DienLanh/scr/admin/serviceEdit/' . (int) $id);
            }
            $model->update((int) $id, $data);
            $this->setFlash('success', 'Đã cập nhật dịch vụ.');
            $this->redirect('/DoAn_DienLanh/scr/admin/services');
        }
        $this->view('admin/service-form', [
            'title' => 'Sửa dịch vụ',
            'service' => $service,
            'categories' => $this->model('CategoryModel')->getAll(),
        ]);
    }

    public function serviceToggle($id = 0)
    {
        $this->authorize();
        $this->verifyPost();
        $this->model('ServiceModel')->toggle((int) $id);
        $this->setFlash('success', 'Đã cập nhật trạng thái dịch vụ.');
        $this->redirect('/DoAn_DienLanh/scr/admin/services');
    }

    public function serviceDelete($id = 0)
    {
        $this->authorize();
        $this->verifyPost();
        $deleted = $this->model('ServiceModel')->delete((int) $id);
        $this->setFlash($deleted ? 'success' : 'error', $deleted
            ? 'Đã xóa dịch vụ.'
            : 'Không thể xóa dịch vụ đã có yêu cầu.');
        $this->redirect($this->paginationReturnUrl('/DoAn_DienLanh/scr/admin/services'));
    }

    private function serviceData()
    {
        $name = trim($_POST['name'] ?? '');
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $price = $_POST['base_price'] ?? '';
        $minutes = $_POST['est_minutes'] ?? '';
        if ($name === '' || $categoryId < 1 || !is_numeric($price) || $price < 0 || !ctype_digit((string) $minutes) || (int) $minutes < 1) {
            return false;
        }

        return [
            'name' => $name,
            'category_id' => $categoryId,
            'description' => $description,
            'base_price' => (float) $price,
            'est_minutes' => (int) $minutes,
        ];
    }
}

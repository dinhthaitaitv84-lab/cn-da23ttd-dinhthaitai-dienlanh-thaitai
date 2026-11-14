<?php
class RequestController extends Controller
{
    public function create()
    {
        $this->requireRole('customer');
        $customerId = (int) $_SESSION['user']['id'];
        $oldInput = $_SESSION['request_form_data'] ?? [];
        unset($_SESSION['request_form_data']);
        if (!is_array($oldInput)) {
            $oldInput = [];
        }
        $categoryModel = $this->model('CategoryModel');
        $serviceModel = $this->model('ServiceModel');
        $categories = $categoryModel->getActive();
        $services = $serviceModel->getPublicList();
        $selectedServiceId = isset($oldInput['service_id'])
            ? max(0, (int) $oldInput['service_id'])
            : $this->queryInt('service_id');
        $selectedCategoryId = isset($oldInput['category_id'])
            ? max(0, (int) $oldInput['category_id'])
            : 0;
        if ($selectedServiceId > 0) {
            $selectedService = $serviceModel->findActive($selectedServiceId);
            if ($selectedService) {
                if ($selectedCategoryId === 0) {
                    $selectedCategoryId = (int) $selectedService['category_id'];
                }
            } else {
                $selectedServiceId = 0;
                $this->setFlash('error', 'Dịch vụ đã chọn hiện không khả dụng.');
            }
        }

        $requestModel = $this->model('RequestModel');
        $this->view('request/create', [
            'title' => 'Gửi yêu cầu dịch vụ',
            'categories' => $categories,
            'services' => $services,
            'devices' => $this->model('DeviceModel')->getByCustomer($customerId),
            'selectedCategoryId' => $selectedCategoryId,
            'selectedServiceId' => $selectedServiceId,
            'deviceId' => $oldInput['device_id'] ?? '',
            'deviceName' => $oldInput['device_name'] ?? '',
            'deviceBrand' => $oldInput['device_brand'] ?? '',
            'deviceModel' => $oldInput['device_model'] ?? '',
            'title' => $oldInput['title'] ?? '',
            'description' => $oldInput['description'] ?? '',
            'preferredTime' => $oldInput['preferred_time'] ?? '',
            'address' => $oldInput['address'] ?? ($requestModel->getCustomerAddress($customerId) ?: ''),
        ]);
    }

    public function store()
    {
        $this->requireRole('customer');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf()) {
            $this->setFlash('error', 'Yêu cầu không hợp lệ hoặc phiên làm việc đã hết hạn.');
            $this->redirect('/DoAn_DienLanh/scr/request/create');
        }

        $categoryId = max(0, (int) ($_POST['category_id'] ?? 0));
        $serviceId = max(0, (int) ($_POST['service_id'] ?? 0));
        $service = $this->model('ServiceModel')->findActive($serviceId);
        $title = trim($_POST['title'] ?? '');
        $address = trim($_POST['address'] ?? '');
        if ($address !== '' && !$this->isAddressInServiceArea($address)) {
            $_SESSION['request_form_data'] = [
                'category_id' => $_POST['category_id'] ?? '',
                'service_id' => $_POST['service_id'] ?? '',
                'device_id' => $_POST['device_id'] ?? '',
                'device_name' => $_POST['device_name'] ?? '',
                'device_brand' => $_POST['device_brand'] ?? '',
                'device_model' => $_POST['device_model'] ?? '',
                'title' => $_POST['title'] ?? '',
                'description' => $_POST['description'] ?? '',
                'address' => $_POST['address'],
                'preferred_time' => $_POST['preferred_time'] ?? '',
            ];
            $this->setFlash('error', 'Địa chỉ nằm ngoài khu vực phục vụ. Hiện chỉ nhận yêu cầu tại ' . SERVICE_AREA_TEXT . '.');
            $this->redirect('/DoAn_DienLanh/scr/request/create');
        }
        $preferredInput = trim($_POST['preferred_time'] ?? '');
        $preferredTime = DateTime::createFromFormat('Y-m-d\TH:i', $preferredInput);
        $dateErrors = DateTime::getLastErrors();
        $dateInvalid = !$preferredTime
            || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
            || $preferredTime->format('Y-m-d\TH:i') !== $preferredInput
            || $preferredTime->format('Y-m-d\TH:i') < date('Y-m-d\TH:i');

        $deviceId = trim($_POST['device_id'] ?? '');
        $deviceName = trim($_POST['device_name'] ?? '');
        $deviceBrand = trim($_POST['device_brand'] ?? '');
        $deviceModel = trim($_POST['device_model'] ?? '');
        if ($deviceId === '' && $deviceName === '') {
            $this->setFlash('error', 'Vui lòng chọn thiết bị hoặc nhập tên thiết bị mới.');
            $this->redirect('/DoAn_DienLanh/scr/request/create');
        }
        if ($deviceId !== '' && !ctype_digit($deviceId)) {
            $this->setFlash('error', 'Thiết bị đã chọn không hợp lệ.');
            $this->redirect('/DoAn_DienLanh/scr/request/create');
        }
        if (!$service || (int) $service['category_id'] !== $categoryId
            || $title === '' || mb_strlen($title) > 200 || $address === '' || mb_strlen($address) > 500 || $dateInvalid
            || mb_strlen($deviceName) > 150 || mb_strlen($deviceBrand) > 100 || mb_strlen($deviceModel) > 100) {
            $this->setFlash('error', 'Vui lòng kiểm tra dịch vụ, tiêu đề, địa chỉ và thời gian mong muốn.');
            $this->redirect('/DoAn_DienLanh/scr/request/create');
        }

        $uploadedPaths = [];
        try {
            $files = $this->uploadedImages($_FILES['images'] ?? null);
            if (count($files) > 5) {
                throw new InvalidArgumentException('Có thể tải lên tối đa 5 ảnh.');
            }
            foreach ($files as $file) {
                $uploadedPaths[] = $this->uploadImage($file);
            }

            $data = [
                'customer_id' => (int) $_SESSION['user']['id'],
                'service_id' => (int) $service['id'],
                'device_id' => $deviceId,
                'device_name' => $deviceName,
                'device_brand' => $deviceBrand,
                'device_model' => $deviceModel,
                'title' => $title,
                'description' => trim($_POST['description'] ?? ''),
                'address' => $address,
                'preferred_time' => $preferredTime->format('Y-m-d H:i:s'),
            ];
            $code = $this->model('RequestModel')->store($data, $uploadedPaths);
        } catch (Throwable $exception) {
            foreach ($uploadedPaths as $path) {
                $absolutePath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR
                    . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
                if (is_file($absolutePath) && !unlink($absolutePath)) {
                    error_log('Unable to remove uploaded request image: ' . $path);
                }
            }
            if (!($exception instanceof InvalidArgumentException)) {
                error_log('Unable to store customer request: ' . $exception->getMessage());
            }
            $this->setFlash('error', $exception instanceof InvalidArgumentException
                ? $exception->getMessage()
                : 'Không thể gửi yêu cầu. Vui lòng thử lại.');
            $this->redirect('/DoAn_DienLanh/scr/request/create');
        }

        $this->setFlash('success', 'Đã gửi yêu cầu.');
        $this->redirect('/DoAn_DienLanh/scr/request/mine');
    }

    public function mine()
    {
        $this->requireRole('customer');
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
        $status = $this->queryString('status', 30);
        if (!array_key_exists($status, $statuses)) {
            $status = '';
        }
        $search = $this->queryString('search', 40);
        $customerId = (int) $_SESSION['user']['id'];
        $requestModel = $this->model('RequestModel');
        $total = $requestModel->countMine($customerId, $status, $search);
        $pagination = $this->paginationData($total);

        $this->view('request/mine', [
            'title' => 'Yêu cầu',
            'requests' => $requestModel->getMine($customerId, $status, $search, $pagination['perPage'], $pagination['offset']),
            'statuses' => $statuses,
            'selectedStatus' => $status,
            'search' => $search,
        ] + $pagination);
    }

    public function detail($id = 0)
    {
        $this->requireRole('customer');
        $requestModel = $this->model('RequestModel');
        $request = $requestModel->findMine((int) $id, (int) $_SESSION['user']['id']);
        if (!$request) {
            $this->setFlash('error', 'Không tìm thấy yêu cầu hoặc Quý khách không có quyền xem yêu cầu này.');
            $this->redirect('/DoAn_DienLanh/scr/request/mine');
        }

        $completedAt = null;
        foreach ($request['logs'] as $log) {
            if ($log['status'] === 'completed'
                && (int) $log['changed_by'] !== (int) $_SESSION['user']['id']) {
                $completedAt = $log['created_at'];
                break;
            }
        }
        $warrantyExpiresAt = null;
        if ($completedAt !== null && $request['report'] && (int) $request['report']['warranty_months'] > 0) {
            $expires = new DateTime($completedAt);
            $expires->modify('+' . (int) $request['report']['warranty_months'] . ' months');
            $warrantyExpiresAt = $expires->format('Y-m-d H:i:s');
        }
        $this->view('request/detail', [
            'title' => 'Chi tiết yêu cầu ' . $request['code'],
            'request' => $request,
            'completionConfirmedAt' => $request['completion_confirmed_at'],
            'warrantyCode' => $warrantyExpiresAt !== null ? $request['code'] . '-BH' : null,
            'warrantyExpiresAt' => $warrantyExpiresAt,
            'statusLabels' => [
                'pending' => 'Chờ tiếp nhận',
                'received' => 'Đã tiếp nhận',
                'checking' => 'Đang kiểm tra',
                'waiting_parts' => 'Chờ linh kiện',
                'repairing' => 'Đang sửa chữa',
                'quoted' => 'Đã báo giá',
                'completed' => 'Hoàn thành',
                'cancelled' => 'Đã hủy',
            ],
            'priorityLabels' => [
                'low' => 'Thấp',
                'normal' => 'Bình thường',
                'high' => 'Cao',
                'urgent' => 'Khẩn cấp',
            ],
        ]);
    }

    public function acceptQuote($id = 0)
    {
        $this->requireRole('customer');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf()) {
            $this->setFlash('error', 'Yêu cầu không hợp lệ hoặc phiên làm việc đã hết hạn.');
            $this->redirect('/DoAn_DienLanh/scr/request/mine');
        }
        $result = $this->model('RequestModel')->quoteDecision(
            (int) $id,
            (int) $_SESSION['user']['id'],
            'accepted',
            ''
        );
        if ($result === 'accepted') {
            $this->setFlash('success', 'Đã chấp nhận báo giá.');
        } else {
            $this->setFlash('error', $result === 'missing'
                ? 'Không tìm thấy yêu cầu hoặc Quý khách không có quyền thao tác.'
                : 'Báo giá không còn hiệu lực để chấp nhận.');
        }
        $this->redirect('/DoAn_DienLanh/scr/request/detail/' . (int) $id);
    }

    public function rejectQuote($id = 0)
    {
        $this->requireRole('customer');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf()) {
            $this->setFlash('error', 'Yêu cầu không hợp lệ hoặc phiên làm việc đã hết hạn.');
            $this->redirect('/DoAn_DienLanh/scr/request/mine');
        }
        $reasonInput = $_POST['reason'] ?? '';
        $reason = is_string($reasonInput) ? trim($reasonInput) : '';
        if ($reason === '' || mb_strlen($reason) > 1000) {
            $this->setFlash('error', 'Vui lòng nhập lý do từ chối báo giá (tối đa 1000 ký tự).');
            $this->redirect('/DoAn_DienLanh/scr/request/detail/' . (int) $id);
        }
        $result = $this->model('RequestModel')->quoteDecision(
            (int) $id,
            (int) $_SESSION['user']['id'],
            'rejected',
            $reason
        );
        if ($result === 'rejected') {
            $this->setFlash('success', 'Đã từ chối báo giá.');
        } else {
            $this->setFlash('error', $result === 'missing'
                ? 'Không tìm thấy yêu cầu hoặc Quý khách không có quyền thao tác.'
                : 'Báo giá không còn hiệu lực để từ chối.');
        }
        $this->redirect('/DoAn_DienLanh/scr/request/detail/' . (int) $id);
    }

    public function review($id = 0)
    {
        $this->requireRole('customer');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf()) {
            $this->setFlash('error', 'Yêu cầu không hợp lệ hoặc phiên làm việc đã hết hạn.');
            $this->redirect('/DoAn_DienLanh/scr/request/mine');
        }
        $ratingInput = $_POST['rating'] ?? '';
        $commentInput = $_POST['comment'] ?? '';
        $rating = is_string($ratingInput) && ctype_digit($ratingInput) ? (int) $ratingInput : 0;
        $comment = is_string($commentInput) ? trim($commentInput) : '';
        if ($rating < 1 || $rating > 5 || mb_strlen($comment) > 5000) {
            $this->setFlash('error', 'Vui lòng chọn số sao từ 1 đến 5 và kiểm tra nội dung đánh giá.');
            $this->redirect('/DoAn_DienLanh/scr/request/detail/' . (int) $id);
        }
        $result = $this->model('RequestModel')->createReview(
            (int) $id,
            (int) $_SESSION['user']['id'],
            $rating,
            $comment
        );
        if ($result === 'created') {
            $this->setFlash('success', 'Đã gửi đánh giá.');
        } elseif ($result === 'exists') {
            $this->setFlash('error', 'Yêu cầu này đã được đánh giá.');
        } elseif ($result === 'not_completed') {
            $this->setFlash('error', 'Chỉ có thể đánh giá yêu cầu đã hoàn thành.');
        } elseif ($result === 'not_confirmed') {
            $this->setFlash('error', 'Vui lòng xác nhận hoàn thành trước khi đánh giá dịch vụ.');
        } else {
            $this->setFlash('error', 'Không tìm thấy yêu cầu hoặc Quý khách không có quyền đánh giá.');
        }
        $this->redirect('/DoAn_DienLanh/scr/request/detail/' . (int) $id);
    }

    public function confirmCompletion($id = 0)
    {
        $this->requireRole('customer');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf()) {
            $this->setFlash('error', 'Yêu cầu không hợp lệ hoặc phiên làm việc đã hết hạn.');
            $this->redirect('/DoAn_DienLanh/scr/request/mine');
        }

        $result = $this->model('RequestModel')->confirmCompletion(
            (int) $id,
            (int) $_SESSION['user']['id']
        );
        if ($result === 'confirmed') {
            $this->setFlash('success', 'Đã xác nhận hoàn thành dịch vụ.');
        } elseif ($result === 'already_confirmed') {
            $this->setFlash('success', 'Yêu cầu này đã được xác nhận hoàn thành.');
        } elseif ($result === 'not_completed') {
            $this->setFlash('error', 'Chỉ có thể xác nhận yêu cầu đã được hoàn thành.');
        } else {
            $this->setFlash('error', 'Không tìm thấy yêu cầu hoặc Quý khách không có quyền xác nhận.');
        }
        $this->redirect('/DoAn_DienLanh/scr/request/detail/' . (int) $id);
    }

    public function cancel($id = 0)
    {
        $this->requireRole('customer');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf()) {
            $this->setFlash('error', 'Yêu cầu không hợp lệ hoặc phiên làm việc đã hết hạn.');
            $this->redirect('/DoAn_DienLanh/scr/request/mine');
        }

        $result = $this->model('RequestModel')->cancelMine((int) $id, (int) $_SESSION['user']['id']);
        if ($result === 'cancelled') {
            $this->setFlash('success', 'Đã hủy yêu cầu.');
        } elseif ($result === 'not_pending') {
            $this->setFlash('error', 'Chỉ có thể hủy yêu cầu đang chờ tiếp nhận.');
        } else {
            $this->setFlash('error', 'Không tìm thấy yêu cầu hoặc Quý khách không có quyền hủy yêu cầu này.');
        }
        $this->redirect('/DoAn_DienLanh/scr/request/detail/' . (int) $id);
    }

    private function uploadedImages($files)
    {
        if ($files === null || !isset($files['name']) || $files['name'] === []) {
            return [];
        }
        if (!is_array($files['name']) || !is_array($files['error']) || !is_array($files['tmp_name']) || !is_array($files['size'])) {
            throw new InvalidArgumentException('Danh sách ảnh không hợp lệ.');
        }
        if (count($files['name']) > 5) {
            throw new InvalidArgumentException('Có thể tải lên tối đa 5 ảnh.');
        }

        $normalized = [];
        foreach ($files['name'] as $index => $name) {
            $error = $files['error'][$index] ?? UPLOAD_ERR_NO_FILE;
            if ($error === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $normalized[] = [
                'name' => $name,
                'type' => $files['type'][$index] ?? '',
                'tmp_name' => $files['tmp_name'][$index] ?? '',
                'error' => $error,
                'size' => $files['size'][$index] ?? 0,
            ];
        }
        return $normalized;
    }
}

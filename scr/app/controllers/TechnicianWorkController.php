<?php
class TechnicianWorkController extends Controller
{
    private function workContext()
    {
        $this->requireRole('technician');
        $model = $this->model('TechnicianWorkModel');
        return [$model, $model->getTechnicianId((int) $_SESSION['user']['id'])];
    }

    private function verifyPost()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf()) {
            $this->setFlash('error', 'Yêu cầu không hợp lệ hoặc phiên làm việc đã hết hạn.');
            $this->redirect(url('/technician/dashboard'));
        }
    }

    private function findOwnedJob($model, $requestId, $technicianId)
    {
        $job = $model->findJob((int) $requestId, (int) $technicianId);
        if (!$job) {
            $this->setFlash('error', 'Không tìm thấy công việc được phân công.');
            $this->redirect(url('/technician/jobs'));
        }
        return $job;
    }

    public function index()
    {
        $this->dashboard();
    }

    public function dashboard()
    {
        list($model, $technicianId) = $this->workContext();
        $this->view('technician/dashboard', [
            'title' => 'Trang chủ kỹ thuật viên',
            'stats' => $model->getDashboardStats($technicianId),
            'jobs' => $model->getRecentJobs($technicianId),
            'statusLabels' => $this->statusLabels(),
        ]);
    }

    public function jobs()
    {
        list($model, $technicianId) = $this->workContext();
        $statusInput = $_GET['status'] ?? '';
        $status = is_string($statusInput) ? trim($statusInput) : '';
        $statuses = $this->statusLabels();
        if (!array_key_exists($status, $statuses)) {
            $status = '';
        }
        $searchInput = $_GET['search'] ?? '';
        $search = mb_substr(is_string($searchInput) ? trim($searchInput) : '', 0, 40);
        $pagination = $this->paginationData($model->countJobs($technicianId, $status, $search));
        $this->view('technician/jobs', [
            'title' => 'Việc của tôi',
            'jobs' => $model->getJobs($technicianId, $status, $search, $pagination['perPage'], $pagination['offset']),
            'statuses' => $statuses,
            'selectedStatus' => $status,
            'search' => $search,
        ] + $pagination);
    }

    public function detail($id = 0)
    {
        list($model, $technicianId) = $this->workContext();
        $job = $this->findOwnedJob($model, $id, $technicianId);
        $this->view('technician/detail', [
            'title' => 'Công việc ' . $job['code'],
            'job' => $job,
            'statusLabels' => $this->statusLabels(),
            'transitions' => [
                'received' => ['checking' => 'Bắt đầu kiểm tra'],
                'checking' => ['waiting_parts' => 'Chờ linh kiện', 'repairing' => 'Bắt đầu sửa chữa'],
                'waiting_parts' => ['repairing' => 'Bắt đầu sửa chữa'],
                'repairing' => ['completed' => 'Hoàn thành sửa chữa'],
                'quoted' => [],
            ],
        ]);
    }

    public function updateStatus($id = 0)
    {
        list($model, $technicianId) = $this->workContext();
        $this->verifyPost();
        $status = $_POST['status'] ?? '';
        $noteInput = $_POST['note'] ?? '';
        $note = is_string($noteInput) ? trim($noteInput) : '';
        $result = $model->updateStatus(
            (int) $id,
            $technicianId,
            is_string($status) ? $status : '',
            is_string($note) ? $note : '',
            (int) $_SESSION['user']['id']
        );
        $messages = [
            'updated' => ['success', 'Đã cập nhật trạng thái công việc.'],
            'transition' => ['error', 'Không thể chuyển trạng thái theo thứ tự này.'],
            'report_required' => ['error', 'Cần lưu biên bản sửa chữa trước khi hoàn thành.'],
            'quote_required' => ['error', 'Cần tạo báo giá trước khi hoàn thành.'],
            'stock' => ['error', 'Tồn kho không đủ để hoàn tất công việc.'],
            'missing' => ['error', 'Không tìm thấy công việc được phân công.'],
            'invalid' => ['error', 'Thông tin trạng thái không hợp lệ.'],
        ];
        $message = $messages[$result] ?? ['error', 'Không thể cập nhật trạng thái.'];
        $this->setFlash($message[0], $message[1]);
        $this->redirect(url('/technician/detail/' . (int) $id));
    }

    public function saveReport($id = 0)
    {
        list($model, $technicianId) = $this->workContext();
        $this->verifyPost();
        $causeInput = $_POST['cause'] ?? '';
        $solutionInput = $_POST['solution'] ?? '';
        $cause = is_string($causeInput) ? trim($causeInput) : '';
        $solution = is_string($solutionInput) ? trim($solutionInput) : '';
        $laborInput = $_POST['labor_cost'] ?? '';
        $warranty = filter_var($_POST['warranty_months'] ?? null, FILTER_VALIDATE_INT);
        $partIds = $_POST['part_ids'] ?? [];
        $quantities = $_POST['quantities'] ?? [];
        $validLabor = is_scalar($laborInput) && is_numeric($laborInput)
            && (float) $laborInput >= 0 && (float) $laborInput <= 999999999999;
        $parts = [];
        if (is_array($partIds) && is_array($quantities) && count($partIds) === count($quantities)) {
            foreach ($partIds as $index => $partId) {
                if (!is_scalar($partId) || !isset($quantities[$index]) || !is_scalar($quantities[$index])) {
                    $parts = false;
                    break;
                }
                $quantity = (string) $quantities[$index];
                if (!ctype_digit((string) $partId) || (int) $partId < 1
                    || !preg_match('/^\d+(?:\.\d{1,2})?$/', $quantity) || (float) $quantity <= 0) {
                    $parts = false;
                    break;
                }
                $parts[(string) $partId] = number_format(
                    (float) ($parts[(string) $partId] ?? 0) + (float) $quantity,
                    2,
                    '.',
                    ''
                );
            }
        } else {
            $parts = false;
        }
        if (!$validLabor || $warranty === false || $warranty < 0 || $warranty > 65535
            || strlen($cause) > 65535 || strlen($solution) > 65535 || $parts === false) {
            $this->setFlash('error', 'Vui lòng kiểm tra nội dung biên bản, tiền công và thời gian bảo hành.');
            $this->redirect(url('/technician/detail/' . (int) $id));
        }
        $result = $model->saveReport((int) $id, $technicianId, (int) $_SESSION['user']['id'], [
            'cause' => $cause,
            'solution' => $solution,
            'labor_cost' => (float) $laborInput,
            'warranty_months' => $warranty,
            'parts' => $parts,
        ]);
        if ($result === 'saved') {
            $this->setFlash('success', 'Đã lưu biên bản sửa chữa và linh kiện.');
        } elseif ($result === 'stock') {
            $this->setFlash('error', 'Tồn kho không đủ hoặc linh kiện đã ngừng hoạt động.');
        } elseif ($result === 'invalid_parts') {
            $this->setFlash('error', 'Số lượng linh kiện không hợp lệ.');
        } elseif ($result === 'closed') {
            $this->setFlash('error', 'Không thể sửa biên bản của công việc đã đóng.');
        } else {
            $this->setFlash('error', 'Không tìm thấy công việc được phân công.');
        }
        $this->redirect(url('/technician/detail/' . (int) $id));
    }

    public function createQuote($id = 0)
    {
        list($model, $technicianId) = $this->workContext();
        $this->verifyPost();
        $result = $model->createQuote(
            (int) $id,
            $technicianId,
            (int) $_SESSION['user']['id']
        );
        if ($result === 'quoted') {
            $this->setFlash('success', 'Đã tạo báo giá.');
        } elseif ($result === 'report') {
            $this->setFlash('error', 'Vui lòng lưu biên bản sửa chữa trước khi tạo báo giá.');
        } elseif ($result === 'status') {
            $this->setFlash('error', 'Chỉ có thể tạo báo giá khi yêu cầu đang kiểm tra, sửa chữa hoặc đã báo giá.');
        } elseif ($result === 'amount') {
            $this->setFlash('error', 'Tổng báo giá vượt quá giới hạn lưu trữ.');
        } else {
            $this->setFlash('error', 'Không tìm thấy công việc được phân công.');
        }
        $this->redirect(url('/technician/detail/' . (int) $id));
    }

    public function schedule()
    {
        list($model, $technicianId) = $this->workContext();
        $periodInput = $_GET['period'] ?? 'week';
        $period = is_string($periodInput) ? $periodInput : 'week';
        if (!in_array($period, ['day', 'week'], true)) {
            $period = 'week';
        }
        $dateValue = $_GET['date'] ?? date('Y-m-d');
        $dateInput = is_string($dateValue) ? trim($dateValue) : date('Y-m-d');
        $date = DateTime::createFromFormat('!Y-m-d', $dateInput);
        $dateErrors = DateTime::getLastErrors();
        if (!$date || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
            || $date->format('Y-m-d') !== $dateInput) {
            $date = new DateTime('today');
            $dateInput = $date->format('Y-m-d');
        }

        if ($period === 'day') {
            $start = clone $date;
            $end = (clone $date)->modify('+1 day');
        } else {
            $start = clone $date;
            $start->modify('monday this week');
            $end = (clone $start)->modify('+7 days');
        }
        $pagination = $this->paginationData($model->countSchedule(
            $technicianId,
            $start->format('Y-m-d H:i:s'),
            $end->format('Y-m-d H:i:s')
        ));
        $this->view('technician/schedule', [
            'title' => 'Lịch làm việc',
            'events' => $model->getSchedule($technicianId, $start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s'), $pagination['perPage'], $pagination['offset']),
            'date' => $dateInput,
            'period' => $period,
            'start' => $start->format('d/m/Y'),
            'end' => (clone $end)->modify('-1 second')->format('d/m/Y'),
            'statusLabels' => $this->statusLabels(),
        ] + $pagination);
    }

    private function statusLabels()
    {
        return [
            'pending' => 'Chờ tiếp nhận',
            'received' => 'Đã tiếp nhận',
            'checking' => 'Đang kiểm tra',
            'waiting_parts' => 'Chờ linh kiện',
            'repairing' => 'Đang sửa chữa',
            'quoted' => 'Đã báo giá',
            'completed' => 'Hoàn thành',
            'cancelled' => 'Đã hủy',
        ];
    }
}

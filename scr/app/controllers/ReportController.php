<?php
class ReportController extends Controller
{
    private function authorize()
    {
        $this->requireRole('admin');
    }

    private function filters()
    {
        $today = new DateTimeImmutable('today');
        $defaultStart = $today->modify('first day of this month')->format('Y-m-d');
        $defaultEnd = $today->format('Y-m-d');
        $start = $this->validDate($_GET['from'] ?? '') ?: $defaultStart;
        $end = $this->validDate($_GET['to'] ?? '') ?: $defaultEnd;
        if ($start > $end) {
            $start = $defaultStart;
            $end = $defaultEnd;
        }
        return ['from' => $start, 'to' => $end];
    }

    private function validDate($value)
    {
        if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value ? $value : null;
    }

    private function pageData($title, $filters, $report, $extra = [])
    {
        return array_merge([
            'title' => $title,
            'filters' => $filters,
            'report' => $report,
        ], $extra);
    }

    public function index()
    {
        $this->authorize();
        $filters = $this->filters();
        $report = $this->model('ReportModel')->overview($filters['from'], $filters['to']);
        $this->view('admin/reports/index', $this->pageData('Báo cáo tổng quan', $filters, $report));
    }

    public function services()
    {
        $this->authorize();
        $filters = $this->filters();
        $model = $this->model('ReportModel');
        $pagination = $this->paginationData($model->countServiceRows());
        $rows = $model->serviceRows($filters['from'], $filters['to'], $pagination['perPage'], $pagination['offset']);
        $allRows = $model->serviceRows($filters['from'], $filters['to']);
        $this->view('admin/reports/services', $this->pageData(
            'Báo cáo theo dịch vụ',
            $filters,
            $rows,
            ['categorySeries' => $model->serviceCategories($allRows), 'printRows' => $allRows]
        ) + $pagination);
    }

    public function technicians()
    {
        $this->authorize();
        $filters = $this->filters();
        $model = $this->model('ReportModel');
        $pagination = $this->paginationData($model->countTechnicianRows());
        $rows = $model->technicianRows($filters['from'], $filters['to'], $pagination['perPage'], $pagination['offset']);
        $chartRows = $model->technicianRows($filters['from'], $filters['to']);
        $this->view('admin/reports/technicians', $this->pageData(
            'Báo cáo theo kỹ thuật viên',
            $filters,
            $rows
        ) + $pagination + ['chartRows' => $chartRows, 'printRows' => $chartRows]);
    }

    public function status()
    {
        $this->authorize();
        $filters = $this->filters();
        $model = $this->model('ReportModel');
        $this->view('admin/reports/status', $this->pageData(
            'Báo cáo trạng thái',
            $filters,
            ['statuses' => $model->statusRows($filters['from'], $filters['to']),
                'priorities' => $model->priorityRows($filters['from'], $filters['to'])]
        ));
    }

    public function parts()
    {
        $this->authorize();
        $filters = $this->filters();
        $model = $this->model('ReportModel');
        $pagination = $this->paginationData($model->countLowStockParts());
        $report = $model->partRows($filters['from'], $filters['to'], $pagination['perPage'], $pagination['offset']);
        $printReport = $model->partRows($filters['from'], $filters['to']);
        $this->view('admin/reports/parts', $this->pageData('Báo cáo linh kiện', $filters, $report) + $pagination + ['printReport' => $printReport]);
    }

    public function export($type = '')
    {
        $this->authorize();
        if (!in_array($type, ['index', 'services', 'technicians', 'status', 'parts'], true)) {
            http_response_code(404);
            return;
        }
        $filters = $this->filters();
        $model = $this->model('ReportModel');
        $filename = 'bao-cao-' . $type . '-' . $filters['from'] . '-' . $filters['to'] . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $output = fopen('php://output', 'w');
        if ($output === false) {
            throw new RuntimeException('Không thể tạo tệp CSV.');
        }
        fwrite($output, "\xEF\xBB\xBF");

        switch ($type) {
            case 'index':
                $report = $model->overview($filters['from'], $filters['to']);
                $rows = [
                    ['Tổng doanh thu', $report['revenue']],
                    ['Số yêu cầu hoàn thành', $report['completed']],
                    ['Giá trị trung bình/đơn', $report['average_order']],
                    ['Điểm đánh giá trung bình', $report['average_rating']],
                ];
                foreach ($report['revenue_series']['items'] as $item) {
                    $rows[] = ['Doanh thu ' . $item['label'], $item['value']];
                }
                $this->writeCsv($output, ['Chỉ số / thời gian', 'Giá trị'], $rows);
                break;
            case 'services':
                $rows = $model->serviceRows($filters['from'], $filters['to']);
                $this->writeCsv($output, ['Nhóm dịch vụ', 'Dịch vụ', 'Số yêu cầu', 'Hoàn thành', 'Doanh thu', 'Tỷ lệ hoàn thành (%)'],
                    array_map(function ($row) {
                        return [$row['category_name'], $row['service_name'], $row['request_count'],
                            $row['completed_count'], $row['revenue'], round($row['completion_rate'], 2)];
                    }, $rows));
                break;
            case 'technicians':
                $rows = $model->technicianRows($filters['from'], $filters['to']);
                $this->writeCsv($output, ['Kỹ thuật viên', 'Được giao', 'Hoàn thành', 'Doanh thu', 'Điểm đánh giá', 'Thời gian xử lý trung bình (giờ)'],
                    array_map(function ($row) {
                        return [$row['technician_name'], $row['assigned_count'], $row['completed_count'],
                            $row['revenue'], $row['average_rating'] ?? 0, $row['average_hours']];
                    }, $rows));
                break;
            case 'status':
                $statuses = $model->statusRows($filters['from'], $filters['to']);
                $priorities = $model->priorityRows($filters['from'], $filters['to']);
                $this->writeCsv($output, ['Loại', 'Trạng thái / Mức ưu tiên', 'Số yêu cầu'], array_merge(
                    array_map(function ($row) {
                        return ['Trạng thái', $row['status'], $row['total']];
                    }, $statuses),
                    array_map(function ($row) {
                        return ['Ưu tiên', $row['priority'], $row['total']];
                    }, $priorities)
                ));
                break;
            case 'parts':
                $report = $model->partRows($filters['from'], $filters['to']);
                $rows = array_map(function ($row) {
                    return ['Top sử dụng', $row['name'], $row['sku'], $row['used_qty'], $row['unit'], $row['request_count']];
                }, $report['top']);
                $rows = array_merge($rows, array_map(function ($row) {
                    return ['Sắp hết hàng', $row['name'], $row['sku'], $row['stock_qty'], $row['unit'], ''];
                }, $report['low_stock']));
                $this->writeCsv($output, ['Nhóm', 'Linh kiện', 'SKU', 'Số lượng', 'Đơn vị', 'Số yêu cầu'], $rows);
                break;
        }
        fclose($output);
        exit;
    }

    private function writeCsv($output, $headers, $rows)
    {
        fputcsv($output, $headers);
        foreach ($rows as $row) {
            fputcsv($output, array_map(function ($value) {
                if (is_string($value) && preg_match('/^[\s]*[=+\-@]/u', $value)) {
                    return "'" . $value;
                }
                return $value;
            }, $row));
        }
    }
}

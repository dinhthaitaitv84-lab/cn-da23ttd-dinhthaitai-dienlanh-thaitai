<?php
require_once __DIR__ . '/../../config/database.php';

class TechnicianWorkModel
{
    private $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
        if (!($this->conn instanceof PDO)) {
            throw new RuntimeException('Không thể kết nối cơ sở dữ liệu.');
        }
    }

    public function getTechnicianId($userId)
    {
        $statement = $this->conn->prepare(
            "SELECT technicians.id FROM technicians
             INNER JOIN users ON users.id = technicians.user_id
             WHERE technicians.user_id = :user_id AND users.role = 'technician' AND users.status = 1"
        );
        $statement->execute([':user_id' => (int) $userId]);
        $id = $statement->fetchColumn();
        if ($id === false) {
            throw new RuntimeException('Không tìm thấy hồ sơ kỹ thuật viên đang hoạt động.');
        }
        return (int) $id;
    }

    public function getDashboardStats($technicianId)
    {
        $statement = $this->conn->prepare(
            "SELECT
                SUM(CASE WHEN requests.status = 'received' THEN 1 ELSE 0 END) AS new_jobs,
                SUM(CASE WHEN requests.status IN ('checking', 'waiting_parts', 'repairing', 'quoted') THEN 1 ELSE 0 END) AS in_progress,
                (SELECT COUNT(DISTINCT completed_requests.id)
                 FROM requests AS completed_requests
                 INNER JOIN request_logs ON request_logs.request_id = completed_requests.id
                 WHERE completed_requests.technician_id = :completed_technician_id
                   AND completed_requests.status = 'completed'
                   AND request_logs.status = 'completed'
                   AND request_logs.created_at >= DATE_FORMAT(CURRENT_DATE, '%Y-%m-01')
                   AND request_logs.created_at < DATE_ADD(DATE_FORMAT(CURRENT_DATE, '%Y-%m-01'), INTERVAL 1 MONTH)
                ) AS completed_month
             FROM requests WHERE requests.technician_id = :technician_id"
        );
        $statement->execute([
            ':completed_technician_id' => (int) $technicianId,
            ':technician_id' => (int) $technicianId,
        ]);
        $stats = $statement->fetch(PDO::FETCH_ASSOC);
        return [
            'new_jobs' => (int) ($stats['new_jobs'] ?? 0),
            'in_progress' => (int) ($stats['in_progress'] ?? 0),
            'completed_month' => (int) ($stats['completed_month'] ?? 0),
        ];
    }

    public function getRecentJobs($technicianId)
    {
        $statement = $this->conn->prepare(
            'SELECT requests.id, requests.code, requests.title, requests.status, requests.priority,
                    requests.preferred_time, requests.created_at, services.name AS service_name
             FROM requests INNER JOIN services ON services.id = requests.service_id
             WHERE requests.technician_id = :technician_id
             ORDER BY requests.updated_at DESC, requests.id DESC LIMIT 5'
        );
        $statement->execute([':technician_id' => (int) $technicianId]);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countJobs($technicianId, $status, $search)
    {
        $sql = 'SELECT COUNT(*) FROM requests
                INNER JOIN services ON services.id = requests.service_id
                WHERE technician_id = :technician_id';
        $parameters = [':technician_id' => (int) $technicianId];
        if ($status !== '') {
            $sql .= ' AND requests.status = :status';
            $parameters[':status'] = $status;
        }
        if ($search !== '') {
            $sql .= ' AND code LIKE :search';
            $parameters[':search'] = '%' . $search . '%';
        }
        $statement = $this->conn->prepare($sql);
        $statement->execute($parameters);
        return (int) $statement->fetchColumn();
    }

    public function getJobs($technicianId, $status, $search, $limit, $offset)
    {
        $sql = 'SELECT requests.id, requests.code, requests.title, requests.status, requests.priority,
                       requests.preferred_time, requests.created_at, services.name AS service_name
                FROM requests INNER JOIN services ON services.id = requests.service_id
                WHERE requests.technician_id = :technician_id';
        $parameters = [':technician_id' => (int) $technicianId];
        if ($status !== '') {
            $sql .= ' AND requests.status = :status';
            $parameters[':status'] = $status;
        }
        if ($search !== '') {
            $sql .= ' AND requests.code LIKE :search';
            $parameters[':search'] = '%' . $search . '%';
        }
        $sql .= ' ORDER BY requests.updated_at DESC, requests.id DESC LIMIT :limit OFFSET :offset';
        $statement = $this->conn->prepare($sql);
        foreach ($parameters as $key => $value) {
            $statement->bindValue($key, $value);
        }
        $statement->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findJob($requestId, $technicianId)
    {
        $statement = $this->conn->prepare(
            'SELECT requests.id, requests.code, requests.title, requests.description, requests.address,
                    requests.preferred_time, requests.priority, requests.status, requests.created_at,
                    requests.customer_id, services.name AS service_name,
                    users.full_name AS customer_name, users.phone AS customer_phone,
                    devices.name AS device_name, devices.brand AS device_brand, devices.model AS device_model
             FROM requests
             INNER JOIN services ON services.id = requests.service_id
             INNER JOIN users ON users.id = requests.customer_id
             LEFT JOIN devices ON devices.id = requests.device_id
             WHERE requests.id = :id AND requests.technician_id = :technician_id LIMIT 1'
        );
        $statement->execute([
            ':id' => (int) $requestId,
            ':technician_id' => (int) $technicianId,
        ]);
        $job = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$job) {
            return null;
        }

        $images = $this->conn->prepare(
            'SELECT file_path FROM request_images WHERE request_id = :request_id ORDER BY id'
        );
        $images->execute([':request_id' => (int) $requestId]);
        $job['images'] = $images->fetchAll(PDO::FETCH_COLUMN);

        $logs = $this->conn->prepare(
            'SELECT request_logs.status, request_logs.note, request_logs.created_at, users.full_name AS changed_by_name
             FROM request_logs INNER JOIN users ON users.id = request_logs.changed_by
             WHERE request_logs.request_id = :request_id
             ORDER BY request_logs.created_at DESC, request_logs.id DESC'
        );
        $logs->execute([':request_id' => (int) $requestId]);
        $job['logs'] = $logs->fetchAll(PDO::FETCH_ASSOC);

        $schedule = $this->conn->prepare(
            'SELECT start_time, end_time, note FROM schedules
             WHERE request_id = :request_id AND technician_id = :technician_id
             ORDER BY start_time DESC LIMIT 1'
        );
        $schedule->execute([
            ':request_id' => (int) $requestId,
            ':technician_id' => (int) $technicianId,
        ]);
        $job['schedule'] = $schedule->fetch(PDO::FETCH_ASSOC);

        $report = $this->conn->prepare(
            'SELECT id, cause, solution, labor_cost, warranty_months
             FROM repair_reports WHERE request_id = :request_id LIMIT 1'
        );
        $report->execute([':request_id' => (int) $requestId]);
        $job['report'] = $report->fetch(PDO::FETCH_ASSOC);
        $job['report_parts'] = [];
        if ($job['report']) {
            $reportParts = $this->conn->prepare(
                'SELECT report_parts.part_id, report_parts.qty, report_parts.unit_price,
                        parts.name, parts.sku, parts.unit
                 FROM report_parts INNER JOIN parts ON parts.id = report_parts.part_id
                 WHERE report_parts.report_id = :report_id ORDER BY parts.name'
            );
            $reportParts->execute([':report_id' => (int) $job['report']['id']]);
            $job['report_parts'] = $reportParts->fetchAll(PDO::FETCH_ASSOC);
        }

        $parts = $this->conn->prepare(
            'SELECT id, name, sku, unit, price, stock_qty
             FROM parts WHERE status = 1 AND (stock_qty > 0 OR id IN (
                 SELECT report_parts.part_id FROM report_parts
                 INNER JOIN repair_reports ON repair_reports.id = report_parts.report_id
                 WHERE repair_reports.request_id = :request_id
             )) ORDER BY name'
        );
        $parts->execute([':request_id' => (int) $requestId]);
        $job['parts'] = $parts->fetchAll(PDO::FETCH_ASSOC);

        $quote = $this->conn->prepare(
            'SELECT total_amount, status, created_at FROM quotes
             WHERE request_id = :request_id ORDER BY id DESC LIMIT 1'
        );
        $quote->execute([':request_id' => (int) $requestId]);
        $job['quote'] = $quote->fetch(PDO::FETCH_ASSOC);

        return $job;
    }

    public function updateStatus($requestId, $technicianId, $status, $note, $userId)
    {
        $allowedTransitions = [
            'received' => ['checking'],
            'checking' => ['waiting_parts', 'repairing'],
            'waiting_parts' => ['repairing'],
            'repairing' => ['completed'],
        ];
        if (!in_array($status, ['checking', 'waiting_parts', 'repairing', 'completed'], true)
            || strlen($note) > 5000) {
            return 'invalid';
        }

        $this->conn->beginTransaction();
        try {
            $lock = $this->conn->prepare(
                'SELECT status FROM requests WHERE id = :id AND technician_id = :technician_id FOR UPDATE'
            );
            $lock->execute([
                ':id' => (int) $requestId,
                ':technician_id' => (int) $technicianId,
            ]);
            $currentStatus = $lock->fetchColumn();
            if ($currentStatus === false) {
                $this->conn->rollBack();
                return 'missing';
            }
            if (!isset($allowedTransitions[$currentStatus])
                || !in_array($status, $allowedTransitions[$currentStatus], true)) {
                $this->conn->rollBack();
                return 'transition';
            }

            if ($status === 'completed') {
                $reportCheck = $this->conn->prepare(
                    'SELECT id FROM repair_reports WHERE request_id = :request_id'
                );
                $reportCheck->execute([':request_id' => (int) $requestId]);
                if ($reportCheck->fetchColumn() === false) {
                    $this->conn->rollBack();
                    return 'report_required';
                }
                $quoteCheck = $this->conn->prepare(
                    "SELECT id FROM quotes WHERE request_id = :request_id AND status = 'accepted'
                     ORDER BY id DESC LIMIT 1"
                );
                $quoteCheck->execute([':request_id' => (int) $requestId]);
                if ($quoteCheck->fetchColumn() === false) {
                    $this->conn->rollBack();
                    return 'quote_required';
                }

                $items = $this->conn->prepare(
                    'SELECT report_parts.part_id, report_parts.qty
                     FROM report_parts INNER JOIN repair_reports
                       ON repair_reports.id = report_parts.report_id
                     WHERE repair_reports.request_id = :request_id'
                );
                $items->execute([':request_id' => (int) $requestId]);
                foreach ($items->fetchAll(PDO::FETCH_ASSOC) as $item) {
                    $stock = $this->conn->prepare(
                        'UPDATE parts SET stock_qty = stock_qty - CEIL(:qty)
                         WHERE id = :id AND status = 1 AND stock_qty >= CEIL(:minimum_qty)'
                    );
                    $stock->execute([
                        ':qty' => (float) $item['qty'],
                        ':id' => (int) $item['part_id'],
                        ':minimum_qty' => (float) $item['qty'],
                    ]);
                    if ($stock->rowCount() !== 1) {
                        $this->conn->rollBack();
                        return 'stock';
                    }
                }
            }

            $update = $this->conn->prepare('UPDATE requests SET status = :status WHERE id = :id');
            $update->execute([':status' => $status, ':id' => (int) $requestId]);
            $log = $this->conn->prepare(
                'INSERT INTO request_logs (request_id, status, note, changed_by)
                 VALUES (:request_id, :status, :note, :changed_by)'
            );
            $log->execute([
                ':request_id' => (int) $requestId,
                ':status' => $status,
                ':note' => $note !== '' ? $note : null,
                ':changed_by' => (int) $userId,
            ]);

            $busyCheck = $this->conn->prepare(
                "SELECT COUNT(*) FROM requests WHERE technician_id = :technician_id
                 AND status IN ('checking', 'repairing')"
            );
            $busyCheck->execute([':technician_id' => (int) $technicianId]);
            $technicianStatus = (int) $busyCheck->fetchColumn() > 0 ? 'busy' : 'available';
            $setTechnicianStatus = $this->conn->prepare(
                'UPDATE technicians SET status = :status WHERE id = :id'
            );
            $setTechnicianStatus->execute([
                ':status' => $technicianStatus,
                ':id' => (int) $technicianId,
            ]);

            $this->conn->commit();
            return 'updated';
        } catch (Throwable $exception) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $exception;
        }
    }

    public function saveReport($requestId, $technicianId, $userId, $data)
    {
        $this->conn->beginTransaction();
        try {
            $request = $this->conn->prepare(
                "SELECT status FROM requests
                 WHERE id = :id AND technician_id = :technician_id FOR UPDATE"
            );
            $request->execute([
                ':id' => (int) $requestId,
                ':technician_id' => (int) $technicianId,
            ]);
            $status = $request->fetchColumn();
            if ($status === false) {
                $this->conn->rollBack();
                return 'missing';
            }
            if (in_array($status, ['completed', 'cancelled'], true)) {
                $this->conn->rollBack();
                return 'closed';
            }

            $report = $this->conn->prepare(
                'SELECT id FROM repair_reports WHERE request_id = :request_id FOR UPDATE'
            );
            $report->execute([':request_id' => (int) $requestId]);
            $reportId = $report->fetchColumn();
            if ($reportId === false) {
                $insert = $this->conn->prepare(
                    'INSERT INTO repair_reports (request_id, cause, solution, labor_cost, warranty_months)
                     VALUES (:request_id, :cause, :solution, :labor_cost, :warranty_months)'
                );
                $insert->execute([
                    ':request_id' => (int) $requestId,
                    ':cause' => $data['cause'] !== '' ? $data['cause'] : null,
                    ':solution' => $data['solution'] !== '' ? $data['solution'] : null,
                    ':labor_cost' => $data['labor_cost'],
                    ':warranty_months' => (int) $data['warranty_months'],
                ]);
                $reportId = (int) $this->conn->lastInsertId();
            } else {
                $update = $this->conn->prepare(
                    'UPDATE repair_reports SET cause = :cause, solution = :solution,
                     labor_cost = :labor_cost, warranty_months = :warranty_months WHERE id = :id'
                );
                $update->execute([
                    ':cause' => $data['cause'] !== '' ? $data['cause'] : null,
                    ':solution' => $data['solution'] !== '' ? $data['solution'] : null,
                    ':labor_cost' => $data['labor_cost'],
                    ':warranty_months' => (int) $data['warranty_months'],
                    ':id' => (int) $reportId,
                ]);
            }

            $requested = [];
            foreach ($data['parts'] as $partId => $qty) {
                if ((int) $partId < 1 || !is_numeric($qty) || (float) $qty <= 0
                    || (float) $qty > 99999999.99
                    || number_format((float) $qty, 2, '.', '') !== (string) $qty) {
                    $this->conn->rollBack();
                    return 'invalid_parts';
                }
                $requested[(int) $partId] = (float) $qty;
            }

            foreach ($requested as $partId => $qty) {
                $part = $this->conn->prepare(
                    'SELECT stock_qty, price, status FROM parts WHERE id = :id FOR UPDATE'
                );
                $part->execute([':id' => $partId]);
                $partData = $part->fetch(PDO::FETCH_ASSOC);
                if (!$partData || (int) $partData['status'] !== 1
                    || $qty > (float) $partData['stock_qty']) {
                    $this->conn->rollBack();
                    return 'stock';
                }
            }

            $delete = $this->conn->prepare('DELETE FROM report_parts WHERE report_id = :report_id');
            $delete->execute([':report_id' => (int) $reportId]);
            $insertPart = $this->conn->prepare(
                'INSERT INTO report_parts (report_id, part_id, qty, unit_price)
                 SELECT :report_id, parts.id, :qty, parts.price
                 FROM parts WHERE parts.id = :part_id AND parts.status = 1'
            );
            foreach ($requested as $partId => $qty) {
                $insertPart->execute([
                    ':report_id' => (int) $reportId,
                    ':part_id' => $partId,
                    ':qty' => $qty,
                ]);
            }

            $invalidateQuotes = $this->conn->prepare(
                "UPDATE quotes SET status = 'draft'
                 WHERE request_id = :request_id AND status IN ('sent', 'accepted')"
            );
            $invalidateQuotes->execute([':request_id' => (int) $requestId]);
            $this->conn->commit();
            return 'saved';
        } catch (Throwable $exception) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $exception;
        }
    }

    public function createQuote($requestId, $technicianId, $userId)
    {
        $this->conn->beginTransaction();
        try {
            $request = $this->conn->prepare(
                'SELECT status FROM requests WHERE id = :id AND technician_id = :technician_id FOR UPDATE'
            );
            $request->execute([
                ':id' => (int) $requestId,
                ':technician_id' => (int) $technicianId,
            ]);
            $status = $request->fetchColumn();
            if ($status === false) {
                $this->conn->rollBack();
                return 'missing';
            }
            if (!in_array($status, ['checking', 'repairing', 'quoted'], true)) {
                $this->conn->rollBack();
                return 'status';
            }

            $report = $this->conn->prepare(
                'SELECT id, labor_cost FROM repair_reports WHERE request_id = :request_id'
            );
            $report->execute([':request_id' => (int) $requestId]);
            $reportData = $report->fetch(PDO::FETCH_ASSOC);
            if (!$reportData) {
                $this->conn->rollBack();
                return 'report';
            }
            $parts = $this->conn->prepare(
                'SELECT COALESCE(SUM(report_parts.qty * report_parts.unit_price), 0)
                 FROM report_parts WHERE report_id = :report_id'
            );
            $parts->execute([':report_id' => (int) $reportData['id']]);
            $total = (float) $reportData['labor_cost'] + (float) $parts->fetchColumn();
            if ($total > 999999999999) {
                $this->conn->rollBack();
                return 'amount';
            }

            $quote = $this->conn->prepare(
                "INSERT INTO quotes (request_id, total_amount, status)
                 VALUES (:request_id, :total_amount, 'sent')"
            );
            $quote->execute([
                ':request_id' => (int) $requestId,
                ':total_amount' => $total,
            ]);
            if ($status !== 'quoted') {
                $update = $this->conn->prepare("UPDATE requests SET status = 'quoted' WHERE id = :id");
                $update->execute([':id' => (int) $requestId]);
            }
            $log = $this->conn->prepare(
                'INSERT INTO request_logs (request_id, status, note, changed_by)
                 VALUES (:request_id, :status, :note, :changed_by)'
            );
            $log->execute([
                ':request_id' => (int) $requestId,
                ':status' => 'quoted',
                ':note' => 'Đã tạo báo giá: ' . number_format($total, 0, ',', '.') . ' ₫',
                ':changed_by' => (int) $userId,
            ]);
            $busyCheck = $this->conn->prepare(
                "SELECT COUNT(*) FROM requests WHERE technician_id = :technician_id
                 AND status IN ('checking', 'repairing')"
            );
            $busyCheck->execute([':technician_id' => (int) $technicianId]);
            $technicianStatus = (int) $busyCheck->fetchColumn() > 0 ? 'busy' : 'available';
            $setTechnicianStatus = $this->conn->prepare(
                'UPDATE technicians SET status = :status WHERE id = :id'
            );
            $setTechnicianStatus->execute([
                ':status' => $technicianStatus,
                ':id' => (int) $technicianId,
            ]);
            $this->conn->commit();
            return 'quoted';
        } catch (Throwable $exception) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $exception;
        }
    }

    public function countSchedule($technicianId, $start, $end)
    {
        $statement = $this->conn->prepare(
            'SELECT COUNT(*) FROM schedules
             WHERE technician_id = :technician_id
               AND start_time < :end_time AND end_time > :start_time'
        );
        $statement->execute([
            ':technician_id' => (int) $technicianId,
            ':start_time' => $start,
            ':end_time' => $end,
        ]);
        return (int) $statement->fetchColumn();
    }

    public function getSchedule($technicianId, $start, $end, $limit = 10, $offset = 0)
    {
        $statement = $this->conn->prepare(
            'SELECT schedules.id, schedules.start_time, schedules.end_time, schedules.note,
                    requests.id AS request_id, requests.code, requests.title, requests.status,
                    users.full_name AS customer_name
             FROM schedules
             LEFT JOIN requests ON requests.id = schedules.request_id
             LEFT JOIN users ON users.id = requests.customer_id
             WHERE schedules.technician_id = :technician_id
               AND schedules.start_time < :end_time AND schedules.end_time > :start_time
             ORDER BY schedules.start_time LIMIT :limit OFFSET :offset'
        );
        $statement->bindValue(':technician_id', (int) $technicianId, PDO::PARAM_INT);
        $statement->bindValue(':start_time', $start);
        $statement->bindValue(':end_time', $end);
        $statement->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}

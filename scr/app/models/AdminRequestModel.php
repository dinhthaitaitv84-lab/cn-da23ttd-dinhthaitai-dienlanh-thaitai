<?php
require_once __DIR__ . '/../../config/database.php';

class AdminRequestModel
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

    private function conditions($filters)
    {
        $conditions = ['1 = 1'];
        $parameters = [];
        foreach (['status', 'priority', 'category_id', 'technician_id'] as $field) {
            $value = $filters[$field] ?? '';
            if ($value === '' || (in_array($field, ['category_id', 'technician_id'], true) && (int) $value <= 0)) {
                continue;
            }
            $column = $field === 'category_id' ? 's.category_id' : 'r.' . $field;
            $conditions[] = $column . ' = :' . $field;
            $parameters[':' . $field] = in_array($field, ['category_id', 'technician_id'], true)
                ? (int) $value
                : $value;
        }
        if (($filters['search'] ?? '') !== '') {
            $conditions[] = '(r.code LIKE :code_search OR u.full_name LIKE :name_search)';
            $parameters[':code_search'] = '%' . $filters['search'] . '%';
            $parameters[':name_search'] = '%' . $filters['search'] . '%';
        }
        return [$conditions, $parameters];
    }

    public function countAll($filters)
    {
        list($conditions, $parameters) = $this->conditions($filters);
        $statement = $this->conn->prepare(
            'SELECT COUNT(*) FROM requests r
             INNER JOIN users u ON u.id = r.customer_id
             INNER JOIN services s ON s.id = r.service_id
            INNER JOIN service_categories c ON c.id = s.category_id
            WHERE ' . implode(' AND ', $conditions)
        );
        $statement->execute($parameters);
        return (int) $statement->fetchColumn();
    }

    public function getAll($filters, $limit, $offset)
    {
        list($conditions, $parameters) = $this->conditions($filters);
        $statement = $this->conn->prepare(
            'SELECT r.id, r.code, r.priority, r.status, r.created_at,
                    u.full_name AS customer_name, s.name AS service_name,
                    t.id AS technician_id, tu.full_name AS technician_name,
                    c.name AS category_name
             FROM requests r
             INNER JOIN users u ON u.id = r.customer_id
             INNER JOIN services s ON s.id = r.service_id
             INNER JOIN service_categories c ON c.id = s.category_id
             LEFT JOIN technicians t ON t.id = r.technician_id
             LEFT JOIN users tu ON tu.id = t.user_id
             WHERE ' . implode(' AND ', $conditions) . '
             ORDER BY r.created_at DESC, r.id DESC LIMIT :limit OFFSET :offset'
        );
        foreach ($parameters as $key => $value) {
            $statement->bindValue($key, $value);
        }
        $statement->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find($id)
    {
        $statement = $this->conn->prepare(
            'SELECT r.id, r.code, r.customer_id, r.service_id, r.device_id, r.title, r.description,
                    r.address, r.preferred_time, r.priority, r.status, r.technician_id, r.created_at,
                    u.full_name AS customer_name, u.email AS customer_email, u.phone AS customer_phone,
                    s.name AS service_name, s.est_minutes, c.name AS category_name,
                    d.name AS device_name, d.brand AS device_brand, d.model AS device_model,
                    t.status AS technician_status, tu.full_name AS technician_name
             FROM requests r
             INNER JOIN users u ON u.id = r.customer_id
             INNER JOIN services s ON s.id = r.service_id
             INNER JOIN service_categories c ON c.id = s.category_id
             LEFT JOIN devices d ON d.id = r.device_id
             LEFT JOIN technicians t ON t.id = r.technician_id
             LEFT JOIN users tu ON tu.id = t.user_id
             WHERE r.id = :id LIMIT 1'
        );
        $statement->execute([':id' => (int) $id]);
        $request = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$request) {
            return null;
        }

        $images = $this->conn->prepare(
            'SELECT file_path FROM request_images WHERE request_id = :request_id ORDER BY id'
        );
        $images->execute([':request_id' => (int) $id]);
        $request['images'] = $images->fetchAll(PDO::FETCH_COLUMN);

        $logs = $this->conn->prepare(
            'SELECT request_logs.status, request_logs.note, request_logs.created_at,
                    users.full_name AS changed_by_name
             FROM request_logs
             INNER JOIN users ON users.id = request_logs.changed_by
             WHERE request_logs.request_id = :request_id
             ORDER BY request_logs.created_at DESC, request_logs.id DESC'
        );
        $logs->execute([':request_id' => (int) $id]);
        $request['logs'] = $logs->fetchAll(PDO::FETCH_ASSOC);

        $report = $this->conn->prepare(
            'SELECT id, cause, solution, labor_cost, warranty_months
             FROM repair_reports WHERE request_id = :request_id LIMIT 1'
        );
        $report->execute([':request_id' => (int) $id]);
        $request['report'] = $report->fetch(PDO::FETCH_ASSOC);
        $request['report_parts'] = [];
        if ($request['report']) {
            $reportParts = $this->conn->prepare(
                'SELECT parts.name, parts.unit, report_parts.qty, report_parts.unit_price
                 FROM report_parts INNER JOIN parts ON parts.id = report_parts.part_id
                 WHERE report_parts.report_id = :report_id ORDER BY parts.name'
            );
            $reportParts->execute([':report_id' => (int) $request['report']['id']]);
            $request['report_parts'] = $reportParts->fetchAll(PDO::FETCH_ASSOC);
        }

        $quote = $this->conn->prepare(
            'SELECT id, total_amount, status, created_at
             FROM quotes WHERE request_id = :request_id ORDER BY id DESC LIMIT 1'
        );
        $quote->execute([':request_id' => (int) $id]);
        $request['quote'] = $quote->fetch(PDO::FETCH_ASSOC);

        $review = $this->conn->prepare(
            'SELECT reviews.rating, reviews.comment, reviews.created_at,
                    users.full_name AS reviewer_name, technician_users.full_name AS technician_name
             FROM reviews
             INNER JOIN users ON users.id = reviews.customer_id
             LEFT JOIN requests ON requests.id = reviews.request_id
             LEFT JOIN technicians ON technicians.id = requests.technician_id
             LEFT JOIN users AS technician_users ON technician_users.id = technicians.user_id
             WHERE reviews.request_id = :request_id LIMIT 1'
        );
        $review->execute([':request_id' => (int) $id]);
        $request['review'] = $review->fetch(PDO::FETCH_ASSOC);

        return $request;
    }

    public function countReviews($rating, $technicianId)
    {
        $conditions = ['1 = 1'];
        $parameters = [];
        if ($rating > 0) {
            $conditions[] = 'reviews.rating = :rating';
            $parameters[':rating'] = (int) $rating;
        }
        if ($technicianId > 0) {
            $conditions[] = 'requests.technician_id = :technician_id';
            $parameters[':technician_id'] = (int) $technicianId;
        }
        $statement = $this->conn->prepare(
            'SELECT COUNT(*) FROM reviews
             INNER JOIN requests ON requests.id = reviews.request_id
             WHERE ' . implode(' AND ', $conditions)
        );
        $statement->execute($parameters);
        return (int) $statement->fetchColumn();
    }

    public function getReviews($rating, $technicianId, $limit, $offset)
    {
        $conditions = ['1 = 1'];
        $parameters = [];
        if ($rating > 0) {
            $conditions[] = 'reviews.rating = :rating';
            $parameters[':rating'] = (int) $rating;
        }
        if ($technicianId > 0) {
            $conditions[] = 'requests.technician_id = :technician_id';
            $parameters[':technician_id'] = (int) $technicianId;
        }
        $statement = $this->conn->prepare(
            'SELECT reviews.id, reviews.request_id, reviews.rating, reviews.comment, reviews.created_at,
                    customer_users.full_name AS customer_name, requests.code,
                    technician_users.full_name AS technician_name
             FROM reviews
             INNER JOIN requests ON requests.id = reviews.request_id
             INNER JOIN users AS customer_users ON customer_users.id = reviews.customer_id
             LEFT JOIN technicians ON technicians.id = requests.technician_id
             LEFT JOIN users AS technician_users ON technician_users.id = technicians.user_id
             WHERE ' . implode(' AND ', $conditions) . '
             ORDER BY reviews.created_at DESC, reviews.id DESC LIMIT :limit OFFSET :offset'
        );
        foreach ($parameters as $key => $value) {
            $statement->bindValue($key, $value, PDO::PARAM_INT);
        }
        $statement->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updatePriority($id, $priority)
    {
        $statement = $this->conn->prepare('UPDATE requests SET priority = :priority WHERE id = :id');
        $statement->execute([':priority' => $priority, ':id' => (int) $id]);
        if ($statement->rowCount() > 0) {
            return true;
        }
        $check = $this->conn->prepare('SELECT id FROM requests WHERE id = :id');
        $check->execute([':id' => (int) $id]);
        return $check->fetchColumn() !== false;
    }

    public function assign($requestId, $technicianId, $startTime, $endTime, $adminId)
    {
        $this->conn->beginTransaction();
        try {
            $requestStatement = $this->conn->prepare(
                'SELECT requests.status, requests.technician_id, services.est_minutes
                 FROM requests INNER JOIN services ON services.id = requests.service_id
                 WHERE requests.id = :id FOR UPDATE'
            );
            $requestStatement->execute([':id' => (int) $requestId]);
            $request = $requestStatement->fetch(PDO::FETCH_ASSOC);
            if (!$request) {
                $this->conn->rollBack();
                return 'missing';
            }
            if (in_array($request['status'], ['completed', 'cancelled'], true)) {
                $this->conn->rollBack();
                return 'closed';
            }

            $technician = $this->conn->prepare(
                "SELECT t.id, u.full_name FROM technicians t
                 INNER JOIN users u ON u.id = t.user_id
                 WHERE t.id = :id AND t.status <> 'off' AND u.status = 1 AND u.role = 'technician'
                 FOR UPDATE"
            );
            $technician->execute([':id' => (int) $technicianId]);
            $technicianData = $technician->fetch(PDO::FETCH_ASSOC);
            if (!$technicianData) {
                $this->conn->rollBack();
                return 'technician';
            }

            $start = is_string($startTime) ? DateTime::createFromFormat('Y-m-d\TH:i', $startTime) : false;
            $startErrors = DateTime::getLastErrors();
            $end = is_string($endTime) ? DateTime::createFromFormat('Y-m-d\TH:i', $endTime) : false;
            $endErrors = DateTime::getLastErrors();
            $validStart = $start && ($startErrors === false || ($startErrors['warning_count'] === 0 && $startErrors['error_count'] === 0))
                && $start->format('Y-m-d\TH:i') === $startTime;
            $validEnd = $end && ($endErrors === false || ($endErrors['warning_count'] === 0 && $endErrors['error_count'] === 0))
                && $end->format('Y-m-d\TH:i') === $endTime;
            if (!$validStart || !$validEnd || $end <= $start) {
                $this->conn->rollBack();
                return 'time';
            }

            $overlap = $this->conn->prepare(
                'SELECT COUNT(*) FROM schedules
                 WHERE technician_id = :technician_id AND start_time < :end_time AND end_time > :start_time
                   AND (request_id IS NULL OR request_id <> :request_id)'
            );
            $overlap->execute([
                ':technician_id' => (int) $technicianId,
                ':start_time' => $start->format('Y-m-d H:i:s'),
                ':end_time' => $end->format('Y-m-d H:i:s'),
                ':request_id' => (int) $requestId,
            ]);
            if ((int) $overlap->fetchColumn() > 0) {
                $this->conn->rollBack();
                return 'overlap';
            }

            $deleteSchedule = $this->conn->prepare('DELETE FROM schedules WHERE request_id = :request_id');
            $deleteSchedule->execute([':request_id' => (int) $requestId]);
            $schedule = $this->conn->prepare(
                'INSERT INTO schedules (technician_id, request_id, start_time, end_time, note)
                 VALUES (:technician_id, :request_id, :start_time, :end_time, :note)'
            );
            $schedule->execute([
                ':technician_id' => (int) $technicianId,
                ':request_id' => (int) $requestId,
                ':start_time' => $start->format('Y-m-d H:i:s'),
                ':end_time' => $end->format('Y-m-d H:i:s'),
                ':note' => 'Lịch phân công yêu cầu',
            ]);

            $newStatus = $request['status'] === 'pending' ? 'received' : $request['status'];
            $update = $this->conn->prepare(
                'UPDATE requests SET technician_id = :technician_id, status = :status WHERE id = :id'
            );
            $update->execute([
                ':technician_id' => (int) $technicianId,
                ':status' => $newStatus,
                ':id' => (int) $requestId,
            ]);
            $log = $this->conn->prepare(
                'INSERT INTO request_logs (request_id, status, note, changed_by)
                 VALUES (:request_id, :status, :note, :changed_by)'
            );
            $log->execute([
                ':request_id' => (int) $requestId,
                ':status' => $newStatus,
                ':note' => 'Đã phân công kỹ thuật viên: ' . $technicianData['full_name'],
                ':changed_by' => (int) $adminId,
            ]);
            $this->conn->commit();
            return 'assigned';
        } catch (Throwable $exception) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $exception;
        }
    }

    public function cancel($requestId, $reason, $adminId)
    {
        $this->conn->beginTransaction();
        try {
            $check = $this->conn->prepare('SELECT status FROM requests WHERE id = :id FOR UPDATE');
            $check->execute([':id' => (int) $requestId]);
            $status = $check->fetchColumn();
            if ($status === false) {
                $this->conn->rollBack();
                return 'missing';
            }
            if ($status === 'completed' || $status === 'cancelled') {
                $this->conn->rollBack();
                return 'closed';
            }
            $update = $this->conn->prepare("UPDATE requests SET status = 'cancelled' WHERE id = :id");
            $update->execute([':id' => (int) $requestId]);
            $schedule = $this->conn->prepare('DELETE FROM schedules WHERE request_id = :request_id');
            $schedule->execute([':request_id' => (int) $requestId]);
            $log = $this->conn->prepare(
                "INSERT INTO request_logs (request_id, status, note, changed_by)
                 VALUES (:request_id, 'cancelled', :note, :changed_by)"
            );
            $log->execute([
                ':request_id' => (int) $requestId,
                ':note' => 'Admin hủy yêu cầu: ' . $reason,
                ':changed_by' => (int) $adminId,
            ]);
            $this->conn->commit();
            return 'cancelled';
        } catch (Throwable $exception) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $exception;
        }
    }
}

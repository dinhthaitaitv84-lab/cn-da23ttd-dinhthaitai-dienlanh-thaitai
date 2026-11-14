<?php
require_once __DIR__ . '/../../config/database.php';

class RequestModel
{
    private const COMPLETION_CONFIRMATION_NOTE = 'Khách hàng xác nhận hoàn thành dịch vụ';

    private $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
        if (!($this->conn instanceof PDO)) {
            throw new RuntimeException('Không thể kết nối cơ sở dữ liệu.');
        }
    }

    public function getCustomerAddress($customerId)
    {
        $statement = $this->conn->prepare('SELECT address FROM users WHERE id = :id AND role = :role');
        $statement->execute([':id' => (int) $customerId, ':role' => 'customer']);
        return $statement->fetchColumn();
    }

    public function countMine($customerId, $status, $search)
    {
        $conditions = ['r.customer_id = :customer_id'];
        $parameters = [':customer_id' => (int) $customerId];
        if ($status !== '') {
            $conditions[] = 'r.status = :status';
            $parameters[':status'] = $status;
        }
        if ($search !== '') {
            $conditions[] = 'r.code LIKE :search';
            $parameters[':search'] = '%' . $search . '%';
        }

        $statement = $this->conn->prepare(
            'SELECT COUNT(*) FROM requests r
             INNER JOIN services s ON s.id = r.service_id
             WHERE ' . implode(' AND ', $conditions)
        );
        $statement->execute($parameters);
        return (int) $statement->fetchColumn();
    }

    public function getMine($customerId, $status, $search, $limit, $offset)
    {
        $conditions = ['r.customer_id = :customer_id'];
        $parameters = [':customer_id' => (int) $customerId];
        if ($status !== '') {
            $conditions[] = 'r.status = :status';
            $parameters[':status'] = $status;
        }
        if ($search !== '') {
            $conditions[] = 'r.code LIKE :search';
            $parameters[':search'] = '%' . $search . '%';
        }

        $statement = $this->conn->prepare(
            'SELECT r.id, r.code, r.created_at, r.status, s.name AS service_name
             FROM requests r
             INNER JOIN services s ON s.id = r.service_id
             WHERE ' . implode(' AND ', $conditions) . '
             ORDER BY r.created_at DESC, r.id DESC
             LIMIT :limit OFFSET :offset'
        );
        foreach ($parameters as $key => $value) {
            $statement->bindValue($key, $value);
        }
        $statement->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findMine($requestId, $customerId)
    {
        $statement = $this->conn->prepare(
            'SELECT r.id, r.code, r.title, r.description, r.address, r.preferred_time,
                    r.priority, r.status, r.created_at, s.name AS service_name,
                    d.name AS device_name, d.brand AS device_brand, d.model AS device_model,
                    c.name AS category_name, u.full_name AS technician_name, u.phone AS technician_phone
             FROM requests r
             INNER JOIN services s ON s.id = r.service_id
             LEFT JOIN devices d ON d.id = r.device_id
             LEFT JOIN service_categories c ON c.id = d.category_id
             LEFT JOIN technicians t ON t.id = r.technician_id
             LEFT JOIN users u ON u.id = t.user_id
             WHERE r.id = :id AND r.customer_id = :customer_id
             LIMIT 1'
        );
        $statement->execute([
            ':id' => (int) $requestId,
            ':customer_id' => (int) $customerId,
        ]);
        $request = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$request) {
            return null;
        }

        $imageStatement = $this->conn->prepare(
            'SELECT file_path FROM request_images WHERE request_id = :request_id ORDER BY id'
        );
        $imageStatement->execute([':request_id' => (int) $requestId]);
        $request['images'] = $imageStatement->fetchAll(PDO::FETCH_COLUMN);

        $logStatement = $this->conn->prepare(
            'SELECT status, note, created_at, changed_by FROM request_logs
             WHERE request_id = :request_id ORDER BY created_at DESC, id DESC'
        );
        $logStatement->execute([':request_id' => (int) $requestId]);
        $request['logs'] = $logStatement->fetchAll(PDO::FETCH_ASSOC);

        $confirmationStatement = $this->conn->prepare(
            'SELECT created_at FROM request_logs
             WHERE request_id = :request_id AND status = :status
               AND changed_by = :customer_id AND note = :note
             ORDER BY id DESC LIMIT 1'
        );
        $confirmationStatement->execute([
            ':request_id' => (int) $requestId,
            ':status' => 'completed',
            ':customer_id' => (int) $customerId,
            ':note' => self::COMPLETION_CONFIRMATION_NOTE,
        ]);
        $confirmationDate = $confirmationStatement->fetchColumn();
        $request['completion_confirmed_at'] = $confirmationDate === false ? null : $confirmationDate;

        $reportStatement = $this->conn->prepare(
            'SELECT id, cause, solution, labor_cost, warranty_months
             FROM repair_reports WHERE request_id = :request_id LIMIT 1'
        );
        $reportStatement->execute([':request_id' => (int) $requestId]);
        $request['report'] = $reportStatement->fetch(PDO::FETCH_ASSOC);
        $request['report_parts'] = [];
        if ($request['report']) {
            $partStatement = $this->conn->prepare(
                'SELECT parts.name, parts.unit, report_parts.qty, report_parts.unit_price
                 FROM report_parts INNER JOIN parts ON parts.id = report_parts.part_id
                 WHERE report_parts.report_id = :report_id ORDER BY parts.name'
            );
            $partStatement->execute([':report_id' => (int) $request['report']['id']]);
            $request['report_parts'] = $partStatement->fetchAll(PDO::FETCH_ASSOC);
        }

        $quoteStatement = $this->conn->prepare(
            'SELECT id, total_amount, status, created_at
             FROM quotes WHERE request_id = :request_id AND status <> :draft
             ORDER BY id DESC LIMIT 1'
        );
        $quoteStatement->execute([
            ':request_id' => (int) $requestId,
            ':draft' => 'draft',
        ]);
        $request['quote'] = $quoteStatement->fetch(PDO::FETCH_ASSOC);

        $reviewStatement = $this->conn->prepare(
            'SELECT rating, comment, created_at FROM reviews
             WHERE request_id = :request_id AND customer_id = :customer_id LIMIT 1'
        );
        $reviewStatement->execute([
            ':request_id' => (int) $requestId,
            ':customer_id' => (int) $customerId,
        ]);
        $request['review'] = $reviewStatement->fetch(PDO::FETCH_ASSOC);

        return $request;
    }

    public function quoteDecision($requestId, $customerId, $decision, $reason)
    {
        if (!in_array($decision, ['accepted', 'rejected'], true)) {
            return 'invalid';
        }
        $this->conn->beginTransaction();
        try {
            $requestStatement = $this->conn->prepare(
                'SELECT status FROM requests
                 WHERE id = :id AND customer_id = :customer_id FOR UPDATE'
            );
            $requestStatement->execute([
                ':id' => (int) $requestId,
                ':customer_id' => (int) $customerId,
            ]);
            $status = $requestStatement->fetchColumn();
            if ($status === false) {
                $this->conn->rollBack();
                return 'missing';
            }
            if ($status !== 'quoted') {
                $this->conn->rollBack();
                return 'invalid_status';
            }

            $quote = $this->conn->prepare(
                "SELECT id FROM quotes
                 WHERE request_id = :request_id AND status = 'sent'
                 ORDER BY id DESC LIMIT 1 FOR UPDATE"
            );
            $quote->execute([':request_id' => (int) $requestId]);
            $quoteId = $quote->fetchColumn();
            if ($quoteId === false) {
                $this->conn->rollBack();
                return 'no_quote';
            }

            $updateQuote = $this->conn->prepare(
                'UPDATE quotes SET status = :status WHERE id = :id AND status = :current_status'
            );
            $updateQuote->execute([
                ':status' => $decision,
                ':id' => (int) $quoteId,
                ':current_status' => 'sent',
            ]);
            if ($updateQuote->rowCount() !== 1) {
                $this->conn->rollBack();
                return 'no_quote';
            }

            $newStatus = $decision === 'accepted' ? 'repairing' : 'checking';
            $updateRequest = $this->conn->prepare('UPDATE requests SET status = :status WHERE id = :id');
            $updateRequest->execute([
                ':status' => $newStatus,
                ':id' => (int) $requestId,
            ]);
            $note = $decision === 'accepted'
                ? 'Khách chấp nhận báo giá'
                : 'Khách từ chối báo giá: ' . $reason;
            $log = $this->conn->prepare(
                'INSERT INTO request_logs (request_id, status, note, changed_by)
                 VALUES (:request_id, :status, :note, :changed_by)'
            );
            $log->execute([
                ':request_id' => (int) $requestId,
                ':status' => $newStatus,
                ':note' => $note,
                ':changed_by' => (int) $customerId,
            ]);
            $technicianStatement = $this->conn->prepare(
                'SELECT technician_id FROM requests WHERE id = :id'
            );
            $technicianStatement->execute([':id' => (int) $requestId]);
            $technicianId = $technicianStatement->fetchColumn();
            if ($technicianId !== false && $technicianId !== null) {
                $technicianStatus = $this->conn->prepare(
                    'UPDATE technicians SET status = :status WHERE id = :id'
                );
                $technicianStatus->execute([
                    ':status' => 'busy',
                    ':id' => (int) $technicianId,
                ]);
            }
            $this->conn->commit();
            return $decision;
        } catch (Throwable $exception) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $exception;
        }
    }

    public function createReview($requestId, $customerId, $rating, $comment)
    {
        $this->conn->beginTransaction();
        try {
            $request = $this->conn->prepare(
                'SELECT status FROM requests
                 WHERE id = :id AND customer_id = :customer_id FOR UPDATE'
            );
            $request->execute([
                ':id' => (int) $requestId,
                ':customer_id' => (int) $customerId,
            ]);
            $status = $request->fetchColumn();
            if ($status === false) {
                $this->conn->rollBack();
                return 'missing';
            }
            if ($status !== 'completed') {
                $this->conn->rollBack();
                return 'not_completed';
            }

            $confirmation = $this->conn->prepare(
                'SELECT id FROM request_logs
                 WHERE request_id = :request_id AND status = :status
                   AND changed_by = :customer_id AND note = :note
                 LIMIT 1'
            );
            $confirmation->execute([
                ':request_id' => (int) $requestId,
                ':status' => 'completed',
                ':customer_id' => (int) $customerId,
                ':note' => self::COMPLETION_CONFIRMATION_NOTE,
            ]);
            if ($confirmation->fetchColumn() === false) {
                $this->conn->rollBack();
                return 'not_confirmed';
            }

            $existing = $this->conn->prepare('SELECT id FROM reviews WHERE request_id = :request_id');
            $existing->execute([':request_id' => (int) $requestId]);
            if ($existing->fetchColumn() !== false) {
                $this->conn->rollBack();
                return 'exists';
            }
            $insert = $this->conn->prepare(
                'INSERT INTO reviews (request_id, customer_id, rating, comment)
                 VALUES (:request_id, :customer_id, :rating, :comment)'
            );
            $insert->execute([
                ':request_id' => (int) $requestId,
                ':customer_id' => (int) $customerId,
                ':rating' => (int) $rating,
                ':comment' => $comment !== '' ? $comment : null,
            ]);
            $this->conn->commit();
            return 'created';
        } catch (PDOException $exception) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            if ($exception->getCode() === '23000') {
                return 'exists';
            }
            throw $exception;
        } catch (Throwable $exception) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $exception;
        }
    }

    public function confirmCompletion($requestId, $customerId)
    {
        $this->conn->beginTransaction();
        try {
            $request = $this->conn->prepare(
                'SELECT status FROM requests
                 WHERE id = :id AND customer_id = :customer_id FOR UPDATE'
            );
            $request->execute([
                ':id' => (int) $requestId,
                ':customer_id' => (int) $customerId,
            ]);
            $status = $request->fetchColumn();
            if ($status === false) {
                $this->conn->rollBack();
                return 'missing';
            }
            if ($status !== 'completed') {
                $this->conn->rollBack();
                return 'not_completed';
            }

            $existing = $this->conn->prepare(
                'SELECT id FROM request_logs
                 WHERE request_id = :request_id AND status = :status
                   AND changed_by = :customer_id AND note = :note
                 LIMIT 1'
            );
            $existing->execute([
                ':request_id' => (int) $requestId,
                ':status' => 'completed',
                ':customer_id' => (int) $customerId,
                ':note' => self::COMPLETION_CONFIRMATION_NOTE,
            ]);
            if ($existing->fetchColumn() !== false) {
                $this->conn->commit();
                return 'already_confirmed';
            }

            $log = $this->conn->prepare(
                'INSERT INTO request_logs (request_id, status, note, changed_by)
                 VALUES (:request_id, :status, :note, :changed_by)'
            );
            $log->execute([
                ':request_id' => (int) $requestId,
                ':status' => 'completed',
                ':note' => self::COMPLETION_CONFIRMATION_NOTE,
                ':changed_by' => (int) $customerId,
            ]);
            $this->conn->commit();
            return 'confirmed';
        } catch (Throwable $exception) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $exception;
        }
    }

    public function cancelMine($requestId, $customerId)
    {
        $this->conn->beginTransaction();
        try {
            $check = $this->conn->prepare(
                'SELECT status FROM requests WHERE id = :id AND customer_id = :customer_id FOR UPDATE'
            );
            $check->execute([
                ':id' => (int) $requestId,
                ':customer_id' => (int) $customerId,
            ]);
            $status = $check->fetchColumn();
            if ($status === false) {
                $this->conn->rollBack();
                return 'missing';
            }
            if ($status !== 'pending') {
                $this->conn->rollBack();
                return 'not_pending';
            }

            $update = $this->conn->prepare(
                "UPDATE requests SET status = 'cancelled'
                 WHERE id = :id AND customer_id = :customer_id AND status = 'pending'"
            );
            $update->execute([
                ':id' => (int) $requestId,
                ':customer_id' => (int) $customerId,
            ]);
            if ($update->rowCount() !== 1) {
                throw new RuntimeException('Không thể cập nhật trạng thái yêu cầu.');
            }

            $log = $this->conn->prepare(
                "INSERT INTO request_logs (request_id, status, note, changed_by)
                 VALUES (:request_id, 'cancelled', 'Khách hủy yêu cầu', :changed_by)"
            );
            $log->execute([
                ':request_id' => (int) $requestId,
                ':changed_by' => (int) $customerId,
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

    public function store($data, $images)
    {
        $this->conn->beginTransaction();
        try {
            $serviceCheck = $this->conn->prepare(
                'SELECT s.category_id FROM services s
                 INNER JOIN service_categories c ON c.id = s.category_id
                 WHERE s.id = :id AND s.status = 1 AND c.status = 1'
            );
            $serviceCheck->execute([':id' => (int) $data['service_id']]);
            $categoryId = $serviceCheck->fetchColumn();
            if ($categoryId === false) {
                throw new InvalidArgumentException('Dịch vụ đã chọn hiện không khả dụng.');
            }

            $deviceId = null;
            if ($data['device_id'] !== '') {
                $deviceCheck = $this->conn->prepare(
                    'SELECT id FROM devices WHERE id = :id AND customer_id = :customer_id'
                );
                $deviceCheck->execute([
                    ':id' => (int) $data['device_id'],
                    ':customer_id' => (int) $data['customer_id'],
                ]);
                $deviceId = $deviceCheck->fetchColumn();
                if ($deviceId === false) {
                    throw new InvalidArgumentException('Thiết bị đã chọn không hợp lệ.');
                }
            } elseif ($data['device_name'] !== '') {
                $deviceStatement = $this->conn->prepare(
                    'INSERT INTO devices (customer_id, category_id, name, brand, model)
                     VALUES (:customer_id, :category_id, :name, :brand, :model)'
                );
                $deviceStatement->execute([
                    ':customer_id' => (int) $data['customer_id'],
                    ':category_id' => (int) $categoryId,
                    ':name' => $data['device_name'],
                    ':brand' => $data['device_brand'] !== '' ? $data['device_brand'] : null,
                    ':model' => $data['device_model'] !== '' ? $data['device_model'] : null,
                ]);
                $deviceId = (int) $this->conn->lastInsertId();
            }

            $code = $this->uniqueCode();
            $statement = $this->conn->prepare(
                "INSERT INTO requests
                    (code, customer_id, service_id, device_id, title, description, address, preferred_time, priority, status)
                 VALUES
                    (:code, :customer_id, :service_id, :device_id, :title, :description, :address, :preferred_time, 'normal', 'pending')"
            );
            $statement->execute([
                ':code' => $code,
                ':customer_id' => (int) $data['customer_id'],
                ':service_id' => (int) $data['service_id'],
                ':device_id' => $deviceId,
                ':title' => $data['title'],
                ':description' => $data['description'] !== '' ? $data['description'] : null,
                ':address' => $data['address'],
                ':preferred_time' => $data['preferred_time'],
            ]);
            $requestId = (int) $this->conn->lastInsertId();

            $imageStatement = $this->conn->prepare(
                'INSERT INTO request_images (request_id, file_path) VALUES (:request_id, :file_path)'
            );
            foreach ($images as $imagePath) {
                $imageStatement->execute([
                    ':request_id' => $requestId,
                    ':file_path' => $imagePath,
                ]);
            }

            $logStatement = $this->conn->prepare(
                "INSERT INTO request_logs (request_id, status, note, changed_by)
                 VALUES (:request_id, 'pending', 'Khách gửi yêu cầu', :changed_by)"
            );
            $logStatement->execute([
                ':request_id' => $requestId,
                ':changed_by' => (int) $data['customer_id'],
            ]);

            $this->conn->commit();
            return $code;
        } catch (Throwable $exception) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $exception;
        }
    }

    private function uniqueCode()
    {
        $check = $this->conn->prepare('SELECT 1 FROM requests WHERE code = :code LIMIT 1');
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $code = 'DL' . date('ymd') . sprintf('%04d', random_int(0, 9999));
            $check->execute([':code' => $code]);
            if ($check->fetchColumn() === false) {
                return $code;
            }
        }

        throw new RuntimeException('Không thể tạo mã yêu cầu duy nhất.');
    }
}

<?php
require_once __DIR__ . '/../../config/database.php';

class CustomerModel
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

    private function conditions($search)
    {
        $conditions = ["users.role = 'customer'"];
        $parameters = [];
        if ($search !== '') {
            $conditions[] = '(users.full_name LIKE :name_search OR users.phone LIKE :phone_search OR users.email LIKE :email_search)';
            $parameters[':name_search'] = '%' . $search . '%';
            $parameters[':phone_search'] = '%' . $search . '%';
            $parameters[':email_search'] = '%' . $search . '%';
        }
        return [$conditions, $parameters];
    }

    public function countAll($search)
    {
        list($conditions, $parameters) = $this->conditions($search);
        $statement = $this->conn->prepare(
            'SELECT COUNT(*) FROM users WHERE ' . implode(' AND ', $conditions)
        );
        $statement->execute($parameters);
        return (int) $statement->fetchColumn();
    }

    public function getAll($search, $limit, $offset)
    {
        list($conditions, $parameters) = $this->conditions($search);
        $statement = $this->conn->prepare(
            'SELECT users.id, users.full_name, users.phone, users.email, users.status, users.created_at,
                    (SELECT COUNT(*) FROM requests WHERE requests.customer_id = users.id) AS request_count,
                    (SELECT COALESCE(SUM(quotes.total_amount), 0)
                     FROM requests
                     INNER JOIN quotes ON quotes.request_id = requests.id
                     WHERE requests.customer_id = users.id AND quotes.status = :accepted_status) AS total_spent
             FROM users WHERE ' . implode(' AND ', $conditions) . '
             ORDER BY users.created_at DESC, users.id DESC LIMIT :limit OFFSET :offset'
        );
        foreach ($parameters as $key => $value) {
            $statement->bindValue($key, $value);
        }
        $statement->bindValue(':accepted_status', 'accepted');
        $statement->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function setStatus($customerId, $status)
    {
        $statement = $this->conn->prepare(
            "UPDATE users SET status = :status
             WHERE id = :id AND role = 'customer'"
        );
        $statement->execute([':status' => (int) $status, ':id' => (int) $customerId]);
        if ($statement->rowCount() > 0) {
            return true;
        }
        $check = $this->conn->prepare("SELECT status FROM users WHERE id = :id AND role = 'customer'");
        $check->execute([':id' => (int) $customerId]);
        $current = $check->fetchColumn();
        return $current !== false && (int) $current === (int) $status;
    }

    public function resetPassword($customerId, $password)
    {
        $statement = $this->conn->prepare(
            "UPDATE users SET password = :password
             WHERE id = :id AND role = 'customer'"
        );
        $statement->execute([
            ':password' => password_hash($password, PASSWORD_DEFAULT),
            ':id' => (int) $customerId,
        ]);
        if ($statement->rowCount() > 0) {
            return true;
        }
        $check = $this->conn->prepare(
            "SELECT id FROM users WHERE id = :id AND role = 'customer' LIMIT 1"
        );
        $check->execute([':id' => (int) $customerId]);
        return $check->fetchColumn() !== false;
    }

    public function countRequests($customerId)
    {
        $statement = $this->conn->prepare(
            'SELECT COUNT(*) FROM requests
             INNER JOIN services ON services.id = requests.service_id
             WHERE customer_id = :customer_id'
        );
        $statement->execute([':customer_id' => (int) $customerId]);
        return (int) $statement->fetchColumn();
    }

    public function find($customerId, $limit = 10, $offset = 0)
    {
        $statement = $this->conn->prepare(
            "SELECT id, full_name, phone, email, address, status, created_at
             FROM users WHERE id = :id AND role = 'customer' LIMIT 1"
        );
        $statement->execute([':id' => (int) $customerId]);
        $customer = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$customer) {
            return null;
        }

        $devices = $this->conn->prepare(
            'SELECT devices.id, devices.name, devices.brand, devices.model,
                    service_categories.name AS category_name
             FROM devices
             INNER JOIN service_categories ON service_categories.id = devices.category_id
             WHERE devices.customer_id = :customer_id ORDER BY devices.name, devices.id'
        );
        $devices->execute([':customer_id' => (int) $customerId]);
        $customer['devices'] = $devices->fetchAll(PDO::FETCH_ASSOC);

        $requests = $this->conn->prepare(
            'SELECT requests.id, requests.code, requests.status, requests.created_at,
                    services.name AS service_name, devices.id AS device_id,
                    devices.name AS device_name, devices.brand AS device_brand, devices.model AS device_model,
                    repair_reports.cause, repair_reports.solution, repair_reports.warranty_months,
                    repair_reports.created_at AS report_created_at,
                    (SELECT request_logs.created_at FROM request_logs
                     WHERE request_logs.request_id = requests.id AND request_logs.status = :completed_status
                       AND request_logs.changed_by <> requests.customer_id
                     ORDER BY request_logs.created_at DESC, request_logs.id DESC LIMIT 1) AS completed_at,
                    (SELECT quotes.total_amount FROM quotes
                     WHERE quotes.request_id = requests.id AND quotes.status = :accepted_status
                     ORDER BY quotes.id DESC LIMIT 1) AS cost
             FROM requests
             INNER JOIN services ON services.id = requests.service_id
             LEFT JOIN devices ON devices.id = requests.device_id
             LEFT JOIN repair_reports ON repair_reports.request_id = requests.id
             WHERE requests.customer_id = :customer_id
             ORDER BY requests.created_at DESC, requests.id DESC LIMIT :limit OFFSET :offset'
        );
        $requests->bindValue(':accepted_status', 'accepted');
        $requests->bindValue(':completed_status', 'completed');
        $requests->bindValue(':customer_id', (int) $customerId, PDO::PARAM_INT);
        $requests->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $requests->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        $requests->execute();
        $customer['requests'] = $requests->fetchAll(PDO::FETCH_ASSOC);
        return $customer;
    }

    public function countDeviceRepairs($deviceId, $customerId = null)
    {
        $conditions = ['requests.device_id = :device_id'];
        $parameters = [':device_id' => (int) $deviceId];
        if ($customerId !== null) {
            $conditions[] = 'devices.customer_id = :customer_id';
            $parameters[':customer_id'] = (int) $customerId;
        }
        $statement = $this->conn->prepare(
            'SELECT COUNT(*) FROM requests INNER JOIN devices ON devices.id = requests.device_id
             WHERE ' . implode(' AND ', $conditions)
        );
        $statement->execute($parameters);
        return (int) $statement->fetchColumn();
    }

    public function deviceHistory($deviceId, $customerId = null, $limit = 10, $offset = 0)
    {
        $conditions = ['devices.id = :device_id'];
        $parameters = [':device_id' => (int) $deviceId];
        if ($customerId !== null) {
            $conditions[] = 'devices.customer_id = :customer_id';
            $parameters[':customer_id'] = (int) $customerId;
        }
        $deviceStatement = $this->conn->prepare(
            'SELECT devices.id, devices.customer_id, devices.name, devices.brand, devices.model,
                    devices.note, service_categories.name AS category_name
             FROM devices
             INNER JOIN service_categories ON service_categories.id = devices.category_id
             WHERE ' . implode(' AND ', $conditions) . ' LIMIT 1'
        );
        $deviceStatement->execute($parameters);
        $device = $deviceStatement->fetch(PDO::FETCH_ASSOC);
        if (!$device) {
            return null;
        }

        $repairs = $this->conn->prepare(
            'SELECT requests.id, requests.code, requests.status, requests.created_at,
                    services.name AS service_name, repair_reports.cause, repair_reports.solution,
                    repair_reports.labor_cost, repair_reports.warranty_months,
                    (SELECT request_logs.created_at FROM request_logs
                     WHERE request_logs.request_id = requests.id AND request_logs.status = :completed_status
                       AND request_logs.changed_by <> requests.customer_id
                     ORDER BY request_logs.created_at DESC, request_logs.id DESC LIMIT 1) AS completed_at,
                    (SELECT quotes.total_amount FROM quotes
                     WHERE quotes.request_id = requests.id AND quotes.status = :accepted_status
                     ORDER BY quotes.id DESC LIMIT 1) AS total_cost
             FROM requests
             INNER JOIN services ON services.id = requests.service_id
             LEFT JOIN repair_reports ON repair_reports.request_id = requests.id
             WHERE requests.device_id = :device_id
             ORDER BY requests.created_at DESC, requests.id DESC LIMIT :limit OFFSET :offset'
        );
        $repairs->bindValue(':completed_status', 'completed');
        $repairs->bindValue(':accepted_status', 'accepted');
        $repairs->bindValue(':device_id', (int) $deviceId, PDO::PARAM_INT);
        $repairs->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $repairs->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        $repairs->execute();
        $device['repairs'] = $repairs->fetchAll(PDO::FETCH_ASSOC);

        $parts = $this->conn->prepare(
            'SELECT requests.id AS request_id, report_parts.part_id, parts.name, parts.unit,
                    report_parts.qty, report_parts.unit_price
             FROM report_parts
             INNER JOIN parts ON parts.id = report_parts.part_id
             INNER JOIN repair_reports ON repair_reports.id = report_parts.report_id
             INNER JOIN requests ON requests.id = repair_reports.request_id
             WHERE requests.device_id = :device_id
               AND requests.id IN (
                   SELECT page_requests.id FROM (
                       SELECT id FROM requests WHERE device_id = :page_device_id
                       ORDER BY created_at DESC, id DESC LIMIT :page_limit OFFSET :page_offset
                   ) AS page_requests
               )
             ORDER BY requests.created_at DESC, parts.name'
        );
        $parts->bindValue(':device_id', (int) $deviceId, PDO::PARAM_INT);
        $parts->bindValue(':page_device_id', (int) $deviceId, PDO::PARAM_INT);
        $parts->bindValue(':page_limit', (int) $limit, PDO::PARAM_INT);
        $parts->bindValue(':page_offset', (int) $offset, PDO::PARAM_INT);
        $parts->execute();
        $device['repair_parts'] = $parts->fetchAll(PDO::FETCH_ASSOC);
        return $device;
    }
}

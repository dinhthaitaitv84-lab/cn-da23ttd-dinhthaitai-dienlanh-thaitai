<?php
require_once __DIR__ . '/../../config/database.php';

class AdminScheduleModel
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

    private function conditions($start, $end, $technicianId)
    {
        $conditions = ['s.start_time < :week_end', 's.end_time > :week_start'];
        $parameters = [':week_end' => $end, ':week_start' => $start];
        if ($technicianId > 0) {
            $conditions[] = 's.technician_id = :technician_id';
            $parameters[':technician_id'] = $technicianId;
        }
        return [$conditions, $parameters];
    }

    public function countWeek($start, $end, $technicianId = 0)
    {
        list($conditions, $parameters) = $this->conditions($start, $end, $technicianId);
        $statement = $this->conn->prepare(
            'SELECT COUNT(*) FROM schedules s WHERE ' . implode(' AND ', $conditions)
        );
        $statement->execute($parameters);
        return (int) $statement->fetchColumn();
    }

    public function getWeek($start, $end, $technicianId = 0, $limit = 10, $offset = 0)
    {
        list($conditions, $parameters) = $this->conditions($start, $end, $technicianId);
        $statement = $this->conn->prepare(
            'SELECT s.id, s.technician_id, s.request_id, s.start_time, s.end_time, s.note,
                    users.full_name AS technician_name, users.phone AS technician_phone,
                    requests.code AS request_code, requests.title AS request_title,
                    requests.status AS request_status
             FROM schedules s
             INNER JOIN technicians ON technicians.id = s.technician_id
             INNER JOIN users ON users.id = technicians.user_id
             LEFT JOIN requests ON requests.id = s.request_id
             WHERE ' . implode(' AND ', $conditions) . '
             ORDER BY s.start_time, users.full_name, s.id LIMIT :limit OFFSET :offset'
        );
        foreach ($parameters as $key => $value) {
            $statement->bindValue($key, $value);
        }
        $statement->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}

<?php
require_once __DIR__ . '/../../config/database.php';

class TechnicianModel
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

    public function countAll($search)
    {
        $sql = "SELECT COUNT(*) FROM technicians t
                INNER JOIN users u ON u.id = t.user_id
                WHERE u.role = 'technician'";
        $parameters = [];
        if ($search !== '') {
            $sql .= ' AND u.full_name LIKE :search';
            $parameters[':search'] = '%' . $search . '%';
        }
        $statement = $this->conn->prepare($sql);
        $statement->execute($parameters);
        return (int) $statement->fetchColumn();
    }

    public function getAll($search, $limit, $offset)
    {
        $sql = "SELECT t.id, t.user_id, t.specialty, t.experience_years, t.status AS technician_status,
                       u.full_name, u.phone, u.email, u.status AS account_status,
                       (SELECT COUNT(*) FROM requests r
                        WHERE r.technician_id = t.id
                          AND r.status NOT IN ('completed', 'cancelled')) AS active_requests
                FROM technicians t
                INNER JOIN users u ON u.id = t.user_id
                WHERE u.role = 'technician'";
        $parameters = [];
        if ($search !== '') {
            $sql .= ' AND u.full_name LIKE :search';
            $parameters[':search'] = '%' . $search . '%';
        }
        $sql .= ' ORDER BY u.full_name, t.id LIMIT :limit OFFSET :offset';
        $statement = $this->conn->prepare($sql);
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
            "SELECT t.id, t.user_id, t.specialty, t.experience_years, t.status AS technician_status,
                    u.full_name, u.phone, u.email, u.status AS account_status
             FROM technicians t
             INNER JOIN users u ON u.id = t.user_id
             WHERE t.id = :id AND u.role = 'technician' LIMIT 1"
        );
        $statement->execute([':id' => (int) $id]);
        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $this->conn->beginTransaction();
        try {
            $check = $this->conn->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
            $check->execute([':email' => $data['email']]);
            if ($check->fetchColumn() !== false) {
                $this->conn->rollBack();
                return 'duplicate';
            }

            $user = $this->conn->prepare(
                "INSERT INTO users (full_name, email, phone, password, role, status)
                 VALUES (:full_name, :email, :phone, :password, 'technician', 1)"
            );
            $user->execute([
                ':full_name' => $data['full_name'],
                ':email' => $data['email'],
                ':phone' => $data['phone'] !== '' ? $data['phone'] : null,
                ':password' => password_hash($data['password'], PASSWORD_DEFAULT),
            ]);
            $userId = (int) $this->conn->lastInsertId();

            $technician = $this->conn->prepare(
                'INSERT INTO technicians (user_id, specialty, experience_years, status)
                 VALUES (:user_id, :specialty, :experience_years, :status)'
            );
            $technician->execute([
                ':user_id' => $userId,
                ':specialty' => $data['specialty'],
                ':experience_years' => (int) $data['experience_years'],
                ':status' => $data['status'],
            ]);
            $this->conn->commit();
            return 'created';
        } catch (Throwable $exception) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $exception;
        }
    }

    public function update($id, $data)
    {
        $this->conn->beginTransaction();
        try {
            $technician = $this->find($id);
            if (!$technician) {
                $this->conn->rollBack();
                return 'missing';
            }
            $emailCheck = $this->conn->prepare('SELECT id FROM users WHERE email = :email AND id <> :user_id');
            $emailCheck->execute([':email' => $data['email'], ':user_id' => (int) $technician['user_id']]);
            if ($emailCheck->fetchColumn() !== false) {
                $this->conn->rollBack();
                return 'duplicate';
            }

            $user = $this->conn->prepare(
                'UPDATE users SET full_name = :full_name, email = :email, phone = :phone
                 WHERE id = :user_id AND role = :role'
            );
            $user->execute([
                ':full_name' => $data['full_name'],
                ':email' => $data['email'],
                ':phone' => $data['phone'] !== '' ? $data['phone'] : null,
                ':user_id' => (int) $technician['user_id'],
                ':role' => 'technician',
            ]);
            $update = $this->conn->prepare(
                'UPDATE technicians SET specialty = :specialty, experience_years = :experience_years,
                 status = :status WHERE id = :id'
            );
            $update->execute([
                ':specialty' => $data['specialty'],
                ':experience_years' => (int) $data['experience_years'],
                ':status' => $data['status'],
                ':id' => (int) $id,
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

    public function setStatus($id, $status)
    {
        $statement = $this->conn->prepare('UPDATE technicians SET status = :status WHERE id = :id');
        $statement->execute([':status' => $status, ':id' => (int) $id]);
        return $statement->rowCount() > 0;
    }

    public function setAccountStatus($id, $status)
    {
        $statement = $this->conn->prepare(
            "UPDATE users u INNER JOIN technicians t ON t.user_id = u.id
             SET u.status = :status WHERE t.id = :id AND u.role = 'technician'"
        );
        $statement->execute([':status' => (int) $status, ':id' => (int) $id]);
        return $statement->rowCount() > 0;
    }

    public function resetPassword($id, $password)
    {
        $statement = $this->conn->prepare(
            "UPDATE users u INNER JOIN technicians t ON t.user_id = u.id
             SET u.password = :password WHERE t.id = :id AND u.role = 'technician'"
        );
        $statement->execute([
            ':password' => password_hash($password, PASSWORD_DEFAULT),
            ':id' => (int) $id,
        ]);
        return $statement->rowCount() > 0;
    }

    public function delete($id)
    {
        $this->conn->beginTransaction();
        try {
            $check = $this->conn->prepare('SELECT user_id FROM technicians WHERE id = :id FOR UPDATE');
            $check->execute([':id' => (int) $id]);
            $userId = $check->fetchColumn();
            if ($userId === false) {
                $this->conn->rollBack();
                return 'missing';
            }

            $assigned = $this->conn->prepare('SELECT COUNT(*) FROM requests WHERE technician_id = :id');
            $assigned->execute([':id' => (int) $id]);
            if ((int) $assigned->fetchColumn() > 0) {
                $this->conn->rollBack();
                return 'assigned';
            }

            $logs = $this->conn->prepare(
                'SELECT COUNT(*) FROM request_logs WHERE changed_by = :user_id'
            );
            $logs->execute([':user_id' => (int) $userId]);
            if ((int) $logs->fetchColumn() > 0) {
                $this->conn->rollBack();
                return 'logged';
            }

            $deleteSchedules = $this->conn->prepare('DELETE FROM schedules WHERE technician_id = :technician_id');
            $deleteSchedules->execute([':technician_id' => (int) $id]);
            $delete = $this->conn->prepare('DELETE FROM technicians WHERE id = :id');
            $delete->execute([':id' => (int) $id]);
            $deleteUser = $this->conn->prepare("DELETE FROM users WHERE id = :id AND role = 'technician'");
            $deleteUser->execute([':id' => (int) $userId]);
            $this->conn->commit();
            return 'deleted';
        } catch (Throwable $exception) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $exception;
        }
    }

    public function getAssignable()
    {
        $statement = $this->conn->prepare(
            "SELECT t.id, t.specialty, t.experience_years, u.full_name, u.phone
             FROM technicians t INNER JOIN users u ON u.id = t.user_id
             WHERE t.status <> 'off' AND u.status = 1 AND u.role = 'technician'
             ORDER BY u.full_name"
        );
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getForFilter()
    {
        $statement = $this->conn->prepare(
            "SELECT t.id, u.full_name FROM technicians t
             INNER JOIN users u ON u.id = t.user_id
             WHERE u.role = 'technician' ORDER BY u.full_name"
        );
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}

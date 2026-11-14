<?php
require_once __DIR__ . '/../../config/database.php';

class DeviceModel
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

    public function countByCustomer($customerId)
    {
        $statement = $this->conn->prepare(
            'SELECT COUNT(*) FROM devices
             INNER JOIN service_categories ON service_categories.id = devices.category_id
             WHERE devices.customer_id = :customer_id'
        );
        $statement->execute([':customer_id' => (int) $customerId]);
        return (int) $statement->fetchColumn();
    }

    public function getByCustomer($customerId, $limit = null, $offset = 0)
    {
        $statement = $this->conn->prepare(
            'SELECT d.id, d.category_id, d.name, d.brand, d.model, d.note, c.name AS category_name
             FROM devices d
             INNER JOIN service_categories c ON c.id = d.category_id
             WHERE d.customer_id = :customer_id ORDER BY d.name, d.id' .
            ($limit !== null ? ' LIMIT :limit OFFSET :offset' : '')
        );
        $statement->bindValue(':customer_id', (int) $customerId, PDO::PARAM_INT);
        if ($limit !== null) {
            $statement->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
            $statement->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        }
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($customerId, $data)
    {
        $statement = $this->conn->prepare(
            'INSERT INTO devices (customer_id, category_id, name, brand, model, note)
             SELECT :customer_id, c.id, :name, :brand, :model, :note
             FROM service_categories c WHERE c.id = :category_id AND c.status = 1'
        );
        $statement->execute([
            ':customer_id' => (int) $customerId,
            ':category_id' => (int) $data['category_id'],
            ':name' => $data['name'],
            ':brand' => $data['brand'] !== '' ? $data['brand'] : null,
            ':model' => $data['model'] !== '' ? $data['model'] : null,
            ':note' => $data['note'] !== '' ? $data['note'] : null,
        ]);
        return $statement->rowCount() === 1;
    }

    public function update($deviceId, $customerId, $data)
    {
        $statement = $this->conn->prepare(
            'UPDATE devices d
             INNER JOIN service_categories c ON c.id = :category_id AND c.status = 1
             SET d.category_id = c.id, d.name = :name, d.brand = :brand, d.model = :model, d.note = :note
             WHERE d.id = :id AND d.customer_id = :customer_id'
        );
        $statement->execute([
            ':category_id' => (int) $data['category_id'],
            ':name' => $data['name'],
            ':brand' => $data['brand'] !== '' ? $data['brand'] : null,
            ':model' => $data['model'] !== '' ? $data['model'] : null,
            ':note' => $data['note'] !== '' ? $data['note'] : null,
            ':id' => (int) $deviceId,
            ':customer_id' => (int) $customerId,
        ]);
        if ($statement->rowCount() > 0) {
            return true;
        }

        $check = $this->conn->prepare(
            'SELECT d.id FROM devices d
             INNER JOIN service_categories c ON c.id = :category_id AND c.status = 1
             WHERE d.id = :id AND d.customer_id = :customer_id'
        );
        $check->execute([
            ':category_id' => (int) $data['category_id'],
            ':id' => (int) $deviceId,
            ':customer_id' => (int) $customerId,
        ]);
        return $check->fetchColumn() !== false;
    }

    public function delete($deviceId, $customerId)
    {
        $this->conn->beginTransaction();
        try {
            $check = $this->conn->prepare(
                'SELECT id FROM devices WHERE id = :id AND customer_id = :customer_id FOR UPDATE'
            );
            $check->execute([
                ':id' => (int) $deviceId,
                ':customer_id' => (int) $customerId,
            ]);
            if ($check->fetchColumn() === false) {
                $this->conn->rollBack();
                return 'missing';
            }

            $requestCheck = $this->conn->prepare('SELECT COUNT(*) FROM requests WHERE device_id = :device_id');
            $requestCheck->execute([':device_id' => (int) $deviceId]);
            if ((int) $requestCheck->fetchColumn() > 0) {
                $this->conn->rollBack();
                return 'in_use';
            }

            $delete = $this->conn->prepare(
                'DELETE FROM devices WHERE id = :id AND customer_id = :customer_id'
            );
            $delete->execute([
                ':id' => (int) $deviceId,
                ':customer_id' => (int) $customerId,
            ]);
            $this->conn->commit();
            return 'deleted';
        } catch (Throwable $exception) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $exception;
        }
    }
}

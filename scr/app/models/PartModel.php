<?php
require_once __DIR__ . '/../../config/database.php';

class PartModel
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

    private function filters($search, $lowStock)
    {
        $conditions = ['1 = 1'];
        $parameters = [];
        if ($search !== '') {
            $conditions[] = '(name LIKE :name_search OR sku LIKE :sku_search)';
            $parameters[':name_search'] = '%' . $search . '%';
            $parameters[':sku_search'] = '%' . $search . '%';
        }
        if ($lowStock) {
            $conditions[] = 'stock_qty <= 5';
        }
        return [$conditions, $parameters];
    }

    public function countAll($search, $lowStock)
    {
        list($conditions, $parameters) = $this->filters($search, $lowStock);
        $statement = $this->conn->prepare(
            'SELECT COUNT(*) FROM parts WHERE ' . implode(' AND ', $conditions)
        );
        $statement->execute($parameters);
        return (int) $statement->fetchColumn();
    }

    public function getAll($search, $lowStock, $limit, $offset)
    {
        list($conditions, $parameters) = $this->filters($search, $lowStock);
        $statement = $this->conn->prepare(
            'SELECT id, name, sku, unit, price, stock_qty, status
             FROM parts WHERE ' . implode(' AND ', $conditions) . '
             ORDER BY (stock_qty <= 5) DESC, name LIMIT :limit OFFSET :offset'
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
            'SELECT id, name, sku, unit, price, stock_qty, status FROM parts WHERE id = :id'
        );
        $statement->execute([':id' => (int) $id]);
        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        if ($this->skuExists($data['sku'])) {
            return 'duplicate';
        }
        $statement = $this->conn->prepare(
            'INSERT INTO parts (name, sku, unit, price, stock_qty, status)
             VALUES (:name, :sku, :unit, :price, :stock_qty, :status)'
        );
        try {
            $statement->execute([
                ':name' => $data['name'],
                ':sku' => $data['sku'],
                ':unit' => $data['unit'],
                ':price' => $data['price'],
                ':stock_qty' => (int) $data['stock_qty'],
                ':status' => (int) $data['status'],
            ]);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                return 'duplicate';
            }
            throw $exception;
        }
        return 'created';
    }

    public function update($id, $data)
    {
        if ($this->skuExists($data['sku'], (int) $id)) {
            return 'duplicate';
        }
        $statement = $this->conn->prepare(
            'UPDATE parts SET name = :name, sku = :sku, unit = :unit, price = :price,
             stock_qty = :stock_qty, status = :status WHERE id = :id'
        );
        try {
            $statement->execute([
                ':name' => $data['name'],
                ':sku' => $data['sku'],
                ':unit' => $data['unit'],
                ':price' => $data['price'],
                ':stock_qty' => (int) $data['stock_qty'],
                ':status' => (int) $data['status'],
                ':id' => (int) $id,
            ]);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                return 'duplicate';
            }
            throw $exception;
        }
        return $this->find($id) ? 'updated' : 'missing';
    }

    public function setStatus($id, $status)
    {
        $statement = $this->conn->prepare('UPDATE parts SET status = :status WHERE id = :id');
        $statement->execute([':status' => (int) $status, ':id' => (int) $id]);
        return $statement->rowCount() > 0 || $this->find($id) !== false;
    }

    public function addStock($id, $quantity)
    {
        $statement = $this->conn->prepare(
            'UPDATE parts SET stock_qty = stock_qty + :quantity
             WHERE id = :id AND stock_qty <= 4294967295 - :max_quantity'
        );
        $statement->execute([
            ':quantity' => (int) $quantity,
            ':id' => (int) $id,
            ':max_quantity' => (int) $quantity,
        ]);
        if ($statement->rowCount() > 0) {
            return 'updated';
        }
        return $this->find($id) ? 'overflow' : 'missing';
    }

    private function skuExists($sku, $excludeId = 0)
    {
        $statement = $this->conn->prepare(
            'SELECT id FROM parts WHERE sku = :sku AND id <> :id LIMIT 1'
        );
        $statement->execute([':sku' => $sku, ':id' => (int) $excludeId]);
        return $statement->fetchColumn() !== false;
    }

    public function delete($id)
    {
        $used = $this->conn->prepare(
            'SELECT COUNT(*) FROM report_parts WHERE part_id = :part_id'
        );
        $used->execute([':part_id' => (int) $id]);
        if ((int) $used->fetchColumn() > 0) {
            return 'used';
        }
        $statement = $this->conn->prepare('DELETE FROM parts WHERE id = :id');
        $statement->execute([':id' => (int) $id]);
        return $statement->rowCount() > 0 ? 'deleted' : 'missing';
    }
}

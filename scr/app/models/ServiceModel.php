<?php
require_once __DIR__ . '/../../config/database.php';

class ServiceModel
{
    private $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getAll($categoryId, $search, $limit, $offset)
    {
        $conditions = [];
        $parameters = [];
        if ($categoryId > 0) {
            $conditions[] = 's.category_id = :category_id';
            $parameters[':category_id'] = $categoryId;
        }
        if ($search !== '') {
            $conditions[] = 's.name LIKE :search';
            $parameters[':search'] = '%' . $search . '%';
        }

        $where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
        $statement = $this->conn->prepare(
            'SELECT s.id, s.name, s.category_id, s.description, s.base_price, s.est_minutes, s.status, c.name AS category_name
             FROM services s LEFT JOIN service_categories c ON c.id = s.category_id' . $where . '
             ORDER BY s.id DESC LIMIT :limit OFFSET :offset'
        );
        foreach ($parameters as $key => $value) {
            $statement->bindValue($key, $value);
        }
        $statement->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countAll($categoryId, $search)
    {
        $conditions = [];
        $parameters = [];
        if ($categoryId > 0) {
            $conditions[] = 'category_id = :category_id';
            $parameters[':category_id'] = $categoryId;
        }
        if ($search !== '') {
            $conditions[] = 'name LIKE :search';
            $parameters[':search'] = '%' . $search . '%';
        }
        $where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
        $statement = $this->conn->prepare('SELECT COUNT(*) FROM services' . $where);
        $statement->execute($parameters);
        return (int) $statement->fetchColumn();
    }

    public function countPublicList($categoryId = 0, $search = '')
    {
        $conditions = ['s.status = 1', 'c.status = 1'];
        $parameters = [];
        if ((int) $categoryId > 0) {
            $conditions[] = 's.category_id = :category_id';
            $parameters[':category_id'] = (int) $categoryId;
        }
        if ($search !== '') {
            $conditions[] = 's.name LIKE :search';
            $parameters[':search'] = '%' . $search . '%';
        }
        $statement = $this->conn->prepare(
            'SELECT COUNT(*) FROM services s INNER JOIN service_categories c ON c.id = s.category_id
             WHERE ' . implode(' AND ', $conditions)
        );
        $statement->execute($parameters);
        return (int) $statement->fetchColumn();
    }

    public function getPublicList($categoryId = 0, $search = '', $limit = null, $offset = 0)
    {
        $conditions = ['s.status = 1', 'c.status = 1'];
        $parameters = [];
        if ((int) $categoryId > 0) {
            $conditions[] = 's.category_id = :category_id';
            $parameters[':category_id'] = (int) $categoryId;
        }
        if ($search !== '') {
            $conditions[] = 's.name LIKE :search';
            $parameters[':search'] = '%' . $search . '%';
        }
        $statement = $this->conn->prepare(
            'SELECT s.id, s.category_id, s.name, s.description, s.base_price, s.est_minutes, c.name AS category_name
             FROM services s
             INNER JOIN service_categories c ON c.id = s.category_id
             WHERE ' . implode(' AND ', $conditions) . '
             ORDER BY c.name, s.name' . ($limit !== null ? ' LIMIT :limit OFFSET :offset' : '')
        );
        foreach ($parameters as $key => $value) {
            $statement->bindValue($key, $value);
        }
        if ($limit !== null) {
            $statement->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
            $statement->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        }
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findActive($id)
    {
        $statement = $this->conn->prepare(
            'SELECT id, category_id, name, description, base_price, est_minutes
             FROM services
             WHERE id = :id AND status = 1
               AND category_id IN (SELECT id FROM service_categories WHERE status = 1)'
        );
        $statement->execute([':id' => (int) $id]);
        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    public function find($id)
    {
        $statement = $this->conn->prepare('SELECT id, name, category_id, description, base_price, est_minutes, status FROM services WHERE id = :id');
        $statement->execute([':id' => (int) $id]);
        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $statement = $this->conn->prepare(
            'INSERT INTO services (name, category_id, description, base_price, est_minutes, status)
             VALUES (:name, :category_id, :description, :base_price, :est_minutes, 1)'
        );
        return $statement->execute([
            ':name' => $data['name'],
            ':category_id' => $data['category_id'],
            ':description' => $data['description'],
            ':base_price' => $data['base_price'],
            ':est_minutes' => $data['est_minutes'],
        ]);
    }

    public function update($id, $data)
    {
        $statement = $this->conn->prepare(
            'UPDATE services SET name = :name, category_id = :category_id, description = :description,
             base_price = :base_price, est_minutes = :est_minutes WHERE id = :id'
        );
        return $statement->execute([
            ':id' => (int) $id,
            ':name' => $data['name'],
            ':category_id' => $data['category_id'],
            ':description' => $data['description'],
            ':base_price' => $data['base_price'],
            ':est_minutes' => $data['est_minutes'],
        ]);
    }

    public function toggle($id)
    {
        $statement = $this->conn->prepare('UPDATE services SET status = CASE WHEN status = 1 THEN 0 ELSE 1 END WHERE id = :id');
        return $statement->execute([':id' => (int) $id]);
    }

    public function delete($id)
    {
        $check = $this->conn->prepare('SELECT COUNT(*) FROM requests WHERE service_id = :id');
        $check->execute([':id' => (int) $id]);
        if ((int) $check->fetchColumn() > 0) {
            return false;
        }

        $statement = $this->conn->prepare('DELETE FROM services WHERE id = :id');
        return $statement->execute([':id' => (int) $id]);
    }
}

<?php
require_once __DIR__ . '/../../config/database.php';

class CategoryModel
{
    private $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getActive($limit = null)
    {
        if (!($this->conn instanceof PDO)) {
            return [];
        }

        $sql = 'SELECT id, name, description, status FROM service_categories WHERE status = 1 ORDER BY name';
        if ($limit !== null) {
            $sql .= ' LIMIT :limit';
        }
        $statement = $this->conn->prepare($sql);
        if ($limit !== null) {
            $statement->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        }
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countAll()
    {
        $statement = $this->conn->prepare('SELECT COUNT(*) FROM service_categories');
        $statement->execute();
        return (int) $statement->fetchColumn();
    }

    public function getAll($limit = null, $offset = 0)
    {
        $statement = $this->conn->prepare(
            'SELECT id, name, description, status FROM service_categories ORDER BY id DESC' .
            ($limit !== null ? ' LIMIT :limit OFFSET :offset' : '')
        );
        if ($limit !== null) {
            $statement->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
            $statement->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        }
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find($id)
    {
        $statement = $this->conn->prepare('SELECT id, name, description, status FROM service_categories WHERE id = :id');
        $statement->execute([':id' => (int) $id]);
        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    public function create($name, $description)
    {
        $statement = $this->conn->prepare('INSERT INTO service_categories (name, description, status) VALUES (:name, :description, 1)');
        return $statement->execute([':name' => $name, ':description' => $description]);
    }

    public function update($id, $name, $description)
    {
        $statement = $this->conn->prepare('UPDATE service_categories SET name = :name, description = :description WHERE id = :id');
        return $statement->execute([':name' => $name, ':description' => $description, ':id' => (int) $id]);
    }

    public function toggle($id)
    {
        $statement = $this->conn->prepare('UPDATE service_categories SET status = CASE WHEN status = 1 THEN 0 ELSE 1 END WHERE id = :id');
        return $statement->execute([':id' => (int) $id]);
    }

    public function delete($id)
    {
        $check = $this->conn->prepare('SELECT COUNT(*) FROM services WHERE category_id = :id');
        $check->execute([':id' => (int) $id]);
        if ((int) $check->fetchColumn() > 0) {
            return false;
        }

        $statement = $this->conn->prepare('DELETE FROM service_categories WHERE id = :id');
        return $statement->execute([':id' => (int) $id]);
    }
}

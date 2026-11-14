<?php
require_once __DIR__ . '/../../config/database.php';

class HomeModel
{
    private $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getStatistics()
    {
        $statement = $this->conn->prepare(
            'SELECT
                (SELECT COUNT(*) FROM service_categories WHERE status = 1) AS category_count,
                (SELECT COUNT(*) FROM services
                 INNER JOIN service_categories ON service_categories.id = services.category_id
                 WHERE services.status = 1 AND service_categories.status = 1) AS service_count,
                (SELECT COUNT(*) FROM requests WHERE status = :completed_status) AS completed_count,
                (SELECT AVG(rating) FROM reviews) AS average_rating'
        );
        $statement->execute([':completed_status' => 'completed']);
        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    public function getCategories()
    {
        $statement = $this->conn->prepare(
            'SELECT service_categories.id, service_categories.name, service_categories.description,
                    COUNT(services.id) AS service_count
             FROM service_categories
             LEFT JOIN services ON services.category_id = service_categories.id AND services.status = 1
             WHERE service_categories.status = 1
             GROUP BY service_categories.id, service_categories.name, service_categories.description
             ORDER BY service_categories.name'
        );
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPopularServices()
    {
        $statement = $this->conn->prepare(
            'SELECT services.id, services.category_id, services.name, services.description, services.base_price,
                    service_categories.name AS category_name, COUNT(requests.id) AS request_count
             FROM services
             INNER JOIN service_categories ON service_categories.id = services.category_id
             LEFT JOIN requests ON requests.service_id = services.id
             WHERE services.status = 1 AND service_categories.status = 1
             GROUP BY services.id, services.category_id, services.name, services.description,
                      services.base_price, service_categories.name
             ORDER BY request_count DESC, services.name
             LIMIT 6'
        );
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRecentReviews()
    {
        $statement = $this->conn->prepare(
            'SELECT reviews.rating, reviews.comment, reviews.created_at, users.full_name AS customer_name
             FROM reviews
             INNER JOIN users ON users.id = reviews.customer_id
             ORDER BY reviews.created_at DESC, reviews.id DESC
             LIMIT 3'
        );
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}

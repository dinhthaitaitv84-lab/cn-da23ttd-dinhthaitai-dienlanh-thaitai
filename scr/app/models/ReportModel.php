<?php
require_once __DIR__ . '/../../config/database.php';

class ReportModel
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

    private function requestRange($start, $end, $alias = 'requests')
    {
        return [
            $alias . '.created_at >= :request_start AND ' . $alias . '.created_at < :request_end',
            [':request_start' => $start . ' 00:00:00', ':request_end' => $this->exclusiveEnd($end)],
        ];
    }

    private function revenueRange($start, $end, $alias = 'requests')
    {
        return [
            $alias . ".status = 'completed' AND " . $alias . ".updated_at >= :revenue_start
             AND " . $alias . '.updated_at < :revenue_end',
            [':revenue_start' => $start . ' 00:00:00', ':revenue_end' => $this->exclusiveEnd($end)],
        ];
    }

    private function exclusiveEnd($end)
    {
        return (new DateTimeImmutable($end))->modify('+1 day')->format('Y-m-d 00:00:00');
    }

    public function overview($start, $end)
    {
        list($revenueCondition, $revenueParams) = $this->revenueRange($start, $end);
        $revenue = $this->conn->prepare(
            "SELECT COALESCE(SUM(accepted_quotes.total_amount), 0) AS revenue, COUNT(requests.id) AS completed
             FROM requests
             LEFT JOIN (
                SELECT request_id, SUM(total_amount) AS total_amount
                FROM quotes WHERE status = 'accepted' GROUP BY request_id
             ) accepted_quotes ON accepted_quotes.request_id = requests.id
             WHERE " . $revenueCondition
        );
        $revenue->execute($revenueParams);
        $summary = $revenue->fetch(PDO::FETCH_ASSOC);

        $average = (int) $summary['completed'] > 0
            ? (float) $summary['revenue'] / (int) $summary['completed']
            : 0;
        $review = $this->conn->prepare(
            'SELECT COALESCE(AVG(reviews.rating), 0)
             FROM reviews
             INNER JOIN requests ON requests.id = reviews.request_id
             WHERE requests.status = :status
               AND requests.updated_at >= :review_start AND requests.updated_at < :review_end'
        );
        $review->execute([
            ':status' => 'completed',
            ':review_start' => $start . ' 00:00:00',
            ':review_end' => $this->exclusiveEnd($end),
        ]);
        $summary['average_order'] = $average;
        $summary['average_rating'] = (float) $review->fetchColumn();
        $summary['revenue_series'] = $this->revenueSeries($start, $end);
        return $summary;
    }

    public function revenueSeries($start, $end)
    {
        $startDate = new DateTimeImmutable($start);
        $endDate = new DateTimeImmutable($end);
        $monthly = $startDate->diff($endDate)->days > 62;
        if ($monthly) {
            $statement = $this->conn->prepare(
                "SELECT DATE_FORMAT(requests.updated_at, '%Y-%m-01') AS period,
                        COALESCE(SUM(accepted_quotes.total_amount), 0) AS revenue
                 FROM requests
                 INNER JOIN (
                    SELECT request_id, SUM(total_amount) AS total_amount
                    FROM quotes WHERE status = 'accepted' GROUP BY request_id
                 ) accepted_quotes ON accepted_quotes.request_id = requests.id
                 WHERE requests.status = 'completed'
                   AND requests.updated_at >= :start_at AND requests.updated_at < :end_at
                 GROUP BY DATE_FORMAT(requests.updated_at, '%Y-%m-01')
                 ORDER BY period"
            );
        } else {
            $statement = $this->conn->prepare(
                "SELECT DATE(requests.updated_at) AS period,
                        COALESCE(SUM(accepted_quotes.total_amount), 0) AS revenue
                 FROM requests
                 INNER JOIN (
                    SELECT request_id, SUM(total_amount) AS total_amount
                    FROM quotes WHERE status = 'accepted' GROUP BY request_id
                 ) accepted_quotes ON accepted_quotes.request_id = requests.id
                 WHERE requests.status = 'completed'
                   AND requests.updated_at >= :start_at AND requests.updated_at < :end_at
                 GROUP BY DATE(requests.updated_at)
                 ORDER BY period"
            );
        }
        $statement->execute([
            ':start_at' => $start . ' 00:00:00',
            ':end_at' => $this->exclusiveEnd($end),
        ]);
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        $indexed = [];
        foreach ($rows as $row) {
            $indexed[$row['period']] = (float) $row['revenue'];
        }
        $series = [];
        if ($monthly) {
            $period = $startDate->modify('first day of this month');
            $last = $endDate->modify('first day of this month');
            while ($period <= $last) {
                $key = $period->format('Y-m-01');
                $series[] = ['label' => $period->format('m/Y'), 'value' => $indexed[$key] ?? 0];
                $period = $period->modify('+1 month');
            }
        } else {
            $period = $startDate;
            while ($period <= $endDate) {
                $key = $period->format('Y-m-d');
                $series[] = ['label' => $period->format('d/m'), 'value' => $indexed[$key] ?? 0];
                $period = $period->modify('+1 day');
            }
        }
        return ['monthly' => $monthly, 'items' => $series];
    }

    public function countServiceRows()
    {
        $statement = $this->conn->prepare(
            'SELECT COUNT(*) FROM services
             INNER JOIN service_categories ON service_categories.id = services.category_id'
        );
        $statement->execute();
        return (int) $statement->fetchColumn();
    }

    public function serviceRows($start, $end, $limit = null, $offset = 0)
    {
        list($requestCondition, $requestParams) = $this->requestRange($start, $end);
        $statement = $this->conn->prepare(
            "SELECT service_categories.id AS category_id, service_categories.name AS category_name,
                    services.id AS service_id, services.name AS service_name,
                    COUNT(requests.id) AS request_count,
                    SUM(CASE WHEN requests.status = 'completed' THEN 1 ELSE 0 END) AS completed_count,
                    COALESCE(SUM(CASE WHEN requests.status = 'completed'
                        AND requests.updated_at >= :revenue_start AND requests.updated_at < :revenue_end
                        THEN (SELECT COALESCE(SUM(service_quotes.total_amount), 0)
                              FROM quotes service_quotes
                              WHERE service_quotes.request_id = requests.id
                                AND service_quotes.status = 'accepted')
                        ELSE 0 END), 0) AS revenue
             FROM services
             INNER JOIN service_categories ON service_categories.id = services.category_id
             LEFT JOIN requests ON requests.service_id = services.id AND " . $requestCondition . "
             GROUP BY service_categories.id, service_categories.name, services.id, services.name
             ORDER BY service_categories.name, services.name" .
            ($limit !== null ? ' LIMIT :limit OFFSET :offset' : '')
        );
        $parameters = $requestParams + [
            ':revenue_start' => $start . ' 00:00:00',
            ':revenue_end' => $this->exclusiveEnd($end),
        ];
        if ($limit !== null) {
            $statement->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
            $statement->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
            foreach ($parameters as $key => $value) {
                $statement->bindValue($key, $value);
            }
            $statement->execute();
        } else {
            $statement->execute($parameters);
        }
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $row['request_count'] = (int) $row['request_count'];
            $row['completed_count'] = (int) $row['completed_count'];
            $row['revenue'] = (float) $row['revenue'];
            $row['completion_rate'] = $row['request_count'] > 0
                ? 100 * $row['completed_count'] / $row['request_count']
                : 0;
        }
        unset($row);
        return $rows;
    }

    public function serviceCategories($rows)
    {
        $categories = [];
        foreach ($rows as $row) {
            if (!isset($categories[$row['category_id']])) {
                $categories[$row['category_id']] = [
                    'label' => $row['category_name'],
                    'value' => 0,
                ];
            }
            $categories[$row['category_id']]['value'] += $row['revenue'];
        }
        return array_values($categories);
    }

    public function countTechnicianRows()
    {
        $statement = $this->conn->prepare(
            'SELECT COUNT(*) FROM technicians INNER JOIN users ON users.id = technicians.user_id'
        );
        $statement->execute();
        return (int) $statement->fetchColumn();
    }

    public function technicianRows($start, $end, $limit = null, $offset = 0)
    {
        list($requestCondition) = $this->requestRange($start, $end);
        $statement = $this->conn->prepare(
            "SELECT technicians.id AS technician_id, users.full_name AS technician_name,
                    COUNT(requests.id) AS assigned_count,
                    SUM(CASE WHEN requests.status = 'completed' THEN 1 ELSE 0 END) AS completed_count,
                    COALESCE(SUM(CASE WHEN requests.status = 'completed'
                        AND requests.updated_at >= :revenue_start AND requests.updated_at < :revenue_end
                        THEN (SELECT COALESCE(SUM(technician_quotes.total_amount), 0)
                              FROM quotes technician_quotes
                              WHERE technician_quotes.request_id = requests.id
                                AND technician_quotes.status = 'accepted')
                        ELSE 0 END), 0) AS revenue,
                    COALESCE(AVG(CASE WHEN requests.status = 'completed'
                        THEN TIMESTAMPDIFF(MINUTE, requests.created_at, requests.updated_at) / 60 END), 0) AS average_hours,
                    (SELECT AVG(reviews.rating)
                     FROM reviews
                     INNER JOIN requests reviewed_requests ON reviewed_requests.id = reviews.request_id
                     WHERE reviewed_requests.technician_id = technicians.id
                       AND reviewed_requests.status = 'completed'
                       AND reviewed_requests.updated_at >= :review_start
                       AND reviewed_requests.updated_at < :review_end) AS average_rating
             FROM technicians
             INNER JOIN users ON users.id = technicians.user_id
             LEFT JOIN requests ON requests.technician_id = technicians.id AND " . $requestCondition . "
             GROUP BY technicians.id, users.full_name
             ORDER BY completed_count DESC, revenue DESC, users.full_name" .
            ($limit !== null ? ' LIMIT :limit OFFSET :offset' : '')
        );
        $parameters = [
            ':request_start' => $start . ' 00:00:00',
            ':request_end' => $this->exclusiveEnd($end),
            ':revenue_start' => $start . ' 00:00:00',
            ':revenue_end' => $this->exclusiveEnd($end),
            ':review_start' => $start . ' 00:00:00',
            ':review_end' => $this->exclusiveEnd($end),
        ];
        if ($limit !== null) {
            $statement->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
            $statement->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
            foreach ($parameters as $key => $value) {
                $statement->bindValue($key, $value);
            }
            $statement->execute();
        } else {
            $statement->execute($parameters);
        }
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function statusRows($start, $end)
    {
        list($condition, $params) = $this->requestRange($start, $end);
        $statement = $this->conn->prepare(
            'SELECT requests.status, COUNT(*) AS total FROM requests
             WHERE ' . $condition . ' GROUP BY requests.status'
        );
        $statement->execute($params);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function priorityRows($start, $end)
    {
        list($condition, $params) = $this->requestRange($start, $end);
        $statement = $this->conn->prepare(
            'SELECT requests.priority, COUNT(*) AS total FROM requests
             WHERE ' . $condition . ' GROUP BY requests.priority'
        );
        $statement->execute($params);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countLowStockParts()
    {
        $statement = $this->conn->prepare('SELECT COUNT(*) FROM parts WHERE stock_qty <= 5 AND status = 1');
        $statement->execute();
        return (int) $statement->fetchColumn();
    }

    public function partRows($start, $end, $limit = null, $offset = 0)
    {
        $statement = $this->conn->prepare(
            "SELECT p.id, p.name, p.sku, p.unit,
                    SUM(rp.qty) AS used_qty,
                    COUNT(DISTINCT r.id) AS request_count
             FROM report_parts rp
             INNER JOIN repair_reports rr ON rp.report_id = rr.id
             INNER JOIN requests r ON rr.request_id = r.id
             INNER JOIN parts p ON rp.part_id = p.id
             WHERE r.status = 'completed'
               AND r.updated_at >= :request_start AND r.updated_at < :request_end
             GROUP BY p.id, p.name, p.sku, p.unit
             ORDER BY used_qty DESC
             LIMIT 5"
        );
        $statement->execute([
            ':request_start' => $start . ' 00:00:00',
            ':request_end' => $this->exclusiveEnd($end),
        ]);
        $topParts = $statement->fetchAll(PDO::FETCH_ASSOC);
        $stock = $this->conn->prepare(
            'SELECT id, name, sku, unit, stock_qty, price
             FROM parts WHERE stock_qty <= 5 AND status = 1 ORDER BY stock_qty, name' .
            ($limit !== null ? ' LIMIT :limit OFFSET :offset' : '')
        );
        if ($limit !== null) {
            $stock->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
            $stock->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        }
        $stock->execute();
        return [
            'top' => $topParts,
            'low_stock' => $stock->fetchAll(PDO::FETCH_ASSOC),
            'low_stock_total' => $this->countLowStockParts(),
        ];
    }

    public function dashboardRevenue($start, $end)
    {
        return $this->revenueSeries($start, $end);
    }

    public function topTechnicians($start, $end)
    {
        $statement = $this->conn->prepare(
            "SELECT technicians.id, users.full_name,
                    COUNT(requests.id) AS completed_count,
                    COALESCE(SUM(accepted_quotes.total_amount), 0) AS revenue
             FROM technicians
             INNER JOIN users ON users.id = technicians.user_id
             LEFT JOIN requests ON requests.technician_id = technicians.id
                AND requests.status = 'completed'
                AND requests.updated_at >= :start_at AND requests.updated_at < :end_at
             LEFT JOIN (
                SELECT request_id, SUM(total_amount) AS total_amount
                FROM quotes WHERE status = 'accepted' GROUP BY request_id
             ) accepted_quotes ON accepted_quotes.request_id = requests.id
             GROUP BY technicians.id, users.full_name
             ORDER BY completed_count DESC, revenue DESC, users.full_name LIMIT 3"
        );
        $statement->execute([
            ':start_at' => $start . ' 00:00:00',
            ':end_at' => $this->exclusiveEnd($end),
        ]);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}

<?php
// app/Models/Analytics.php — strict MVC Model
class Analytics extends Model
{
    // Helper to generate SQL date conditions safely
    private function getDateCondition($filter, $column = 'o.order_date') 
    {
        switch ($filter) {
            case 'weekly':
                return "AND $column >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
            case 'monthly':
                return "AND $column >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
            case 'yearly':
                return "AND $column >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            default:
                return ""; // All time
        }
    }

    public function getTopProducts($limit = 5, $filter = 'all')
    {
        $dateCondition = $this->getDateCondition($filter, 'o.order_date');
        
        return $this->conn->query("
            SELECT p.name, SUM(oi.quantity) as total_sold, SUM(oi.quantity * oi.unit_price) as total_revenue
            FROM order_item oi
            JOIN product p ON oi.product_id = p.product_id
            JOIN `order` o ON oi.order_id = o.order_id
            WHERE o.order_status = 'delivered' $dateCondition
            GROUP BY p.product_id
            ORDER BY total_sold DESC
            LIMIT $limit
        ");
    }

    public function getTopDrivers($limit = 5, $filter = 'all')
    {
        // The driver_id is in the `delivery` table, so we join delivery to order to filter by date, then to driver.
        $dateCondition = $this->getDateCondition($filter, 'o.order_date');
        
        return $this->conn->query("
            SELECT dr.name, COUNT(d.delivery_id) as total_deliveries
            FROM delivery d
            JOIN driver dr ON d.driver_id = dr.driver_id
            JOIN `order` o ON d.order_id = o.order_id
            WHERE o.order_status = 'delivered' $dateCondition
            GROUP BY dr.driver_id
            ORDER BY total_deliveries DESC
            LIMIT $limit
        ");
    }

    public function getStats($filter = 'all')
    {
        // For the raw order queries, we use 'order_date' without the 'o.' alias
        $dateCondition = $this->getDateCondition($filter, 'order_date');

        return [
            'total_orders'    => (int)$this->conn->query("SELECT COUNT(*) AS c FROM `order` WHERE 1=1 $dateCondition")->fetch_assoc()['c'],
            'delivered'       => (int)$this->conn->query("SELECT COUNT(*) AS c FROM `order` WHERE order_status = 'delivered' $dateCondition")->fetch_assoc()['c'],
            'rejected'        => (int)$this->conn->query("SELECT COUNT(*) AS c FROM `order` WHERE order_status = 'rejected' $dateCondition")->fetch_assoc()['c'],
            'total_earnings'  => (float)($this->conn->query("SELECT SUM(total_amount) AS total FROM `order` WHERE order_status = 'delivered' $dateCondition")->fetch_assoc()['total'] ?? 0),
        ];
    }
}
<?php
// app/Models/Analytics.php — strict MVC Model
class Analytics extends Model
{
    public function getTopProducts($limit = 5)
    {
        return $this->conn->query("
            SELECT p.name, SUM(oi.quantity) as total_sold, SUM(oi.subtotal) as total_revenue
            FROM order_item oi
            JOIN product p ON oi.product_id = p.product_id
            JOIN `order` o ON oi.order_id = o.order_id
            WHERE o.order_status = 'delivered'
            GROUP BY p.product_id
            ORDER BY total_sold DESC
            LIMIT $limit
        ");
    }

    public function getTopDrivers($limit = 5)
    {
        return $this->conn->query("
            SELECT dr.name, COUNT(d.delivery_id) as total_deliveries
            FROM delivery d
            JOIN driver dr ON d.driver_id = dr.driver_id
            JOIN `order` o ON d.order_id = o.order_id
            WHERE o.order_status = 'delivered'
            GROUP BY dr.driver_id
            ORDER BY total_deliveries DESC
            LIMIT $limit
        ");
    }

    public function getStats()
    {
        return [
            'total_orders'    => (int)$this->conn->query("SELECT COUNT(*) AS c FROM `order`")->fetch_assoc()['c'],
            'delivered'       => (int)$this->conn->query("SELECT COUNT(*) AS c FROM `order` WHERE order_status = 'delivered'")->fetch_assoc()['c'],
            'rejected'        => (int)$this->conn->query("SELECT COUNT(*) AS c FROM `order` WHERE order_status = 'rejected'")->fetch_assoc()['c'],
            'total_earnings'  => (float)($this->conn->query("SELECT SUM(total_amount) AS total FROM `order` WHERE order_status = 'delivered'")->fetch_assoc()['total'] ?? 0),
        ];
    }
}
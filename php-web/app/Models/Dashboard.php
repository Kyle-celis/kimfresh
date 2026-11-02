<?php
// app/Models/Dashboard.php — strict MVC Model
class Dashboard extends Model
{
    public function getStats($dateFilter)
    {
        return [
            'pending'          => $this->conn->query("SELECT COUNT(*) AS c FROM `order` WHERE order_status = 'pending' AND $dateFilter")->fetch_assoc()['c'],
            'waiting'          => $this->conn->query("SELECT COUNT(*) AS c FROM `order` o LEFT JOIN delivery d ON o.order_id = d.order_id WHERE o.order_status = 'approved' AND d.delivery_id IS NULL AND $dateFilter")->fetch_assoc()['c'],
            'waitingForPickup' => $this->conn->query("SELECT COUNT(*) AS c FROM `order` WHERE order_status = 'waiting_for_pickup' AND $dateFilter")->fetch_assoc()['c'],
            'active'           => $this->conn->query("SELECT COUNT(*) AS c FROM `order` WHERE order_status = 'in_transit' AND $dateFilter")->fetch_assoc()['c'],
            'delivered'        => $this->conn->query("SELECT COUNT(*) AS c FROM `order` WHERE order_status = 'delivered' AND $dateFilter")->fetch_assoc()['c'],
            'rejected'         => $this->conn->query("SELECT COUNT(*) AS c FROM `order` WHERE order_status = 'rejected' AND $dateFilter")->fetch_assoc()['c'],
            'lowStock'         => $this->conn->query("SELECT COUNT(*) AS c FROM product WHERE status = 'available' AND stock_quantity <= reorder_level")->fetch_assoc()['c'],
            'totalProducts'    => $this->conn->query("SELECT COUNT(*) AS c FROM product WHERE status = 'available'")->fetch_assoc()['c'],
            'availableDrivers' => $this->conn->query("SELECT COUNT(*) AS c FROM driver WHERE status = 'available'")->fetch_assoc()['c'],
            'totalDrivers'     => $this->conn->query("SELECT COUNT(*) AS c FROM driver")->fetch_assoc()['c'],
            'revenue'          => $this->conn->query("SELECT SUM(total_amount) AS s FROM `order` WHERE order_status = 'delivered' AND $dateFilter")->fetch_assoc()['s'] ?? 0,
        ];
    }

    public function getChartData($filterType, $startDate, $endDate, $startOfWeek)
    {
        $labels = [];
        $data = [];

        if ($filterType === 'monthly') {
            for ($i = 1; $i <= 5; $i++) {
                $wStart = date('Y-m-d', strtotime("$startDate +" . (($i - 1) * 7) . " days"));
                if ($wStart > $endDate) break;
                $wEnd = date('Y-m-d', strtotime("$wStart +6 days"));
                $labels[] = "Week $i";
                $data[] = $this->conn->query("SELECT COUNT(*) AS c FROM `order` WHERE DATE(order_date) BETWEEN '$wStart' AND '$wEnd'")->fetch_assoc()['c'];
            }
        } else {
            for ($i = 0; $i < 7; $i++) {
                $date = date('Y-m-d', strtotime("+$i days", $startOfWeek));
                $labels[] = date('D', strtotime($date));
                $data[]   = $this->conn->query("SELECT COUNT(*) AS c FROM `order` WHERE DATE(order_date) = '$date'")->fetch_assoc()['c'];
            }
        }
        return ['labels' => $labels, 'data' => $data];
    }

    public function getActiveDeliveries($dateFilter)
    {
        return $this->conn->query("
            SELECT o.order_id, r.customer_code, r.address, o.order_status, dr.name AS driver_name
            FROM `order` o
            LEFT JOIN retailer r ON o.retailer_id = r.retailer_id
            LEFT JOIN delivery d ON o.order_id = d.order_id
            LEFT JOIN driver dr ON d.driver_id = dr.driver_id
            WHERE o.order_status IN ('approved', 'waiting_for_pickup', 'in_transit') AND $dateFilter
            ORDER BY o.order_date DESC LIMIT 5
        ");
    }

    public function getLowStock()
    {
        return $this->conn->query("
            SELECT name, stock_quantity, reorder_level FROM product
            WHERE status = 'available' AND stock_quantity <= reorder_level
            ORDER BY stock_quantity ASC LIMIT 5
        ");
    }

    public function countOrdersInPeriod($dateFilter)
    {
        return $this->conn->query("SELECT COUNT(*) AS total FROM `order` o WHERE $dateFilter")->fetch_assoc()['total'];
    }

    public function getRecentOrders($dateFilter, $orderBy, $limit, $offset)
    {
        return $this->conn->query("
            SELECT o.order_id, r.name AS retailer_name, o.order_status, o.total_amount, dr.name AS driver_name,
            (SELECT SUM(quantity) FROM order_item WHERE order_id = o.order_id) AS total_items
            FROM `order` o
            LEFT JOIN retailer r ON o.retailer_id = r.retailer_id
            LEFT JOIN delivery d ON o.order_id = d.order_id
            LEFT JOIN driver dr ON d.driver_id = dr.driver_id
            WHERE $dateFilter
            ORDER BY $orderBy
            LIMIT $limit OFFSET $offset
        ");
    }
}

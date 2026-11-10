<?php
// app/Models/Order.php — strict MVC Model
class Order extends Model
{
    // --- Bulk approve one order (returns true if success, false if failed) ---
    public function approveOrder($order_id)
    {
        $chk = $this->conn->prepare("SELECT order_status FROM `order` WHERE order_id = ?");
        $chk->bind_param("i", $order_id);
        $chk->execute();
        $cur = $chk->get_result()->fetch_assoc();
        if (!$cur || $cur['order_status'] !== 'pending') return false;

        $this->conn->begin_transaction();
        try {
            $items = $this->conn->prepare("SELECT product_id, quantity FROM order_item WHERE order_id = ?");
            $items->bind_param("i", $order_id);
            $items->execute();
            $res = $items->get_result();

            $upd = $this->conn->prepare("UPDATE product SET stock_quantity = stock_quantity - ? WHERE product_id = ? AND
             stock_quantity >= ? AND status != 'expired'");
            while ($it = $res->fetch_assoc()) {
                $upd->bind_param("iii", $it['quantity'], $it['product_id'], $it['quantity']);
                $upd->execute();
                if ($upd->affected_rows === 0) throw new Exception("Insufficient stock or product is expired.");
            }

            $stmt = $this->conn->prepare("UPDATE `order` SET order_status = 'approved' WHERE order_id = ?");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $this->conn->commit();
            return true;
        } catch (Throwable $e) {
            $this->conn->rollback();
            return false;
        }
    }

    public function rejectOrder($order_id)
    {
        $stmt = $this->conn->prepare("UPDATE `order` SET order_status = 'rejected' WHERE order_id = ? AND order_status = 'pending'");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    public function getStatusCounts()
    {
        $counts = [
    'all' => 0, 'pending' => 0, 'approved' => 0, 'waiting_for_pickup' => 0,
    'in_transit' => 0, 'delivered' => 0, 'rejected' => 0, 'cancelled' => 0,
]; 
        $res = $this->conn->query("SELECT order_status, COUNT(*) AS c FROM `order` GROUP BY order_status");
        while ($row = $res->fetch_assoc()) {
            $counts[$row['order_status']] = (int)$row['c'];
            $counts['all'] += (int)$row['c'];
        }
        return $counts;
    }

    public function buildFilter($status)
    {
        if ($status === 'all' || $status === '') return "1=1";
        return "o.order_status = '" . $this->conn->real_escape_string($status) . "'";
    }

    public function count($statusWhere)
    {
        return $this->conn->query("SELECT COUNT(*) AS c FROM `order` o WHERE $statusWhere")->fetch_assoc()['c'];
    }

    public function getAll($statusWhere, $orderBy, $limit, $offset)
    {
        return $this->conn->query("
            SELECT o.*, r.name AS retailer_name, r.customer_code,
                   dr.name AS driver_name,
                   (SELECT SUM(quantity) FROM order_item WHERE order_id = o.order_id) AS total_items
            FROM `order` o
            LEFT JOIN retailer r ON o.retailer_id = r.retailer_id
            LEFT JOIN delivery d ON o.order_id = d.order_id
            LEFT JOIN driver dr ON d.driver_id = dr.driver_id
            WHERE $statusWhere
            ORDER BY $orderBy
            LIMIT $limit OFFSET $offset
        ");
    }

    public function getAllForPdf()
    {
        return $this->conn->query("
            SELECT o.order_id, r.name AS retailer_name, o.order_status, o.total_amount, o.order_date,
                   dr.name AS driver_name
            FROM `order` o
            LEFT JOIN retailer r ON o.retailer_id = r.retailer_id
            LEFT JOIN delivery d ON o.order_id = d.order_id
            LEFT JOIN driver dr ON d.driver_id = dr.driver_id
            ORDER BY o.order_date DESC
        ");
    }

    // --- ViewOrder specific methods ---

    public function getById($order_id)
    {
        $stmt = $this->conn->prepare("
            SELECT o.*, r.name as retailer_name, r.customer_code, r.address, r.phone
            FROM `order` o
            LEFT JOIN retailer r ON o.retailer_id = r.retailer_id
            WHERE o.order_id = ?
        ");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getItems($order_id)
    {
        $stmt = $this->conn->prepare("
            SELECT oi.quantity, oi.unit_price, oi.subtotal, p.name AS product_name
            FROM order_item oi
            JOIN product p ON oi.product_id = p.product_id
            WHERE oi.order_id = ?
        ");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function getSensorData($order_id)
    {
        $stmt = $this->conn->prepare("
            SELECT sd.temperature, sd.humidity, sd.reading_timestamp, sd.is_alert
            FROM sensor_data sd
            JOIN delivery d ON sd.delivery_id = d.delivery_id
            WHERE d.order_id = ?
            ORDER BY sd.reading_timestamp DESC
            LIMIT 10
        ");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function getDriverName($order_id)
    {
        $stmt = $this->conn->prepare("
            SELECT dr.name as driver_name
            FROM delivery d
            JOIN driver dr ON d.driver_id = dr.driver_id
            WHERE d.order_id = ?
        ");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ? $row['driver_name'] : null;
    }
}
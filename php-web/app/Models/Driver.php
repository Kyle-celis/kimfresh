<?php
// app/Models/Driver.php — strict MVC Model
class Driver extends Model
{
    public function getStats()
    {
        return [
            'total'     => $this->conn->query("SELECT COUNT(*) AS c FROM driver WHERE status != 'hidden'")->fetch_assoc()['c'],
            'available' => $this->conn->query("SELECT COUNT(*) AS c FROM driver WHERE status = 'available' AND account_status = 'approved'")->fetch_assoc()['c'],
            'on_delivery' => $this->conn->query("SELECT COUNT(*) AS c FROM driver WHERE status = 'on_delivery'")->fetch_assoc()['c'],
        ];
    }

    public function buildWhereClause($filter, $search)
    {
        $sql = " FROM driver d WHERE 1=1 ";

        if ($filter === 'all') {
            $sql .= " AND d.status != 'hidden'";
        } elseif ($filter === 'available') {
            $sql .= " AND (SELECT COUNT(*) FROM delivery dl INNER JOIN `order` o ON o.order_id = dl.order_id WHERE dl.driver_id = d.driver_id AND o.order_status != 'delivered') = 0 AND d.status != 'hidden'";
        } elseif ($filter === 'assigned') {
            $sql .= " AND (SELECT dl.delivery_status FROM delivery dl INNER JOIN `order` o ON o.order_id = dl.order_id WHERE dl.driver_id = d.driver_id AND o.order_status != 'delivered' ORDER BY dl.delivery_id DESC LIMIT 1) = 'assigned'";
        } elseif ($filter === 'on_delivery') {
            $sql .= " AND (SELECT dl.delivery_status FROM delivery dl INNER JOIN `order` o ON o.order_id = dl.order_id WHERE dl.driver_id = d.driver_id AND o.order_status != 'delivered' ORDER BY dl.delivery_id DESC LIMIT 1) IN ('in_transit','picked_up')";
        } elseif ($filter === 'inactive') {
            $sql .= " AND d.status = 'hidden'";
        }

        if (!empty($search)) {
            $safe = $this->conn->real_escape_string($search);
            $sql .= " AND (d.name LIKE '%$safe%' OR d.email LIKE '%$safe%' OR d.license_number LIKE '%$safe%')";
        }
        return $sql;
    }

    public function count($where)
    {
        return $this->conn->query("SELECT COUNT(*) AS total $where")->fetch_assoc()['total'];
    }

    public function getAll($where, $limit, $offset)
    {
        return $this->conn->query("
            SELECT d.*,
                   (SELECT dl.delivery_status FROM delivery dl INNER JOIN `order` o ON o.order_id = dl.order_id WHERE dl.driver_id = d.driver_id AND o.order_status != 'delivered' ORDER BY dl.delivery_id DESC LIMIT 1) AS active_delivery_status,
                   (SELECT COUNT(*) FROM delivery dl INNER JOIN `order` o ON o.order_id = dl.order_id WHERE dl.driver_id = d.driver_id AND o.order_status != 'delivered') AS active_orders
            $where
            ORDER BY d.name ASC
            LIMIT $limit OFFSET $offset
        ");
    }

    public function getUnassignedOrders()
    {
        return $this->conn->query("
            SELECT o.order_id, r.name AS retailer_name
            FROM `order` o
            JOIN retailer r ON o.retailer_id = r.retailer_id
            LEFT JOIN delivery d ON o.order_id = d.order_id
            WHERE o.order_status = 'approved' AND d.delivery_id IS NULL
        ");
    }

    public function getDeliveryHistories()
    {
        $histories = [];
        $res = $this->conn->query("
            SELECT dl.driver_id, o.order_id, o.order_status, o.total_amount, o.order_date, dl.delivery_status, dl.delivery_date AS assigned_at,
                   r.name AS retailer_name,
                   (SELECT SUM(oi.quantity) FROM order_item oi WHERE oi.order_id = o.order_id) AS total_items
            FROM delivery dl
            INNER JOIN `order` o ON o.order_id = dl.order_id
            LEFT  JOIN retailer r ON r.retailer_id = o.retailer_id
            ORDER BY dl.delivery_date DESC, o.order_id DESC
        ");
        if ($res) {
            while ($h = $res->fetch_assoc()) {
                $histories[(int)$h['driver_id']][] = $h;
            }
        }
        return $histories;
    }

    public function setStatus($driver_id, $new_status)
    {
        $stmt = $this->conn->prepare("UPDATE driver SET status = ? WHERE driver_id = ?");
        $stmt->bind_param("si", $new_status, $driver_id);
        $stmt->execute();
        return $stmt->affected_rows;
    }

    public function getAllForPdf()
    {
        return $this->conn->query("SELECT * FROM driver ORDER BY name ASC");
    }
}

<?php
declare(strict_types=1);

class OrderModel {
    private mysqli $db;

    public function __construct(mysqli $db) {
        $this->db = $db;
    }

    public function getAllProducts(): array {
        $result = $this->db->query("SELECT * FROM product ORDER BY product_id DESC");
        $products = [];
        while ($row = $result->fetch_assoc()) { $products[] = $row; }
        return $products;
    }

    public function getProductPriceAndStock(int $id): ?array {
        $stmt = $this->db->prepare("SELECT unit_price, stock_quantity FROM product WHERE product_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function deductStock(int $id, int $quantity): void {
        $stmt = $this->db->prepare("UPDATE product SET stock_quantity = stock_quantity - ? WHERE product_id = ?");
        $stmt->bind_param("ii", $quantity, $id);
        $stmt->execute();
    }

    public function createOrder(int $retailer_id, float $total): int {
        $stmt = $this->db->prepare("INSERT INTO `order` (retailer_id, total_amount, order_status) VALUES (?, ?, 'pending')");
        $stmt->bind_param("id", $retailer_id, $total);
        $stmt->execute();
        return $this->db->insert_id;
    }

    public function createOrderItem(int $order_id, int $product_id, int $qty, float $price, float $subtotal): void {
        $stmt = $this->db->prepare("INSERT INTO order_item (order_id, product_id, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiidd", $order_id, $product_id, $qty, $price, $subtotal);
        $stmt->execute();
    }

    public function getRetailerOrders(int $retailer_id): array {
        $stmt = $this->db->prepare("
            SELECT o.order_id, o.order_status, o.total_amount, o.order_date, 
                   GROUP_CONCAT(CONCAT(oi.quantity, 'x ', p.name) SEPARATOR ', ') as product_name
            FROM `order` o
            LEFT JOIN order_item oi ON o.order_id = oi.order_id
            LEFT JOIN product p ON oi.product_id = p.product_id
            WHERE o.retailer_id = ?
            GROUP BY o.order_id
            ORDER BY o.order_id DESC
        ");
        $stmt->bind_param("i", $retailer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $orders = [];
        while ($row = $result->fetch_assoc()) { $orders[] = $row; }
        return $orders;
    }

    public function getDriverDeliveries(int $driver_id): array {
        $stmt = $this->db->prepare("
            SELECT d.delivery_id, d.order_id, d.delivery_status, o.total_amount, o.order_status,
                   r.name as retailer_name, r.address as retailer_address, r.phone as retailer_phone
            FROM delivery d 
            JOIN `order` o ON d.order_id = o.order_id
            LEFT JOIN retailer r ON o.retailer_id = r.retailer_id
            WHERE d.driver_id = ? 
            ORDER BY d.delivery_id DESC
        ");
        $stmt->bind_param("i", $driver_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $deliveries = [];
        while ($row = $result->fetch_assoc()) { $deliveries[] = $row; }
        return $deliveries;
    }

    public function updateDelivery(int $delivery_id, string $status): int {
        $stmt = $this->db->prepare("UPDATE delivery SET delivery_status = ? WHERE delivery_id = ?");
        $stmt->bind_param("si", $status, $delivery_id);
        $stmt->execute();

        $stmt2 = $this->db->prepare("SELECT order_id FROM delivery WHERE delivery_id = ?");
        $stmt2->bind_param("i", $delivery_id);
        $stmt2->execute();
        $res = $stmt2->get_result()->fetch_assoc();
        return $res ? (int)$res['order_id'] : 0;
    }

    public function updateOrderStatus(int $order_id, string $status): void {
        $stmt = $this->db->prepare("UPDATE `order` SET order_status = ? WHERE order_id = ?");
        $stmt->bind_param("si", $status, $order_id);
        $stmt->execute();
    }

    public function freeDriver(int $delivery_id): void {
        $stmt = $this->db->prepare("UPDATE driver SET status = 'available' WHERE driver_id = (SELECT driver_id FROM delivery WHERE delivery_id = ?)");
        $stmt->bind_param("i", $delivery_id);
        $stmt->execute();
    }
}
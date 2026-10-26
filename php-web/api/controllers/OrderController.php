<?php
// api/controllers/OrderController.php

class OrderController {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function placeOrder($data) {
        $retailer_id = $data['retailer_id'] ?? 0;
        $items = $data['items'] ?? [];

        if (!$retailer_id || empty($items)) {
            return ["success" => false, "message" => "Missing data"];
        }

        $total = 0;
        foreach ($items as $item) {
            $stmt = $this->conn->prepare("SELECT unit_price, stock_quantity FROM product WHERE product_id = ?");
            $stmt->bind_param("i", $item['product_id']);
            $stmt->execute();
            $prod = $stmt->get_result()->fetch_assoc();

            if (!$prod || $prod['stock_quantity'] < $item['quantity']) {
                return ["success" => false, "message" => "Product out of stock."];
            }
            $total += ($prod['unit_price'] * $item['quantity']);
        }

        $stmt = $this->conn->prepare("INSERT INTO `order` (retailer_id, total_amount, order_status) VALUES (?, ?, 'pending')");
        $stmt->bind_param("id", $retailer_id, $total);
        $stmt->execute();
        $order_id = $this->conn->insert_id;

        foreach ($items as $item) {
            $stmt = $this->conn->prepare("SELECT unit_price FROM product WHERE product_id = ?");
            $stmt->bind_param("i", $item['product_id']);
            $stmt->execute();
            $price = $stmt->get_result()->fetch_assoc()['unit_price'];
            $subtotal = $price * $item['quantity'];

            $ins = $this->conn->prepare("INSERT INTO order_item (order_id, product_id, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)");
            $ins->bind_param("iiidd", $order_id, $item['product_id'], $item['quantity'], $price, $subtotal);
            $ins->execute();

            $deduct = $this->conn->prepare("UPDATE product SET stock_quantity = stock_quantity - ? WHERE product_id = ?");
            $deduct->bind_param("ii", $item['quantity'], $item['product_id']);
            $deduct->execute();
        }

        return ["success" => true, "order_id" => $order_id, "message" => "Order placed successfully!"];
    }
}
?>


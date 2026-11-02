<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require __DIR__ . '/config/db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['order_id'])) {
    echo json_encode(["success" => false, "message" => "Missing order_id"]);
    exit;
}

$order_id = (int)$data['order_id'];

// 1. Check the current status
$stmt = $conn->prepare("SELECT order_status FROM `order` WHERE order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $status = $row['order_status'];

    // 2. Only allow cancel if pending or approved
    if ($status === 'pending' || $status === 'approved') {
        
        // Update order to 'rejected' (or 'cancelled' if you prefer)
        $update = $conn->prepare("UPDATE `order` SET order_status = 'cancelled' WHERE order_id = ?");
        $update->bind_param("i", $order_id);
        
        if ($update->execute()) {
            echo json_encode(["success" => true, "message" => "Order cancelled successfully"]);
        } else {
            echo json_encode(["success" => false, "message" => "Database error"]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Cannot cancel an order that is already $status"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Order not found"]);
}

$stmt->close();
$conn->close();
?>
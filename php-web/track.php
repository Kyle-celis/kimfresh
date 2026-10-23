<?php
header('Content-Type: application/json');
require 'db.php';

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid order ID"]);
    exit;
}

// Fetch the current coordinates
$stmt = $conn->prepare("SELECT driver_lat, driver_lng FROM `order` WHERE order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    echo json_encode(["success" => false, "message" => "Order not found"]);
    exit;
}

if ($row['driver_lat'] === NULL || $row['driver_lng'] === NULL) {
    echo json_encode(["success" => false, "message" => "Driver location not available yet"]);
    exit;
}

echo json_encode([
    "success" => true,
    "lat" => (float)$row['driver_lat'],
    "lng" => (float)$row['driver_lng']
]);
?>
<?php
header('Content-Type: application/json');
require 'db.php';

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid order ID"]);
    exit;
}

// Fetch BOTH driver and retailer coordinates
$stmt = $conn->prepare("SELECT driver_lat, driver_lng, retailer_lat, retailer_lng FROM `order` WHERE order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    echo json_encode(["success" => false, "message" => "Order not found"]);
    exit;
}

// Build the response
$response = [
    "success" => true,
    "driver_lat" => $row['driver_lat'] !== NULL ? (float)$row['driver_lat'] : null,
    "driver_lng" => $row['driver_lng'] !== NULL ? (float)$row['driver_lng'] : null,
    "retailer_lat" => $row['retailer_lat'] !== NULL ? (float)$row['retailer_lat'] : null,
    "retailer_lng" => $row['retailer_lng'] !== NULL ? (float)$row['retailer_lng'] : null
];

echo json_encode($response);
$stmt->close();
$conn->close();
?>
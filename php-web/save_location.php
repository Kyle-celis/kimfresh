<?php
header('Content-Type: application/json');
require 'db.php';

// Get JSON data from the Flutter app
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['order_id']) || !isset($data['lat']) || !isset($data['lng'])) {
    echo json_encode(["success" => false, "message" => "Missing data"]);
    exit;
}

$order_id = $data['order_id'];
$lat = $data['lat'];
$lng = $data['lng'];

// Update the coordinates in the order table
$stmt = $conn->prepare("UPDATE `order` SET driver_lat = ?, driver_lng = ? WHERE order_id = ?");
$stmt->bind_param("ddi", $lat, $lng, $order_id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Location updated"]);
} else {
    echo json_encode(["success" => false, "message" => "Database error"]);
}
?>
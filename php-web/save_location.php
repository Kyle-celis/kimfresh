<?php
header('Content-Type: application/json');
require 'db.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['order_id']) || !isset($data['lat']) || !isset($data['lng'])) {
    echo json_encode(["success" => false, "message" => "Missing data"]);
    exit;
}

$order_id = (int)$data['order_id'];
$lat      = (float)$data['lat'];
$lng      = (float)$data['lng'];


if ($order_id <= 0 || $lat < 4.0 || $lat > 21.0 || $lng < 116.0 || $lng > 127.0) {
    echo json_encode(["success" => false, "message" => "Invalid coordinates"]);
    exit;
}

// 1. Log history
$stmt1 = $conn->prepare("INSERT INTO driver_locations (order_id, lat, lng) VALUES (?, ?, ?)");
$stmt1->bind_param("idd", $order_id, $lat, $lng);
if (!$stmt1->execute()) {
    echo json_encode(["success" => false, "message" => "History insert failed"]);
    exit;
}

// 2. Update current position on order (for track.php / dashboard)
$stmt2 = $conn->prepare("UPDATE `order` SET driver_lat = ?, driver_lng = ? WHERE order_id = ?");
$stmt2->bind_param("ddi", $lat, $lng, $order_id);
if (!$stmt2->execute()) {
    echo json_encode(["success" => false, "message" => "Live update failed"]);
    exit;
}

echo json_encode(["success" => true, "message" => "Location updated and logged"]);
?>
<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);

$order_id  = isset($data['order_id']) ? (int)$data['order_id'] : 0;
$driver_id = isset($data['driver_id']) ? (int)$data['driver_id'] : 0;
$lat       = isset($data['lat']) ? (float)$data['lat'] : null;
$lng       = isset($data['lng']) ? (float)$data['lng'] : null;

if ($order_id <= 0 || $lat === null || $lng === null) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO driver_locations (order_id, driver_id, lat, lng) VALUES (?, ?, ?, ?)");
$stmt->bind_param("iidd", $order_id, $driver_id, $lat, $lng);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Location saved']);
} else {
    echo json_encode(['success' => false, 'message' => 'DB insert failed']);
}
$stmt->close();
?>
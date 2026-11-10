<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['order_id']) || !isset($data['lat']) || !isset($data['lng'])) {
    echo json_encode(["success" => false, "message" => "Missing data"]);
    exit;
}

$order_id = (int)$data['order_id'];
$lat = (float)$data['lat'];
$lng = (float)$data['lng'];

$stmt = $conn->prepare("UPDATE `order` SET driver_lat = ?, driver_lng = ? WHERE order_id = ?");
if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Prepare failed: " . $conn->error]);
    exit;
}
$stmt->bind_param("ddi", $lat, $lng, $order_id);

if ($stmt->execute()) {
    // Even if affected_rows == 0, the query succeeded
    echo json_encode(["success" => true, "message" => "Driver location saved"]);
} else {
    echo json_encode(["success" => false, "message" => "Execute failed: " . $stmt->error]);
}
$conn->close();
?>
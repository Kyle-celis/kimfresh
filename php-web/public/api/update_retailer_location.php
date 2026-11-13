<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['retailer_id']) || !isset($data['lat']) || !isset($data['lng'])) {
    echo json_encode(["success" => false, "message" => "Missing data"]);
    exit;
}

$retailer_id = (int)$data['retailer_id'];
$lat = (float)$data['lat'];
$lng = (float)$data['lng'];

$stmt = $conn->prepare("UPDATE retailer SET lat = ?, lng = ? WHERE retailer_id = ?");
$stmt->bind_param("ddi", $lat, $lng, $retailer_id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Store location saved!"]);
} else {
    echo json_encode(["success" => false, "message" => "Database update failed"]);
}
$stmt->close();
$conn->close();
?>

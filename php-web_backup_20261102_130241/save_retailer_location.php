<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require 'db.php';

// Get JSON data from the Retailer App
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['order_id']) || !isset($data['lat']) || !isset($data['lng'])) {
    echo json_encode(["success" => false, "message" => "Missing data"]);
    exit;
}

$order_id = $data['order_id'];
$lat = $data['lat'];
$lng = $data['lng'];

// Update the retailer's coordinates in the order table
$stmt = $conn->prepare("UPDATE `order` SET retailer_lat = ?, retailer_lng = ? WHERE order_id = ?");
$stmt->bind_param("ddi", $lat, $lng, $order_id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Retailer location saved"]);
} else {
    echo json_encode(["success" => false, "message" => "Database error"]);
}

$stmt->close();
$conn->close();
?>
<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

// Accept only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'POST required']);
    exit;
}

$raw = file_get_contents('php://input');
$body = json_decode($raw, true);

if (!is_array($body)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

$deliveryId  = isset($body['delivery_id'])  ? (int)$body['delivery_id'] : 0;
$temperature = isset($body['temperature'])  ? (float)$body['temperature'] : null;
$humidity    = isset($body['humidity'])     ? (float)$body['humidity'] : null;
$isAlert     = !empty($body['is_alert']) ? 1 : 0;

if ($deliveryId <= 0 || $temperature === null || $humidity === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Verify delivery exists (prevents junk rows)
$check = $conn->prepare("SELECT delivery_id FROM delivery WHERE delivery_id = ? LIMIT 1");
$check->bind_param('i', $deliveryId);
$check->execute();
$check->store_result();
if ($check->num_rows === 0) {
    $check->close();
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Delivery not found']);
    exit;
}
$check->close();

// Insert
$stmt = $conn->prepare(
    "INSERT INTO sensor_data (delivery_id, temperature, humidity, is_alert, reading_timestamp)
     VALUES (?, ?, ?, ?, NOW())"
);
$stmt->bind_param('iddi', $deliveryId, $temperature, $humidity, $isAlert);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB insert failed']);
    $stmt->close();
    exit;
}

$stmt->close();

echo json_encode(['success' => true, 'sensor_data_id' => $conn->insert_id]);
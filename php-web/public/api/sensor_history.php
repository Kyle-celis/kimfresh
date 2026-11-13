<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db.php';

// Check if driver_id is provided
$driverId = isset($_GET['driver_id']) ? (int)$_GET['driver_id'] : 0;

if ($driverId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Driver ID required']);
    exit;
}

// Fetch sensor history for all deliveries assigned to this driver
$query = "
    SELECT 
        s.sensor_id, 
        s.delivery_id, 
        s.temperature, 
        s.humidity, 
        s.is_alert, 
        s.reading_timestamp 
    FROM sensor_data s
    INNER JOIN delivery d ON s.delivery_id = d.delivery_id
    WHERE d.driver_id = ?
    ORDER BY s.reading_timestamp DESC
    LIMIT 100
";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $driverId);
$stmt->execute();
$result = $stmt->get_result();

$history = [];
while ($row = $result->fetch_assoc()) {
    $history[] = $row;
}

$stmt->close();
$conn->close();

// Return the array directly (matches Flutter's expected List<dynamic>)
echo json_encode($history);
<?php
// public/api/get_live_report.php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db.php';

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Order ID required']);
    exit;
}

// 1. Get the latest GPS
$gps_query = "SELECT lat, lng FROM driver_locations WHERE order_id = ? ORDER BY created_at DESC LIMIT 1";
$stmt = $conn->prepare($gps_query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$gps = $stmt->get_result()->fetch_assoc();
$stmt->close();

// 2. Get temperature readings grouped by 1 Minute
// We take the average temperature for every minute to clean up the chart
$temp_query = "
    SELECT 
        AVG(s.temperature) as temp, 
        DATE_FORMAT(s.reading_timestamp, '%Y-%m-%d %H:%i:00') as minute_group
    FROM sensor_data s 
    JOIN delivery d ON s.delivery_id = d.delivery_id 
    WHERE d.order_id = ? 
    GROUP BY minute_group
    ORDER BY minute_group ASC
";
$stmt = $conn->prepare($temp_query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$res = $stmt->get_result();
$stmt->close();

$temps = [];
$labels = [];
while ($row = $res->fetch_assoc()) {
    $val = $row['temp'];
    if (is_numeric($val) && $val > -50 && $val < 100) {
        $temps[] = round((float)$val, 2);
        $labels[] = date('H:i', strtotime($row['minute_group'])); // Only show HH:MM
    }
}

// 3. Get the ACTUAL latest raw temperature for the top box
$latest_query = "
    SELECT s.temperature 
    FROM sensor_data s 
    JOIN delivery d ON s.delivery_id = d.delivery_id 
    WHERE d.order_id = ? 
    ORDER BY s.reading_timestamp DESC LIMIT 1
";
$stmt = $conn->prepare($latest_query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$latest_row = $stmt->get_result()->fetch_assoc();
$latest_temp = $latest_row ? (float)$latest_row['temperature'] : null;
$stmt->close();

// 4. Return JSON
echo json_encode([
    'success' => true,
    'lat' => $gps ? (float)$gps['lat'] : null,
    'lng' => $gps ? (float)$gps['lng'] : null,
    'latest_temp' => $latest_temp,
    'labels' => $labels,
    'temps' => $temps
]);
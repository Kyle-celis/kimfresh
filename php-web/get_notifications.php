<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['count' => 0, 'orders' => []]);
    exit();
}

// 1. Count new pending orders
$pending_res = $conn->query("SELECT COUNT(*) AS c FROM `order` WHERE order_status = 'pending'");
$pending_count = $pending_res->fetch_assoc()['c'];

// 2. Count driver updates in the last 10 minutes
$time_threshold = date('Y-m-d H:i:s', strtotime('-10 minutes'));
$logs_res = $conn->query("
    SELECT COUNT(*) AS c FROM activity_logs 
    WHERE action LIKE 'Driver updated%' 
    AND timestamp >= '$time_threshold'
");
$log_count = $logs_res->fetch_assoc()['c'];

$total_count = $pending_count + $log_count;
$notifications = [];

// 3. Fetch pending orders
if ($pending_count > 0) {
    $query = $conn->query("
        SELECT 'order' as type, o.order_id, r.name AS retailer_name, o.order_date, o.total_amount 
        FROM `order` o 
        LEFT JOIN retailer r ON o.retailer_id = r.retailer_id 
        WHERE o.order_status = 'pending' 
        ORDER BY o.order_date DESC 
        LIMIT 5
    ");
    while ($row = $query->fetch_assoc()) {
        $notifications[] = $row;
    }
}

// 4. Fetch driver updates
if ($log_count > 0) {
    $query = $conn->query("
        SELECT 'log' as type, log_id, action, timestamp,
        SUBSTRING_INDEX(SUBSTRING_INDEX(action, 'Driver ', -1), ' updated', 1) AS driver_name
        FROM activity_logs 
        WHERE action LIKE 'Driver updated%' 
        AND timestamp >= '$time_threshold'
        ORDER BY timestamp DESC 
        LIMIT 5
    ");
    while ($row = $query->fetch_assoc()) {
        $notifications[] = $row;
    }
}

// 5. Sort by date (newest first)
usort($notifications, function($a, $b) {
    $timeA = $a['type'] === 'order' ? strtotime($a['order_date']) : strtotime($a['timestamp']);
    $timeB = $b['type'] === 'order' ? strtotime($b['order_date']) : strtotime($b['timestamp']);
    return $timeB - $timeA;
});

// 6. Keep only the top 5
$notifications = array_slice($notifications, 0, 5);

header('Content-Type: application/json');
echo json_encode(['count' => $total_count, 'orders' => $notifications]);
?>
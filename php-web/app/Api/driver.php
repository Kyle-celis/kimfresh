<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require '../db.php';

$driver_id = isset($_GET['driver_id']) ? (int)$_GET['driver_id'] : 0;

if ($driver_id === 0) {
    echo json_encode([]);
    exit;
}

// Fetch ALL deliveries for this driver (Active and Past)
$sql = "
    SELECT d.delivery_id, d.order_id, d.delivery_status, d.delivery_date,
           r.name AS retailer_name, r.address AS retailer_address, r.phone AS retailer_phone
    FROM delivery d
    JOIN `order` o ON d.order_id = o.order_id
    JOIN retailer r ON o.retailer_id = r.retailer_id
    WHERE d.driver_id = ?
    ORDER BY d.delivery_id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $driver_id);
$stmt->execute();
$result = $stmt->get_result();

$deliveries = [];
while($row = $result->fetch_assoc()) {
    // Fetch the items for this order
    $itemStmt = $conn->prepare("
        SELECT p.name AS product_name, oi.quantity 
        FROM order_item oi 
        JOIN product p ON oi.product_id = p.product_id 
        WHERE oi.order_id = ?
    ");
    $itemStmt->bind_param("i", $row['order_id']);
    $itemStmt->execute();
    $itemsRes = $itemStmt->get_result();
    
    $items = [];
    while($item = $itemsRes->fetch_assoc()) {
        $items[] = $item;
    }
    $row['items'] = $items;
    
    $deliveries[] = $row;
}

echo json_encode($deliveries);
?>
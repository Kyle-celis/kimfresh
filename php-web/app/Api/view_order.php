<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require '../db.php'; 

if (!isset($_GET['retailer_id'])) {
    echo json_encode(["success" => false, "message" => "Missing retailer_id"]);
    exit;
}

$retailer_id = (int)$_GET['retailer_id'];

// Get the orders and join with the items to get product names
$sql = "
    SELECT 
        o.order_id, 
        o.order_date, 
        o.order_status, 
        o.total_amount,
        d.name AS driver_name,
        GROUP_CONCAT(p.name SEPARATOR ', ') AS product_name
    FROM `order` o
    LEFT JOIN delivery del ON o.order_id = del.order_id
    LEFT JOIN driver d ON del.driver_id = d.driver_id
    LEFT JOIN order_item oi ON o.order_id = oi.order_id
    LEFT JOIN product p ON oi.product_id = p.product_id
    WHERE o.retailer_id = ?
    GROUP BY o.order_id
    ORDER BY o.order_date DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $retailer_id);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
    // Format total to be a number so Flutter handles it easily
    $row['total_amount'] = (float)$row['total_amount'];
    $orders[] = $row;
}

echo json_encode($orders);
$stmt->close();
$conn->close();
?>
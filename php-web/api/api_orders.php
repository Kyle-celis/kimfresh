<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require '../db.php'; 

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['retailer_id']) || !isset($data['items'])) {
    echo json_encode(["success" => false, "message" => "Missing data"]);
    exit;
}

$retailer_id = (int)$data['retailer_id'];
$items = $data['items'];
$lat = isset($data['retailer_lat']) ? (float)$data['retailer_lat'] : null;
$lng = isset($data['retailer_lng']) ? (float)$data['retailer_lng'] : null;

// If Flutter didn't send the lat/lng, pull it from the retailer's profile
if ($lat === null || $lng === null) {
    $r_stmt = $conn->prepare("SELECT lat, lng FROM retailer WHERE retailer_id = ?");
    $r_stmt->bind_param("i", $retailer_id);
    $r_stmt->execute();
    $r_res = $r_stmt->get_result();
    if ($r_row = $r_res->fetch_assoc()) {
        $lat = $r_row['lat'];
        $lng = $r_row['lng'];
    }
}

// 1. Create the order
$stmt = $conn->prepare("INSERT INTO `order` (retailer_id, order_status, retailer_lat, retailer_lng) VALUES (?, 'pending', ?, ?)");
$stmt->bind_param("idd", $retailer_id, $lat, $lng);
$stmt->execute();
$order_id = $conn->insert_id;

// 2. Add the items
$stmt_item = $conn->prepare("INSERT INTO order_item (order_id, product_id, quantity, unit_price) SELECT ?, product_id, ?, unit_price FROM product WHERE product_id = ?");
$total_amount = 0;

foreach ($items as $item) {
    $product_id = (int)$item['product_id'];
    $qty = (int)$item['quantity'];
    
    // Get price for total
    $res = $conn->query("SELECT unit_price FROM product WHERE product_id = $product_id");
    if ($row = $res->fetch_assoc()) {
        $total_amount += ($row['unit_price'] * $qty);
    }
    
    $stmt_item->bind_param("iii", $order_id, $qty, $product_id);
    $stmt_item->execute();
}

// 3. Update total amount
$conn->query("UPDATE `order` SET total_amount = $total_amount WHERE order_id = $order_id");

echo json_encode([
    "success" => true, 
    "message" => "Order placed successfully",
    "order_id" => $order_id
]);
?>
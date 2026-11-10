<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['retailer_id']) || !isset($data['items'])) {
    echo json_encode(["success" => false, "message" => "Missing data"]);
    exit;
}

// --- 1. CHECK IF STORE IS CLOSED ---
$storeRes = $conn->query("SELECT manual_override, open_time, close_time FROM store_settings LIMIT 1");
$store = $storeRes ? $storeRes->fetch_assoc() : null;

$current_time = date('H:i:s');
$is_open = false; // Default to CLOSED for safety

if ($store) {
    if ($store['manual_override'] !== null) {
        $is_open = ($store['manual_override'] == 1);
    } else {
        if ($store['open_time'] <= $store['close_time']) {
            $is_open = ($current_time >= $store['open_time'] && $current_time <= $store['close_time']);
        } else {
            $is_open = ($current_time >= $store['open_time'] || $current_time <= $store['close_time']);
        }
    }
}

if (!$is_open) {
    echo json_encode(["success" => false, "message" => "The store is currently closed. Orders cannot be placed right now."]);
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

// --- 2. CHECK IF ALL ITEMS ARE VALID (Not Expired) ---
foreach ($items as $item) {
    $product_id = (int)$item['product_id'];
    $qty = (int)$item['quantity'];

    $check = $conn->prepare("SELECT status, expiry_date, stock_quantity FROM product WHERE product_id = ?");
    $check->bind_param("i", $product_id);
    $check->execute();
    $prod = $check->get_result()->fetch_assoc();

    if (!$prod) {
        echo json_encode(["success" => false, "message" => "Product not found."]);
        exit;
    }
    if ($prod['status'] === 'expired' || ($prod['expiry_date'] !== null && strtotime($prod['expiry_date']) < strtotime(date('Y-m-d')))) {
        echo json_encode(["success" => false, "message" => "One of the items in your cart is expired. Please refresh the page."]);
        exit;
    }
    if ($prod['stock_quantity'] < $qty) {
        echo json_encode(["success" => false, "message" => "Insufficient stock for one of the items."]);
        exit;
    }
}

// --- 3. CREATE THE ORDER ---
$stmt = $conn->prepare("INSERT INTO `order` (retailer_id, order_status, retailer_lat, retailer_lng) VALUES (?, 'pending', ?, ?)");
$stmt->bind_param("idd", $retailer_id, $lat, $lng);
$stmt->execute();
$order_id = $conn->insert_id;

// 4. Add the items
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

// 5. Update total amount
$conn->query("UPDATE `order` SET total_amount = $total_amount WHERE order_id = $order_id");

echo json_encode([
    "success" => true, 
    "message" => "Order placed successfully",
    "order_id" => $order_id
]);
$conn->close();
?>
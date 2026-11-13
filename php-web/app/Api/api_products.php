<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');   
require_once __DIR__ . '/../../config/db.php'; 

// ==========================================
// 1. CHECK IF APP IS ASKING FOR STORE STATUS
// ==========================================
if (isset($_GET['action']) && $_GET['action'] === 'store_status') {
    $storeRes = $conn->query("SELECT manual_override, open_time, close_time FROM store_settings LIMIT 1");
    $store = $storeRes ? $storeRes->fetch_assoc() : null;
    
    $current_time = date('H:i:s');
    $is_open = false;
    $message = "Store is closed";
    
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
        
        // FORCE JSON BOOLEAN
        echo json_encode([
            "is_open" => (bool)$is_open,
            "message" => $is_open ? "Store is Open" : "Manually Closed by Admin",
            "open_time" => date('h:i A', strtotime($store['open_time'])),
            "close_time" => date('h:i A', strtotime($store['close_time']))
        ]);
    } else {
        echo json_encode(["is_open" => false, "message" => "Store settings not found"]);
    }
    exit;
}

// ==========================================
// 2. OTHERWISE, SEND THE PRODUCTS LIST
// ==========================================
$sql = "SELECT * FROM product WHERE status != 'expired' AND (expiry_date IS NULL OR expiry_date >= CURDATE())";
$result = $conn->query($sql);

$products = array();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $row['product_id'] = (int)$row['product_id'];
        $row['unit_price'] = (float)$row['unit_price'];
        $row['stock_quantity'] = (int)$row['stock_quantity'];
        
        $products[] = $row;
    }
}

echo json_encode($products);
$conn->close();
?>
<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require '../db.php';
require '../log_helper.php';

$delivery_id = isset($_GET['delivery_id']) ? (int)$_GET['delivery_id'] : 0;
$data = json_decode(file_get_contents("php://input"), true);
$new_status = $data['delivery_status'] ?? '';

// 1. Validate input
$allowed = ['picked_up', 'in_transit', 'delivered', 'rejected'];
if ($delivery_id <= 0 || !in_array($new_status, $allowed, true)) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid delivery_id or delivery_status"
    ]);
    exit;
}

// 2. Update delivery table
$stmt = $conn->prepare("UPDATE delivery SET delivery_status = ? WHERE delivery_id = ?");
$stmt->bind_param("si", $new_status, $delivery_id);
if (!$stmt->execute()) {
    echo json_encode(["success" => false, "message" => "Failed to update delivery"]);
    exit;
}

// 3. Fetch related info (prepared)
$infoStmt = $conn->prepare("
    SELECT dr.driver_id, dr.name AS driver_name, d.order_id
    FROM delivery d
    JOIN driver dr ON d.driver_id = dr.driver_id
    WHERE d.delivery_id = ?
");
$infoStmt->bind_param("i", $delivery_id);
$infoStmt->execute();
$info = $infoStmt->get_result()->fetch_assoc();

if (!$info) {
    echo json_encode(["success" => false, "message" => "Delivery not found"]);
    exit;
}

$order_id   = (int)$info['order_id'];
$driver_id  = (int)$info['driver_id'];
$driver_name = $info['driver_name'];

// 4. Cascade driver + order status
if ($new_status === 'picked_up') {
    // Driver picked up, but hasn't moved yet
    $conn->query("UPDATE `order` SET order_status = 'waiting_for_pickup' WHERE order_id = $order_id");
    // driver.status stays 'available' (they can still take other orders? or set on_delivery?)
    // Per your design, 'on_delivery' is only set on in_transit.
} elseif ($new_status === 'in_transit') {
    $conn->query("UPDATE driver SET status = 'on_delivery' WHERE driver_id = $driver_id");
    $conn->query("UPDATE `order` SET order_status = 'in_transit' WHERE order_id = $order_id");
} elseif ($new_status === 'delivered') {
    $conn->query("UPDATE `order` SET order_status = 'delivered' WHERE order_id = $order_id");
    $conn->query("UPDATE driver SET status = 'available' WHERE driver_id = $driver_id");
} elseif ($new_status === 'rejected') {
    $conn->query("UPDATE driver SET status = 'available' WHERE driver_id = $driver_id");
    $conn->query("UPDATE `order` SET order_status = 'rejected' WHERE order_id = $order_id");
}

// 5. Log activity (NOTE: admin_id=1 is still hardcoded — see note below)
logActivity($conn, 1, "Driver " . $driver_name . " updated Order #" . $order_id . " to " . strtoupper($new_status));

echo json_encode(["success" => true, "message" => "Delivery updated"]);
?>
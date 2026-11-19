<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../app/helpers/log_helper.php';

$delivery_id = isset($_GET['delivery_id']) ? (int)$_GET['delivery_id'] : 0;
$data = json_decode(file_get_contents("php://input"), true);
$new_status = $data['delivery_status'] ?? '';

// ✅ Get the timestamp from the app, or use current time if missing
$timestamp = isset($data['timestamp']) ? date('Y-m-d H:i:s', strtotime($data['timestamp'])) : date('Y-m-d H:i:s');

// 1. Validate input
$allowed = ['picked_up', 'in_transit', 'delivered', 'rejected'];
if ($delivery_id <= 0 || !in_array($new_status, $allowed, true)) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid delivery_id or delivery_status"
    ]);
    exit;
}

// 2. Proof of delivery is mandatory before an order can be completed
if ($new_status === 'delivered') {
    $proofStmt = $conn->prepare("SELECT proof_photo FROM delivery WHERE delivery_id = ?");
    $proofStmt->bind_param('i', $delivery_id);
    $proofStmt->execute();
    $proofRow = $proofStmt->get_result()->fetch_assoc();
    if (!$proofRow || empty($proofRow['proof_photo'])) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "A proof-of-delivery photo is required. Upload it first."
        ]);
        exit;
    }
}

// 3. Update delivery table based on status
// ✅ Save the exact timestamps for duration calculation
if ($new_status === 'picked_up') {
    $stmt = $conn->prepare("UPDATE delivery SET delivery_status = ?, picked_up_at = ? WHERE delivery_id = ?");
    $stmt->bind_param("ssi", $new_status, $timestamp, $delivery_id);
} elseif ($new_status === 'delivered') {
    $stmt = $conn->prepare("UPDATE delivery SET delivery_status = ?, delivered_at = ? WHERE delivery_id = ?");
    $stmt->bind_param("ssi", $new_status, $timestamp, $delivery_id);
} else {
    $stmt = $conn->prepare("UPDATE delivery SET delivery_status = ? WHERE delivery_id = ?");
    $stmt->bind_param("si", $new_status, $delivery_id);
}

if (!$stmt->execute()) {
    echo json_encode(["success" => false, "message" => "Failed to update delivery"]);
    exit;
}
$stmt->close();

// 4. Fetch related info (prepared)
$infoStmt = $conn->prepare("
    SELECT dr.driver_id, dr.name AS driver_name, d.order_id
    FROM delivery d
    JOIN driver dr ON d.driver_id = dr.driver_id
    WHERE d.delivery_id = ?
");
$infoStmt->bind_param("i", $delivery_id);
$infoStmt->execute();
$info = $infoStmt->get_result()->fetch_assoc();
$infoStmt->close();

if (!$info) {
    echo json_encode(["success" => false, "message" => "Delivery not found"]);
    exit;
}

$order_id    = (int)$info['order_id'];
$driver_id   = (int)$info['driver_id'];
$driver_name = $info['driver_name'];

// 5. Cascade driver + order status
if ($new_status === 'picked_up') {
    $upd = $conn->prepare("UPDATE `order` SET order_status = 'waiting_for_pickup' WHERE order_id = ?");
    $upd->bind_param("i", $order_id);
    $upd->execute();
    $upd->close();
} elseif ($new_status === 'in_transit') {
    $upd1 = $conn->prepare("UPDATE driver SET status = 'on_delivery' WHERE driver_id = ?");
    $upd1->bind_param("i", $driver_id);
    $upd1->execute();
    $upd1->close();

    $upd2 = $conn->prepare("UPDATE `order` SET order_status = 'in_transit' WHERE order_id = ?");
    $upd2->bind_param("i", $order_id);
    $upd2->execute();
    $upd2->close();
} elseif ($new_status === 'delivered') {
    $upd1 = $conn->prepare("UPDATE `order` SET order_status = 'delivered' WHERE order_id = ?");
    $upd1->bind_param("i", $order_id);
    $upd1->execute();
    $upd1->close();

    $upd2 = $conn->prepare("UPDATE driver SET status = 'available' WHERE driver_id = ?");
    $upd2->bind_param("i", $driver_id);
    $upd2->execute();
    $upd2->close();
} elseif ($new_status === 'rejected') {
    $upd1 = $conn->prepare("UPDATE driver SET status = 'available' WHERE driver_id = ?");
    $upd1->bind_param("i", $driver_id);
    $upd1->execute();
    $upd1->close();

    $upd2 = $conn->prepare("UPDATE `order` SET order_status = 'rejected' WHERE order_id = ?");
    $upd2->bind_param("i", $order_id);
    $upd2->execute();
    $upd2->close();
}

// 6. Log activity
logActivity($conn, 1, "Driver " . $driver_name . " updated Order #" . $order_id . " to " . strtoupper($new_status));

echo json_encode(["success" => true, "message" => "Delivery updated"]);
?>
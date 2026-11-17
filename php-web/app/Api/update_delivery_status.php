<?php
// app/Api/update_delivery_status.php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../core/Csrf.php'; // If needed

// Assuming you have some way to authenticate the driver (e.g., session or token)
session_start();
$driver_id = $_SESSION['driver_id'] ?? null; 
if (!$driver_id) {
    // Fallback for testing if not using sessions in API
    $driver_id = $_POST['driver_id'] ?? null; 
}

$delivery_id = $_POST['delivery_id'] ?? null;
$status = $_POST['status'] ?? null;

if (!$delivery_id || !$status) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// 1. Update the delivery status
$stmt = $conn->prepare("UPDATE delivery SET delivery_status = ? WHERE delivery_id = ?");
$stmt->bind_param("si", $status, $delivery_id);
$success = $stmt->execute();

if ($success) {
    // 2. If delivered or rejected, free up the driver
    if ($status === 'delivered' || $status === 'rejected') {
        // Need to get the driver_id associated with this delivery first
        $getDriver = $conn->prepare("SELECT driver_id FROM delivery WHERE delivery_id = ?");
        $getDriver->bind_param("i", $delivery_id);
        $getDriver->execute();
        $res = $getDriver->get_result()->fetch_assoc();
        
        if ($res && $res['driver_id']) {
            $updateDriver = $conn->prepare("UPDATE driver SET status = 'available' WHERE driver_id = ?");
            $updateDriver->bind_param("i", $res['driver_id']);
            $updateDriver->execute();
        }
    }
    
    // Optional: If picked_up or in_transit, set driver to on_delivery
    if ($status === 'picked_up' || $status === 'in_transit') {
        $getDriver = $conn->prepare("SELECT driver_id FROM delivery WHERE delivery_id = ?");
        $getDriver->bind_param("i", $delivery_id);
        $getDriver->execute();
        $res = $getDriver->get_result()->fetch_assoc();
        
        if ($res && $res['driver_id']) {
            $updateDriver = $conn->prepare("UPDATE driver SET status = 'on_delivery' WHERE driver_id = ?");
            $updateDriver->bind_param("i", $res['driver_id']);
            $updateDriver->execute();
        }
    }

    echo json_encode(['success' => true, 'message' => 'Status updated']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
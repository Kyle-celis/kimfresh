<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once __DIR__ . '/../../../config/db.php';

// Get JSON input from the mobile app
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['driver_id']) || !isset($data['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing driver_id or status']);
    exit;
}

$driver_id = (int)$data['driver_id'];
$status    = $data['status']; // 'available' or 'offline'

// Security: Only allow 'available' or 'offline'
if (!in_array($status, ['available', 'offline'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

// Check if the driver is approved by the admin
$check = $conn->prepare("SELECT account_status FROM driver WHERE driver_id = ?");
$check->bind_param("i", $driver_id);
$check->execute();
$res = $check->get_result()->fetch_assoc();

if (!$res || $res['account_status'] !== 'approved') {
    echo json_encode(['success' => false, 'message' => 'Account is not approved or suspended']);
    exit;
}

// Update the status
$stmt = $conn->prepare("UPDATE driver SET status = ? WHERE driver_id = ?");
$stmt->bind_param("si", $status, $driver_id);

if ($stmt->execute()) {
    // If they went online, check if there are orders waiting for them!
    if ($status === 'available') {
        require_once __DIR__ . '/../../../config/db.php'; // ensure db helpers are loaded
        assignWaitingOrders($conn);
    }
    echo json_encode(['success' => true, 'message' => "Status updated to $status"]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
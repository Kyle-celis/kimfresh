<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../../config/db.php';

$id = isset($_GET['driver_id']) ? (int)$_GET['driver_id'] : 0;

if ($id <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid ID"]);
    exit;
}

// --- HANDLE GET (Load Profile) ---
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $conn->prepare("SELECT driver_id, name, email, phone, license_number, status, account_status FROM driver WHERE driver_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Return the direct object. Flutter expects 'name', 'email', etc.
        echo json_encode($row);
    } else {
        echo json_encode(["success" => false, "message" => "Driver not found"]);
    }
    exit;
}

// --- HANDLE PUT (Save Profile) ---
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents("php://input"), true);
    $name = $data['name'] ?? '';
    $phone = $data['phone'] ?? '';
    $license = $data['license_number'] ?? '';

    if (empty($name)) {
        echo json_encode(["success" => false, "message" => "Name is required"]);
        exit;
    }

    $stmt = $conn->prepare("UPDATE driver SET name = ?, phone = ?, license_number = ? WHERE driver_id = ?");
    $stmt->bind_param("sssi", $name, $phone, $license, $id);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Profile updated"]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to update profile"]);
    }
    exit;
}
?>

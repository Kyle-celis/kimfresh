<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require '../db.php';

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid ID"]);
    exit;
}

// --- HANDLE GET (Load Profile) ---
if ($method === 'GET') {
    $stmt = $conn->prepare("SELECT retailer_id, name, email, phone, address, customer_code, lat, lng FROM retailer WHERE retailer_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo json_encode($row); // Return direct object, not wrapped in "data"
    } else {
        echo json_encode(["success" => false, "message" => "Retailer not found"]);
    }
    exit;
}

// --- HANDLE POST (Save Profile) ---
// (Your Flutter app uses http.post for updates, so we check for POST here)
if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    $name = $data['name'] ?? '';
    $phone = $data['phone'] ?? '';
    $address = $data['address'] ?? '';

    if (empty($name)) {
        echo json_encode(["success" => false, "message" => "Name is required"]);
        exit;
    }

    $stmt = $conn->prepare("UPDATE retailer SET name = ?, phone = ?, address = ? WHERE retailer_id = ?");
    $stmt->bind_param("sssi", $name, $phone, $address, $id);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Profile updated"]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to update profile"]);
    }
    exit;
}
?>
<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require '../db.php';

$data = json_decode(file_get_contents("php://input"), true);
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';
$role = $data['role'] ?? 'driver'; // Default to driver if no role sent

if ($role === 'retailer') {
    // --- RETAILER LOGIN ---
    $stmt = $conn->prepare("SELECT * FROM retailer WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // It must look exactly like this:
        if (password_verify($password, $row['password_hash']) || $password === $row['password_hash']) {
            echo json_encode([
                "success" => true,
                "retailer_id" => (int)$row['retailer_id'],
                "name" => $row['name'],
                "token" => "dummy_token_retailer_" . $row['retailer_id']
            ]);
            exit;
        }
    }
} else {
    // --- DRIVER LOGIN ---
    $stmt = $conn->prepare("SELECT * FROM driver WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password_hash'])) {
            echo json_encode([
                "success" => true,
                "driver_id" => (int)$row['driver_id'],
                "name" => $row['name'],
                "token" => "dummy_token_driver_" . $row['driver_id']
            ]);
            exit;
        }
    }
}

echo json_encode(["success" => false, "message" => "Invalid email or password"]);
<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);
$email = $data['email'] ?? '';
$otp = $data['otp'] ?? '';
$new_password = $data['new_password'] ?? '';

if (empty($email) || empty($otp) || empty($new_password)) {
    echo json_encode(["success" => false, "message" => "All fields are required"]);
    exit;
}

// 1. Find driver and check if OTP matches and is not expired
$stmt = $conn->prepare("SELECT driver_id FROM driver WHERE email = ? AND reset_otp = ? AND reset_otp_expires > NOW()");
$stmt->bind_param("ss", $email, $otp);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // 2. Hash the new password
    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
    
    // 3. Update the password and clear the OTP
    $update = $conn->prepare("UPDATE driver SET password_hash = ?, reset_otp = NULL, reset_otp_expires = NULL WHERE driver_id = ?");
    $update->bind_param("si", $new_hash, $row['driver_id']);
    
    if ($update->execute()) {
        echo json_encode(["success" => true, "message" => "Password reset successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to update password"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid or expired OTP"]);
}
?>

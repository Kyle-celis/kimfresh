<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);
$email = $data['email'] ?? '';

if (empty($email)) {
    echo json_encode(["success" => false, "message" => "Email is required"]);
    exit;
}

// 1. Check if driver exists
$stmt = $conn->prepare("SELECT driver_id, name FROM driver WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // 2. Generate a 6-digit OTP
    $otp = rand(100000, 999999);
    
    // 3. Save the OTP to the database
    // (You need to add `reset_otp` and `reset_otp_expires` columns to your driver table for production)
    $conn->query("UPDATE driver SET reset_otp = '$otp', reset_otp_expires = DATE_ADD(NOW(), INTERVAL 5 MINUTE) WHERE driver_id = " . $row['driver_id']);

    // 4. FOR TESTING: Log the OTP to a text file so you can read it.
    // (In production, you would use an email library like PHPMailer here).
    file_put_contents('../otp_log.txt', "Email: $email | OTP: $otp\n", FILE_APPEND);

    echo json_encode([
        "success" => true, 
        "message" => "OTP sent to your email"
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Email not found"]);
}
?>

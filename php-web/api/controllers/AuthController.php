<?php
// api/controllers/AuthController.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class AuthController {
    private $conn;

    private $sender_email = 's2120320@usls.edu.ph';
    private $sender_password = 'YOUR_16_LETTER_APP_PASSWORD'; // <--- REPLACE THIS

    public function __construct($db) {
        $this->conn = $db;
    }

    private function hashPassword($password) {
        return password_hash($password, PASSWORD_ARGON2ID);
    }

    private function generateToken($userId, $role) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode(['user_id' => $userId, 'role' => $role, 'exp' => time() + (86400 * 7)]);
        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        
        $secret = '2027KimfreshDB';
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    private function sendEmail($to, $subject, $body, $otp = "") {
        // LOCAL TESTING FALLBACK
        if ($otp) {
            $log = "Email: $to\nOTP: $otp\nTime: " . date('Y-m-d H:i:s') . "\n-------------------\n";
            file_put_contents(__DIR__ . '/../../local_otps.txt', $log, FILE_APPEND);
            print("Saved OTP to local_otps.txt\n");
        }

        // Try to send real email
        require_once __DIR__ . '/../../PHPMailer/Exception.php';
        require_once __DIR__ . '/../../PHPMailer/PHPMailer.php';
        require_once __DIR__ . '/../../PHPMailer/SMTP.php';

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->sender_email;
            $mail->Password   = $this->sender_password;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom($this->sender_email, 'KimFresh');
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->send();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function login($data) {
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $requested_role = $data['role'] ?? 'any';

        if (!$email || !$password) {
            return ["success" => false, "message" => "Email and password required"];
        }

        $user = null;
        $role = null;

        if ($requested_role === 'retailer') {
            $stmt = $this->conn->prepare("SELECT retailer_id as user_id, name, password_hash, account_status FROM retailer WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $role = "retailer";
        } elseif ($requested_role === 'driver') {
            $stmt = $this->conn->prepare("SELECT driver_id as user_id, name, password_hash, account_status FROM driver WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $role = "driver";
        } else {
            $stmt = $this->conn->prepare("SELECT admin_id as user_id, name, password_hash FROM admin WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $role = "admin";
        }

        if (!$user) {
            return ["success" => false, "message" => "Invalid email or password"];
        }

        if (!password_verify($password, $user['password_hash'])) {
            return ["success" => false, "message" => "Invalid email or password"];
        }

        if ($role !== 'admin') {
            if ($user['account_status'] === 'pending') return ["success" => false, "message" => "Account pending admin approval."];
            if ($user['account_status'] === 'rejected') return ["success" => false, "message" => "Account rejected."];
            if ($user['account_status'] === 'otp_pending') {
                return ["success" => false, "require_otp" => true, "user_id" => $user['user_id'], "retailer_id" => $user['user_id'], "driver_id" => $user['user_id']];
            }
        }

        $token = $this->generateToken($user['user_id'], $role);
        return [
            "success" => true, "token" => $token, "user_id" => $user['user_id'],
            "retailer_id" => $user['user_id'], "driver_id" => $user['user_id'],
            "name" => $user['name'], "retailer_name" => $user['name'], "role" => $role
        ];
    }

    public function register($data) {
        $name = $data['name'] ?? '';
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $phone = $data['phone'] ?? '';
        $address = $data['address'] ?? '';

        $stmt = $this->conn->prepare("SELECT retailer_id, account_status FROM retailer WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();

        if ($existing) {
            if ($existing['account_status'] === 'otp_pending') {
                $otp = sprintf("%06d", mt_rand(1, 999999));
                $expiry = date('Y-m-d H:i:s', strtotime('+5 minutes'));
                $upd = $this->conn->prepare("UPDATE retailer SET otp_code = ?, otp_expires_at = ? WHERE retailer_id = ?");
                $upd->bind_param("ssi", $otp, $expiry, $existing['retailer_id']);
                $upd->execute();
                
                $this->sendEmail($email, "KimFresh Verification Code (Resend)", "<h2>Hello $name,</h2><p>Your code is: <b>$otp</b></p>", $otp);
                return ["success" => true, "retailer_id" => $existing['retailer_id'], "message" => "Unverified account. New OTP sent."];
            }
            return ["success" => false, "message" => "Email already registered."];
        }

        do {
            $code = sprintf("%06d", mt_rand(1, 999999));
            $check = $this->conn->prepare("SELECT retailer_id FROM retailer WHERE customer_code = ?");
            $check->bind_param("s", $code);
            $check->execute();
        } while ($check->get_result()->num_rows > 0);

        $hash = $this->hashPassword($password);
        $otp = sprintf("%06d", mt_rand(1, 999999));
        $expiry = date('Y-m-d H:i:s', strtotime('+5 minutes'));

        $stmt = $this->conn->prepare("INSERT INTO retailer (name, email, phone, password_hash, address, customer_code, account_status, otp_code, otp_expires_at) VALUES (?, ?, ?, ?, ?, ?, 'otp_pending', ?, ?)");
        $stmt->bind_param("ssssssss", $name, $email, $phone, $hash, $address, $code, $otp, $expiry);
        
        if ($stmt->execute()) {
            $retailer_id = $this->conn->insert_id;
            $this->sendEmail($email, "KimFresh Verification Code", "<h2>Hello $name,</h2><p>Your code is: <b>$otp</b></p>", $otp);
            return ["success" => true, "retailer_id" => $retailer_id, "customer_code" => $code, "name" => $name, "message" => "Registered! Check email for OTP."];
        }
        return ["success" => false, "message" => "Database error"];
    }

    public function verifyOTP($data) {
        $retailer_id = $data['retailer_id'] ?? 0;
        $otp = $data['otp'] ?? '';

        $stmt = $this->conn->prepare("SELECT otp_code, otp_expires_at FROM retailer WHERE retailer_id = ? AND account_status = 'otp_pending'");
        $stmt->bind_param("i", $retailer_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();

        if (!$res || $res['otp_code'] !== $otp || strtotime($res['otp_expires_at']) < time()) {
            return ["success" => false, "message" => "Invalid or expired OTP."];
        }

        $upd = $this->conn->prepare("UPDATE retailer SET account_status = 'approved', otp_code = NULL WHERE retailer_id = ?");
        $upd->bind_param("i", $retailer_id);
        $upd->execute();

        return ["success" => true, "message" => "Account verified!"];
    }

    public function forgotPassword($data) {
        $email = $data['email'] ?? '';
        $otp = sprintf("%06d", mt_rand(1, 999999));
        $expiry = date('Y-m-d H:i:s', strtotime('+5 minutes'));

        $stmt = $this->conn->prepare("SELECT retailer_id FROM retailer WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user) {
            $upd = $this->conn->prepare("UPDATE retailer SET otp_code = ?, otp_expires_at = ? WHERE retailer_id = ?");
            $upd->bind_param("ssi", $otp, $expiry, $user['retailer_id']);
            $upd->execute();
            $this->sendEmail($email, "KimFresh Password Reset", "Your OTP is: <b>$otp</b>", $otp);
            return ["success" => true, "message" => "OTP sent to email."];
        }
        return ["success" => false, "message" => "Email not found."];
    }

    public function resetPassword($data) {
        $email = $data['email'] ?? '';
        $new_password = $data['new_password'] ?? '';
        $otp = $data['otp'] ?? '';

        $stmt = $this->conn->prepare("SELECT retailer_id, otp_code, otp_expires_at FROM retailer WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if (!$user || $user['otp_code'] !== $otp || strtotime($user['otp_expires_at']) < time()) {
            return ["success" => false, "message" => "Invalid or expired OTP."];
        }

        $hash = $this->hashPassword($new_password);
        $upd = $this->conn->prepare("UPDATE retailer SET password_hash = ?, otp_code = NULL WHERE retailer_id = ?");
        $upd->bind_param("si", $hash, $user['retailer_id']);
        $upd->execute();

        return ["success" => true, "message" => "Password reset successfully."];
    }
}
?>

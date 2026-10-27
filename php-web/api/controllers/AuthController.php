<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../models/UserModel.php';

class AuthController extends BaseController {
    private string $jwt_secret = '2027KimfreshDB';
    private UserModel $userModel;

    public function __construct(mysqli $db) {
        parent::__construct($db);
        $this->userModel = new UserModel($db);
    }

    public function login(array $data): void {
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $role = $data['role'] ?? 'any';

        if (empty($email) || empty($password)) {
            $this->respond(['success' => false, 'message' => 'Email and password required'], 400);
        }

        $user = null;
        $actual_role = null;

        if ($role === 'retailer') {
            $user = $this->userModel->findRetailerByEmail($email);
            $actual_role = 'retailer';
        } elseif ($role === 'driver') {
            $user = $this->userModel->findDriverByEmail($email);
            $actual_role = 'driver';
        } else {
            $user = $this->userModel->findAdminByEmail($email);
            $actual_role = 'admin';
        }

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->respond(['success' => false, 'message' => 'Invalid credentials'], 401);
        }

        if (isset($user['account_status'])) {
            if ($user['account_status'] === 'pending') {
                $this->respond(['success' => false, 'message' => 'Account pending admin approval.'], 200);
            }
            if ($user['account_status'] === 'otp_pending') {
                $this->respond(['success' => false, 'require_otp' => true, 'user_id' => $user['user_id'], 'driver_id' => $user['user_id'], 'retailer_id' => $user['user_id']], 200);
            }
        }

        $token = $this->generateToken((int)$user['user_id'], $actual_role);
        
        $this->respond([
            'success' => true, 'token' => $token, 'user_id' => $user['user_id'], 
            'retailer_id' => $user['user_id'], 'driver_id' => $user['user_id'], 
            'name' => $user['name'], 'retailer_name' => $user['name'], 'role' => $actual_role
        ]);
    }

    public function register(array $data): void {
        $name = $data['name'] ?? '';
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $phone = $data['phone'] ?? '';

        if (empty($name) || empty($email) || empty($password)) {
            $this->respond(['success' => false, 'message' => 'Missing fields'], 400);
        }

        if ($this->userModel->findRetailerByEmail($email)) {
            $this->respond(['success' => false, 'message' => 'Email already registered'], 400);
        }

        $hash = password_hash($password, PASSWORD_ARGON2ID);
        $otp = sprintf("%06d", mt_rand(1, 999999));
        $expiry = date('Y-m-d H:i:s', strtotime('+5 minutes'));

        $retailer_id = $this->userModel->createRetailer($name, $email, $phone, $hash, $otp, $expiry);

        if ($retailer_id) {
            $this->sendEmail($email, "KimFresh Verification", "Your OTP is: <b>$otp</b>");
            $this->respond(['success' => true, 'retailer_id' => $retailer_id, 'message' => 'Registered! Check email for OTP.']);
        }
        $this->respond(['success' => false, 'message' => 'Database error'], 500);
    }

    public function driverRegister(array $data): void {
        $name = $data['name'] ?? '';
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $phone = $data['phone'] ?? '';
        $license = $data['license_number'] ?? '';

        if (empty($name) || empty($email) || empty($password)) {
            $this->respond(['success' => false, 'message' => 'Missing required fields'], 400);
        }

        if ($this->userModel->findDriverByEmail($email)) {
            $this->respond(['success' => false, 'message' => 'Email already registered'], 400);
        }

        $hash = password_hash($password, PASSWORD_ARGON2ID);
        $driver_id = $this->userModel->createDriver($name, $email, $phone, $license, $hash);

        if ($driver_id) {
            $this->respond(['success' => true, 'driver_id' => $driver_id, 'message' => 'Account created! Wait for admin approval.']);
        }
        $this->respond(['success' => false, 'message' => 'Database error'], 500);
    }

    public function verifyOTP(array $data): void {
        $this->handleOTPVerification('retailer', 'retailer_id', (int)($data['retailer_id'] ?? 0), $data['otp'] ?? '');
    }

    public function driverVerifyOTP(array $data): void {
        $this->handleOTPVerification('driver', 'driver_id', (int)($data['driver_id'] ?? 0), $data['otp'] ?? '');
    }

    private function handleOTPVerification(string $table, string $id_col, int $id, string $otp): void {
        $res = $this->userModel->getOTP($table, $id_col, $id);

        if (!$res || $res['otp_code'] !== $otp || strtotime($res['otp_expires_at']) < time()) {
            $this->respond(['success' => false, 'message' => 'Invalid or expired OTP'], 400);
        }

        $this->userModel->approveUser($table, $id_col, $id);
        $this->respond(['success' => true, 'message' => 'Account verified!']);
    }

    public function getRetailerProfile(int $id): void {
        $this->respond($this->userModel->getRetailerProfile($id) ?: ['success' => false]);
    }

    public function getDriverProfile(int $id): void {
        $this->respond($this->userModel->getDriverProfile($id) ?: ['success' => false]);
    }

    public function getProducts(): void {
        // You can move this to OrderModel if you want to be strict
        $result = $this->db->query("SELECT * FROM product ORDER BY product_id DESC");
        $products = [];
        while ($row = $result->fetch_assoc()) { $products[] = $row; }
        $this->respond($products);
    }

    public function getStoreStatus(): void {
        $result = $this->db->query("SELECT * FROM store_settings WHERE id = 1")->fetch_assoc();
        date_default_timezone_set('Asia/Manila');
        $current_time = date('H:i:s');
        $is_open = false;
        
        if ($result['manual_override'] !== NULL) {
            $is_open = ($result['manual_override'] == 1);
        } else {
            if ($result['open_time'] <= $result['close_time']) {
                $is_open = ($current_time >= $result['open_time'] && $current_time <= $result['close_time']);
            } else {
                $is_open = ($current_time >= $result['open_time'] || $current_time <= $result['close_time']);
            }
        }
        $this->respond(["is_open" => $is_open, "message" => $is_open ? "Open" : "Closed", "status_type" => $is_open ? "normal" : "closed"]);
    }

    private function generateToken(int $userId, string $role): string {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode(['user_id' => $userId, 'role' => $role, 'exp' => time() + 86400]);
        $b64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $b64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        $signature = hash_hmac('sha256', "$b64Header.$b64Payload", $this->jwt_secret, true);
        return "$b64Header.$b64Payload." . str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    }

    private function sendEmail(string $to, string $subject, string $body): void {
        require_once __DIR__ . '/../../PHPMailer/Exception.php';
        require_once __DIR__ . '/../../PHPMailer/PHPMailer.php';
        require_once __DIR__ . '/../../PHPMailer/SMTP.php';

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 's2120320@usls.edu.ph'; 
            $mail->Password   = 'zrtjposwynbyubpq'; 
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;

            $mail->setFrom('s2120320@usls.edu.ph', 'KimFresh');
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;

            $mail->send();
        } catch (\Exception $e) {
            file_put_contents(__DIR__ . '/../../../local_otps.txt', "To: $to | OTP: $body\n", FILE_APPEND);
        }
    }
}

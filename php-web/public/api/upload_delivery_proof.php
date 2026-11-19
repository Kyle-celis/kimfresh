<?php
// public/api/upload_delivery_proof.php
// Driver sends a mandatory proof-of-delivery photo, then the delivery is completed.
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../app/helpers/log_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'POST required']);
    exit;
}

$delivery_id = isset($_POST['delivery_id']) ? (int)$_POST['delivery_id'] : 0;
if ($delivery_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing delivery_id']);
    exit;
}

if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Proof photo is required']);
    exit;
}

// 1. Validate the delivery and make sure it can still be completed
$infoStmt = $conn->prepare("
    SELECT d.order_id, d.driver_id, d.delivery_status, dr.name AS driver_name
    FROM delivery d
    JOIN driver dr ON d.driver_id = dr.driver_id
    WHERE d.delivery_id = ?
");
$infoStmt->bind_param('i', $delivery_id);
$infoStmt->execute();
$info = $infoStmt->get_result()->fetch_assoc();

if (!$info) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Delivery not found']);
    exit;
}
if ($info['delivery_status'] === 'delivered') {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'This delivery is already completed']);
    exit;
}

$order_id    = (int)$info['order_id'];
$driver_id   = (int)$info['driver_id'];
$driver_name = $info['driver_name'];

// 2. Validate the uploaded image
$file = $_FILES['photo'];
if ($file['size'] > 8 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Photo must be 8 MB or smaller']);
    exit;
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

$allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
if (!isset($allowed[$mime])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Photo must be a JPG, PNG or WebP image']);
    exit;
}
$ext = $allowed[$mime];

// 3. Store the file under public/uploads/proofs/
$uploadDir = __DIR__ . '/../uploads/proofs';
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Upload folder could not be created']);
    exit;
}

$filename = 'delivery_' . $delivery_id . '_' . time() . '.' . $ext;
$target   = $uploadDir . '/' . $filename;
if (!move_uploaded_file($file['tmp_name'], $target)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not save the photo']);
    exit;
}

$relativePath = '/php-web/public/uploads/proofs/' . $filename;

// 4. Save the proof and complete the delivery
$conn->begin_transaction();
try {
    $stmt = $conn->prepare("
        UPDATE delivery
        SET proof_photo = ?, proof_uploaded_at = NOW(), delivery_status = 'delivered'
        WHERE delivery_id = ?
    ");
    $stmt->bind_param('si', $relativePath, $delivery_id);
    $stmt->execute();

    $stmt = $conn->prepare("UPDATE `order` SET order_status = 'delivered' WHERE order_id = ?");
    $stmt->bind_param('i', $order_id);
    $stmt->execute();

    $stmt = $conn->prepare("UPDATE driver SET status = 'available' WHERE driver_id = ?");
    $stmt->bind_param('i', $driver_id);
    $stmt->execute();

    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    @unlink($target);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit;
}

logActivity($conn, 1, 'Driver ' . $driver_name . ' uploaded proof of delivery for Order #' . $order_id);

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'localhost';

echo json_encode([
    'success'       => true,
    'message'       => 'Proof of delivery saved',
    'proof_photo'   => $relativePath,
    'proof_url'     => $scheme . '://' . $host . $relativePath,
    'proof_uploaded_at' => date('Y-m-d H:i:s'),
]);

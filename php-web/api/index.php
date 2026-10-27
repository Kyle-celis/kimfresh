<?php
declare(strict_types=1);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/OrderController.php';

$database = new Database();
$conn = $database->conn;

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = str_replace('/api', '', $uri);
$input = json_decode(file_get_contents('php://input'), true) ?? [];

$auth = new AuthController($conn);
$order = new OrderController($conn);

// --- STATIC ROUTES ---
$postRoutes = [
    '/login' => [$auth, 'login'],
    '/retailer/register' => [$auth, 'register'],
    '/retailer/verify-otp' => [$auth, 'verifyOTP'],
    '/driver/register' => [$auth, 'driverRegister'],
    '/driver/verify-otp' => [$auth, 'driverVerifyOTP'],
    '/orders' => [$order, 'placeOrder']
];

$getRoutes = [
    '/products' => [$auth, 'getProducts'],
    '/store/status' => [$auth, 'getStoreStatus']
];

// --- DYNAMIC ROUTES ---
$dynamicRoutes = [
    'GET' => [
        '/^\/retailer\/(\d+)\/profile$/' => [$auth, 'getRetailerProfile'],
        '/^\/retailer\/(\d+)\/orders$/' => [$order, 'getRetailerOrders'],
        '/^\/driver\/(\d+)\/profile$/' => [$auth, 'getDriverProfile'],
        '/^\/driver\/(\d+)\/deliveries$/' => [$order, 'getDriverOrders'], 
    ],
    'PUT' => [
        '/^\/retailer\/(\d+)\/profile$/' => [$auth, 'updateRetailerProfile'],
        '/^\/driver\/(\d+)\/profile$/' => [$auth, 'updateDriverProfile'],
        '/^\/deliveries\/(\d+)\/status$/' => [$order, 'updateDeliveryStatus'], 
    ]
];

// --- EXECUTE ROUTES ---
if ($method === 'POST' && isset($postRoutes[$uri])) {
    call_user_func($postRoutes[$uri], $input);
    exit;
}

if ($method === 'GET' && isset($getRoutes[$uri])) {
    call_user_func($getRoutes[$uri]);
    exit;
}

if (isset($dynamicRoutes[$method])) {
    foreach ($dynamicRoutes[$method] as $pattern => $handler) {
        if (preg_match($pattern, $uri, $matches)) {
            $id = (int)$matches[1];
            if ($method === 'GET') {
                call_user_func($handler, $id);
            } else {
                call_user_func($handler, $id, $input);
            }
            exit;
        }
    }
}

http_response_code(404);
echo json_encode(['success' => false, 'message' => 'API Endpoint Not Found']);

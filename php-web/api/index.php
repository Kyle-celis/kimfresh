<?php
// api/index.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$request_uri = str_replace('/api', '', $request_uri);
$method = $_SERVER['REQUEST_METHOD'];

$input = json_decode(file_get_contents('php://input'), true) ?? [];

require_once 'controllers/AuthController.php';
require_once 'controllers/OrderController.php';
require_once '../db.php'; 
require_once '../log_helper.php';

$authController = new AuthController($conn);
$orderController = new OrderController($conn);

switch (true) {
    case ($request_uri === '/login' && $method === 'POST'):
        echo json_encode($authController->login($input));
        break;

    case ($request_uri === '/retailer/register' && $method === 'POST'):
        echo json_encode($authController->register($input));
        break;

    case ($request_uri === '/retailer/verify-otp' && $method === 'POST'):
        echo json_encode($authController->verifyOTP($input));
        break;

    case ($request_uri === '/forgot-password' && $method === 'POST'):
        echo json_encode($authController->forgotPassword($input));
        break;

    case ($request_uri === '/reset-password' && $method === 'POST'):
        echo json_encode($authController->resetPassword($input));
        break;

    case ($request_uri === '/orders' && $method === 'POST'):
        echo json_encode($orderController->placeOrder($input));
        break;

    default:
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "API Endpoint Not Found"]);
        break;
}

<?php
// public/index.php — strict MVC, no framework

ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
session_start();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../app/helpers/log_helper.php';
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Model.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base = '/php-web/public';
if (strpos($uri, $base) === 0) {
    $uri = substr($uri, strlen($base));
}
$uri = '/' . trim($uri, '/');
$method = $_SERVER['REQUEST_METHOD'];

$routes = [
    'GET'  => [
        '/'                    => ['DashboardController', 'index'],
        '/dashboard'           => ['DashboardController', 'index'],
        '/login'               => ['AuthController', 'showLogin'],
        '/logout'              => ['LogoutController', 'index'],
        '/orders'              => ['OrderController', 'index'],
        '/orders/view'         => ['ViewOrderController', 'show'],
        '/orders/cancel'       => ['CancelOrderController', 'index'],
        '/products'            => ['ProductController', 'index'],
        '/drivers'              => ['DriverController', 'index'],
        '/analytics'           => ['AnalyticsController', 'index'],
        '/settings'            => ['SettingsController', 'index'],
        '/logs'                => ['LogsController', 'index'],
        '/products/export-csv'     => ['CsvController', 'exportProducts'],
        '/superadmin'          => ['SuperAdminController', 'index'],
        '/track'               => ['TrackingController', 'index'],
        '/notifications'       => ['NotificationController', 'index'],
        '/save-location'       => ['SaveLocationController', 'index'],
        '/save-retailer-location' => ['SaveRetailerLocationController', 'index'],
    ],
    'POST' => [
        '/superadmin'              => ['SuperAdminController', 'index'],
        '/login'                   => ['AuthController', 'login'],
        '/products'                => ['ProductController', 'index'],
        '/drivers'              => ['DriverController', 'index'],
        '/orders/view'             => ['ViewOrderController', 'show'],
        '/orders'                  => ['OrderController', 'index'],
        '/settings'                => ['SettingsController', 'index'],
        '/settings'                => ['SettingsController', 'index'],
        '/products/import-csv'     => ['CsvController', 'importProducts'],
        '/orders/cancel'           => ['CancelOrderController', 'index'],
        '/save-retailer-location'  => ['SaveRetailerLocationController', 'index'],
    ],
];

if (!isset($routes[$method][$uri])) {
    http_response_code(404);
    echo "<h1>404 — no route for {$method} {$uri}</h1>";
    echo "<p>Available routes:</p><ul>";
    foreach ($routes as $m => $list) {
        foreach ($list as $path => $handler) {
            echo "<li><code>{$m} {$path}</code> → {$handler[0]}::{$handler[1]}()</li>";
        }
    }
    echo "</ul>";
    exit();
}

list($controllerName, $action) = $routes[$method][$uri];
$file = __DIR__ . '/../app/Controllers/' . $controllerName . '.php';

if (!file_exists($file)) {
    http_response_code(500);
    die("Missing controller file: {$file}");
}

require_once $file;

if (!class_exists($controllerName)) {
    http_response_code(500);
    die("Class {$controllerName} not found in {$file}");
}

$controller = new $controllerName($conn);

if (!method_exists($controller, $action)) {
    http_response_code(500);
    die("Missing action {$action} on {$controllerName}");
}

$controller->$action();



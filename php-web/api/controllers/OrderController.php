<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../models/OrderModel.php';

class OrderController extends BaseController {
    private OrderModel $orderModel;

    public function __construct(mysqli $db) {
        parent::__construct($db);
        $this->orderModel = new OrderModel($db);
    }

    public function placeOrder(array $data): void {
        // 1. Extract Token from Header
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        
        if (strpos($authHeader, 'Bearer ') === false) {
            $this->respond(['success' => false, 'message' => 'Forbidden: Missing token'], 403);
        }

        $token = str_replace('Bearer ', '', $authHeader);
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            $this->respond(['success' => false, 'message' => 'Invalid token format'], 401);
        }

        // 2. Decode Token Payload
        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
        
        if (!$payload || !isset($payload['user_id']) || $payload['role'] !== 'retailer') {
            $this->respond(['success' => false, 'message' => 'Unauthorized: Invalid token or role'], 401);
        }

        $retailer_id = (int)$payload['user_id'];
        $items = $data['items'] ?? [];

        if (empty($items)) {
            $this->respond(['success' => false, 'message' => 'Cart is empty'], 400);
        }

        // 3. Store Gatekeeper
        $store = $this->db->query("SELECT * FROM store_settings WHERE id = 1")->fetch_assoc();
        date_default_timezone_set('Asia/Manila');
        $current_time = date('H:i:s');
        $is_open = false;

        if ($store['manual_override'] !== NULL) {
            $is_open = ($store['manual_override'] == 1);
        } else {
            if ($store['open_time'] <= $store['close_time']) {
                $is_open = ($current_time >= $store['open_time'] && $current_time <= $store['close_time']);
            } else {
                $is_open = ($current_time >= $store['open_time'] || $current_time <= $store['close_time']);
            }
        }

        if (!$is_open) {
            $this->respond(['success' => false, 'message' => 'The store is currently closed.'], 403);
        }

        // 4. Calculate total and check stock
        $total = 0;
        foreach ($items as $item) {
            $prod = $this->orderModel->getProductPriceAndStock((int)$item['product_id']);
            if (!$prod || $prod['stock_quantity'] < $item['quantity']) {
                $this->respond(['success' => false, 'message' => 'One or more products are out of stock.'], 400);
            }
            $total += ((float)$prod['unit_price'] * (int)$item['quantity']);
        }

        // 5. Create Order
        $order_id = $this->orderModel->createOrder($retailer_id, $total);

        // 6. Create Items & Deduct Stock
        foreach ($items as $item) {
            $prod = $this->orderModel->getProductPriceAndStock((int)$item['product_id']);
            $price = (float)$prod['unit_price'];
            $subtotal = $price * (int)$item['quantity'];
            
            $this->orderModel->createOrderItem(
                $order_id, 
                (int)$item['product_id'], 
                (int)$item['quantity'], 
                $price, 
                $subtotal
            );
            $this->orderModel->deductStock((int)$item['product_id'], (int)$item['quantity']);
        }

        $this->respond(['success' => true, 'order_id' => $order_id, 'message' => 'Order placed successfully!']);
    }

    public function getRetailerOrders(int $retailer_id): void {
        $this->respond($this->orderModel->getRetailerOrders($retailer_id));
    }

    public function getDriverOrders(int $driver_id): void {
        $this->respond($this->orderModel->getDriverDeliveries($driver_id));
    }

    public function updateDeliveryStatus(int $delivery_id, array $data): void {
        $new_status = $data['delivery_status'] ?? '';
        if (empty($new_status)) $this->respond(['success' => false, 'message' => 'Missing status'], 400);

        $order_id = $this->orderModel->updateDelivery($delivery_id, $new_status);
        
        if ($order_id > 0) {
            $status_map = ['assigned' => 'approved', 'picked_up' => 'waiting_for_pickup', 'in_transit' => 'in_transit', 'delivered' => 'delivered'];
            $this->orderModel->updateOrderStatus($order_id, $status_map[$new_status] ?? 'approved');

            if ($new_status === 'delivered') {
                $this->orderModel->freeDriver($delivery_id);
            }
        }

        $this->respond(['success' => true, 'message' => "Delivery updated to $new_status"]);
    }
}

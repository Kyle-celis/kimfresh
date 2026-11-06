<?php
// app/Controllers/TrackingController.php — strict MVC Controller (JSON)
class TrackingController extends Controller
{
    public function index()
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');

        $order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

        if ($order_id <= 0) {
            $this->json(['success' => false, 'message' => 'Invalid order ID']);
        }

        $stmt = $this->conn->prepare("
            SELECT driver_lat, driver_lng, retailer_lat, retailer_lng, order_status
            FROM `order`
            WHERE order_id = ?
        ");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if (!$row) {
            $this->json(['success' => false, 'message' => 'Order not found']);
        }

        $this->json([
            'success'      => true,
            'driver_lat'   => $row['driver_lat']   !== null ? (float)$row['driver_lat']   : null,
            'driver_lng'   => $row['driver_lng']   !== null ? (float)$row['driver_lng']   : null,
            'retailer_lat' => $row['retailer_lat'] !== null ? (float)$row['retailer_lat'] : null,
            'retailer_lng' => $row['retailer_lng'] !== null ? (float)$row['retailer_lng'] : null,
            'order_status' => $row['order_status'],
        ]);
    }
}
<?php
// app/Controllers/LocationController.php — strict MVC Controller (JSON)
class LocationController extends Controller
{
    public function index()
    {
        header('Content-Type: application/json');

        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['order_id'], $data['lat'], $data['lng'])) {
            $this->json(['success' => false, 'message' => 'Missing data']);
        }

        $order_id = (int)$data['order_id'];
        $lat      = (float)$data['lat'];
        $lng      = (float)$data['lng'];

        if ($order_id <= 0 || $lat < 4.0 || $lat > 21.0 || $lng < 116.0 || $lng > 127.0) {
            $this->json(['success' => false, 'message' => 'Invalid coordinates']);
        }

        // 1. Log history
        $stmt1 = $this->conn->prepare("INSERT INTO driver_locations (order_id, lat, lng) VALUES (?, ?, ?)");
        $stmt1->bind_param("idd", $order_id, $lat, $lng);
        if (!$stmt1->execute()) {
            $this->json(['success' => false, 'message' => 'History insert failed']);
        }

        // 2. Update current position on order
        $stmt2 = $this->conn->prepare("UPDATE `order` SET driver_lat = ?, driver_lng = ? WHERE order_id = ?");
        $stmt2->bind_param("ddi", $lat, $lng, $order_id);
        if (!$stmt2->execute()) {
            $this->json(['success' => false, 'message' => 'Live update failed']);
        }

        $this->json(['success' => true, 'message' => 'Location updated and logged']);
    }
}
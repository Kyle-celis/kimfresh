<?php
// app/Controllers/AnalyticsController.php — strict MVC Controller
class AnalyticsController extends Controller
{
    public function index()
    {
        $this->requireAuth();

        require_once __DIR__ . '/../Models/Analytics.php';
        $model = new Analytics($this->conn);

        // 1. Get the filter type (default to 'all')
        $filter = $_GET['filter'] ?? 'all';
        if (!in_array($filter, ['weekly', 'monthly', 'yearly', 'all'])) {
            $filter = 'all';
        }

        // 2. Pass the filter to the model
        $top_products  = $model->getTopProducts(5, $filter);
        $top_drivers   = $model->getTopDrivers(5, $filter);
        $stats         = $model->getStats($filter);

        $this->view('analytics/index', [
            'top_products'     => $top_products,
            'top_drivers'      => $top_drivers,
            'total_orders'     => $stats['total_orders'],
            'delivered_orders' => $stats['delivered'],
            'rejected'         => $stats['rejected'],
            'total_earnings'   => $stats['total_earnings'],
            'filter'           => $filter // Pass the current filter to the view
        ]);
    }

    public function viewDelivery()
    {
        $order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($order_id <= 0) {
            $this->redirect('/php-web/public/analytics');
        }

        require_once __DIR__ . '/../Models/Order.php';
        $orderModel = new Order($this->conn);
        
        $order = $orderModel->getById($order_id);
        if (!$order) { die("Order not found."); }
        
        $driver_name = $orderModel->getDriverName($order_id) ?? 'Unassigned';
        $sensor_data = $orderModel->getSensorData($order_id);
        $proof = $orderModel->getDeliveryProof($order_id);

        // 1. Fetch the latest live GPS (using order_id)
        $gps_query = "SELECT lat, lng FROM driver_locations WHERE order_id = ? ORDER BY created_at DESC LIMIT 1";
        $stmt = $this->conn->prepare($gps_query);
        $stmt->bind_param("i", $order_id);
        
        $stmt->execute();
        $live_gps = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // 3. Calculate Historical Duration
        $duration = "In Progress";
        $delivery_query = "SELECT picked_up_at, delivered_at, delivery_date FROM delivery WHERE order_id = ? LIMIT 1";
        $d_stmt = $this->conn->prepare($delivery_query);
        $d_stmt->bind_param("i", $order_id);
        $d_stmt->execute();
        $delivery_record = $d_stmt->get_result()->fetch_assoc();
        $d_stmt->close();

        if ($delivery_record) {
            $startTime = $delivery_record['picked_up_at'] ?? $delivery_record['delivery_date'];
            $endTime = $delivery_record['delivered_at'];

            if ($startTime && $endTime) {
                $start = new DateTime($startTime);
                $end = new DateTime($endTime);
                $diff = $start->diff($end);
                $duration = ($diff->h > 0) ? $diff->h . " hr, " . $diff->i . " mins" : $diff->i . " minutes";
            } elseif ($startTime && ($order['order_status'] === 'in_transit' || $order['order_status'] === 'picked_up')) {
                // ✅ Show live ticking duration
                $start = new DateTime($startTime);
                $now = new DateTime();
                $diff = $start->diff($now);
                $duration = $diff->i . " mins (In Progress)";
            }
        }

        $this->view('analytics/view', [
            'order_id'    => $order_id,
            'order'       => $order,
            'driver_name' => $driver_name,
            'sensor_data' => $sensor_data,
            'proof'       => $proof,
            'live_gps'    => $live_gps,
            'duration'    => $duration
        ]);
    }

    public function viewDriver()
    {
        $driver_id = isset($_GET['driver_id']) ? (int)$_GET['driver_id'] : 0;
        if ($driver_id <= 0) {
            $this->redirect('/php-web/public/analytics');
        }

        // 1. Find the latest delivery for this driver
        $query = "SELECT d.order_id FROM delivery d WHERE d.driver_id = ? ORDER BY d.delivery_date DESC LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $driver_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // If the driver has no deliveries, redirect back
        if (!$res) {
            $this->redirect('/php-web/public/analytics');
        }

        // 2. Redirect to the viewDelivery page using that order ID
        $this->redirect('/php-web/public/analytics/view?id=' . $res['order_id']);
    }

    public function exportPdf()
    {
        if (ob_get_length()) ob_end_clean();
        ob_start();

        require_once __DIR__ . '/../../lib/fpdf/fpdf.php';
        require_once __DIR__ . '/../helpers/KimFreshPdf.php';

        $pdf = new KimFreshPdf();
        $pdf->title = 'KimFresh Analytics Report';
        $pdf->AddPage();
        
        $filter = $_GET['filter'] ?? 'all';
        
        require_once __DIR__ . '/../Models/Analytics.php';
        $analyticsModel = new Analytics($this->conn);
        $stats = $analyticsModel->getStats($filter);

        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, 'Analytics Overview (' . ucfirst($filter) . ')', 0, 1, 'C');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 10, 'Total Orders: ' . $stats['total_orders'], 0, 1);
        $pdf->Cell(0, 10, 'Delivered Orders: ' . $stats['delivered'], 0, 1);
        $pdf->Cell(0, 10, 'Estimated Earnings: PHP ' . number_format($stats['total_earnings'], 2), 0, 1);

        $pdfData = $pdf->Output('S', 'KimFresh_Analytics.pdf');
        ob_end_clean();

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="KimFresh_Analytics.pdf"'); 
        header('Content-Length: ' . strlen($pdfData));
        echo $pdfData;
        exit;
    }

    public function exportCsv()
    {
        $filter = $_GET['filter'] ?? 'all';
        
        require_once __DIR__ . '/../Models/Analytics.php';
        $analyticsModel = new Analytics($this->conn);
        $stats = $analyticsModel->getStats($filter);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="KimFresh_Analytics.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Metric (' . ucfirst($filter) . ')', 'Value']);
        fputcsv($output, ['Total Orders', $stats['total_orders']]);
        fputcsv($output, ['Delivered Orders', $stats['delivered']]);
        fputcsv($output, ['Estimated Earnings', number_format($stats['total_earnings'], 2)]);
        
        fclose($output);
        exit;
    }
}
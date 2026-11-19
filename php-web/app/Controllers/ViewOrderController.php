<?php
// app/Controllers/ViewOrderController.php â€” strict MVC Controller
class ViewOrderController extends Controller
{
    private $model;

    private function boot()
    {
        require_once __DIR__ . '/../Models/Order.php';
        $this->model = new Order($this->conn);
    }

    public function show()
    {
        $this->requireAuth();
        $this->boot();

        $order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($order_id <= 0) {
            $this->redirect('/php-web/public/orders');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['approve_order'])) { $this->handleApprove($order_id); return; }
            if (isset($_POST['reject_order']))  { $this->handleReject($order_id); return; }
        }

        $order = $this->model->getById($order_id);
        if (!$order) { die("Order not found."); }

        $items = $this->model->getItems($order_id);
        $driver_name = $this->model->getDriverName($order_id) ?? 'No driver assigned yet';
        $proof = $this->model->getDeliveryProof($order_id);
        $sensor_data = $this->model->getSensorData($order_id);

        $this->view('orders/view', [
            'order_id'    => $order_id,
            'order'       => $order,
            'items'       => $items,
            'driver_name' => $driver_name,
            'proof'       => $proof,
            'sensor_data' => $sensor_data
        ]);
    }

    private function handleApprove($order_id)
    {
        if ($this->model->approveOrder($order_id)) {
            logActivity($this->conn, $_SESSION['admin_id'], "Approved Order #$order_id (from View)");
        }
        $this->redirect("/php-web/public/orders/view?id=$order_id");
    }

    private function handleReject($order_id)
    {
        if ($this->model->rejectOrder($order_id)) {
            logActivity($this->conn, $_SESSION['admin_id'], "Rejected Order #$order_id (from View)");
        }
        $this->redirect("/php-web/public/orders/view?id=$order_id");
    }

    public function exportPdf()
    {
        $order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $this->boot();
        $order = $this->model->getById($order_id);
        $items = $this->model->getItems($order_id);

        if (!$order) { die("Order not found."); }

        if (ob_get_length()) ob_end_clean();
        ob_start();

        require_once __DIR__ . '/../../lib/fpdf/fpdf.php';
        require_once __DIR__ . '/../helpers/KimFreshPdf.php';

        $pdf = new KimFreshPdf();
        $pdf->title = 'KimFresh Order Receipt #' . $order_id;
        $pdf->AddPage();

        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, 'Order #' . $order_id, 0, 1);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 8, 'Date: ' . date('M d, Y h:i A', strtotime($order['order_date'] ?? 'now')), 0, 1);
        $pdf->Cell(0, 8, 'Retailer: ' . ($order['retailer_name'] ?? 'Unknown'), 0, 1);
        $pdf->Cell(0, 8, 'Status: ' . strtoupper($order['order_status'] ?? 'PENDING'), 0, 1);
        $pdf->Cell(0, 8, 'Total Amount: PHP ' . number_format($order['total_amount'] ?? 0, 2), 0, 1);
        $pdf->Ln(5);

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor(195, 66, 0); $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(90, 8, 'Product', 1, 0, 'C', true);
        $pdf->Cell(30, 8, 'Qty', 1, 0, 'C', true);
        $pdf->Cell(40, 8, 'Subtotal', 1, 1, 'C', true);

        $pdf->SetFont('Arial', '', 10); $pdf->SetTextColor(0, 0, 0);
        if ($items && $items->num_rows > 0) {
            while ($item = $items->fetch_assoc()) {
                $price = $item['unit_price'] ?? $item['price'] ?? 0;
                $qty = $item['quantity'] ?? 1;
                $subtotal = $item['subtotal'] ?? ($price * $qty);
                $pdf->Cell(90, 8, substr(htmlspecialchars($item['product_name'] ?? $item['name'] ?? 'Unknown'), 0, 40), 1, 0, 'L');
                $pdf->Cell(30, 8, $qty, 1, 0, 'C');
                $pdf->Cell(40, 8, 'PHP ' . number_format($subtotal, 2), 1, 1, 'R');
            }
        } else {
            $pdf->Cell(160, 8, 'No items found.', 1, 1, 'C');
        }

        $pdfData = $pdf->Output('S', 'KimFresh_Order_'.$order_id.'.pdf');
        ob_end_clean();

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="KimFresh_Order_'.$order_id.'.pdf"');
        header('Content-Length: ' . strlen($pdfData));
        echo $pdfData;
        exit;
    }

    public function exportCsv()
    {
        $order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $this->boot();
        $order = $this->model->getById($order_id);
        $items = $this->model->getItems($order_id);

        if (!$order) { die("Order not found."); }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="KimFresh_Order_'.$order_id.'.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Order ID', $order_id]);
        fputcsv($output, ['Retailer', $order['retailer_name'] ?? 'Unknown']);
        fputcsv($output, ['Date', date('M d, Y h:i A', strtotime($order['order_date'] ?? 'now'))]);
        fputcsv($output, ['Total Amount', $order['total_amount'] ?? 0]);
        fputcsv($output, []);
        fputcsv($output, ['Product', 'Quantity', 'Subtotal']);

        if ($items && $items->num_rows > 0) {
            while ($item = $items->fetch_assoc()) {
                $price = $item['unit_price'] ?? $item['price'] ?? 0;
                $qty = $item['quantity'] ?? 1;
                $subtotal = $item['subtotal'] ?? ($price * $qty);
                fputcsv($output, [
                    $item['product_name'] ?? $item['name'] ?? 'Unknown',
                    $qty,
                    $subtotal
                ]);
            }
        }
        fclose($output);
        exit;
    }
}

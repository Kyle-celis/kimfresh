<?php
// app/Controllers/ViewOrderController.php — strict MVC Controller
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

        if (isset($_GET['export_pdf'])) { $this->handlePdfExport($order_id); return; }

        $order = $this->model->getById($order_id);
        if (!$order) { die("Order not found."); }

        $items       = $this->model->getItems($order_id);
        $sensor_data = $this->model->getSensorData($order_id);
        $driver_name = $this->model->getDriverName($order_id) ?? 'No driver assigned yet';

        $this->view('orders/view', [
            'order_id'    => $order_id,
            'order'       => $order,
            'items'       => $items,
            'sensor_data' => $sensor_data,
            'driver_name' => $driver_name,
        ]);
    }

    private function handleApprove($order_id)
    {
        $cur = $this->model->getById($order_id);
        if (!$cur || $cur['order_status'] !== 'pending') {
            $this->redirect("/php-web/public/orders/view?id=$order_id");
        }

        if ($this->model->approveOrder($order_id)) {
            $assigned = autoAssignDriver($this->conn, $order_id);
            assignWaitingOrders($this->conn);
            logActivity($this->conn, $_SESSION['admin_id'], $assigned ? "Approved Order #$order_id (from View) and Auto-Assigned to Driver #$assigned" : "Approved Order #$order_id (from View, No drivers)");
        } else {
            logActivity($this->conn, $_SESSION['admin_id'], "Approve FAILED Order #$order_id from View (Stock issue)");
            $this->redirect("/php-web/public/orders/view?id=$order_id&error=stock");
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

    private function handlePdfExport($order_id)
    {
        logActivity($this->conn, $_SESSION['admin_id'], "Exported Order #$order_id Receipt to PDF");

        require_once __DIR__ . '/../../lib/fpdf/fpdf.php';
        require_once __DIR__ . '/../helpers/KimFreshPdf.php';

        $order = $this->model->getById($order_id);
        $items = $this->model->getItems($order_id);
        $driver_name = $this->model->getDriverName($order_id) ?? 'No driver assigned yet';

        $pdf = new KimFreshPdf();
        $pdf->title = 'KimFresh Order Receipt';
        $pdf->AddPage();
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(0, 0, 0);

        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(0, 8, 'Retailer Information', 0, 1);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 6, 'Name: ' . htmlspecialchars($order['retailer_name']), 0, 1);
        $pdf->Cell(0, 6, 'Code: ' . htmlspecialchars($order['customer_code']), 0, 1);
        $pdf->Cell(0, 6, 'Address: ' . htmlspecialchars($order['address']), 0, 1);
        $pdf->Ln(5);

        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(0, 8, 'Order Information', 0, 1);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 6, 'Order ID: #' . $order_id, 0, 1);
        $pdf->Cell(0, 6, 'Date: ' . date('M d, Y h:i A', strtotime($order['order_date'])), 0, 1);
        $pdf->Cell(0, 6, 'Driver: ' . htmlspecialchars($driver_name), 0, 1);
        $pdf->Cell(0, 6, 'Status: ' . strtoupper(str_replace('_', ' ', $order['order_status'])), 0, 1);
        $pdf->Ln(5);

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor(195, 66, 0);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(80, 8, 'Product', 1, 0, 'L', true);
        $pdf->Cell(25, 8, 'Qty', 1, 0, 'C', true);
        $pdf->Cell(35, 8, 'Unit Price', 1, 0, 'R', true);
        $pdf->Cell(35, 8, 'Subtotal', 1, 1, 'R', true);
        
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(0, 0, 0);
        while($item = $items->fetch_assoc()) {
            $pdf->Cell(80, 8, htmlspecialchars($item['product_name']), 1, 0, 'L');
            $pdf->Cell(25, 8, $item['quantity'], 1, 0, 'C');
            $pdf->Cell(35, 8, 'PHP ' . number_format($item['unit_price'], 2), 1, 0, 'R');
            $pdf->Cell(35, 8, 'PHP ' . number_format($item['subtotal'], 2), 1, 1, 'R');
        }
        
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(140, 8, 'Total Amount', 1, 0, 'R');
        $pdf->Cell(35, 8, 'PHP ' . number_format($order['total_amount'], 2), 1, 1, 'R');

        $pdf->Output('D', 'KimFresh_Order_' . $order_id . '.pdf');
        exit();
    }
}
<?php
// app/Controllers/DriverController.php — strict MVC Controller
class DriverController extends Controller
{
    private $model;

    private function boot()
    {
        require_once __DIR__ . '/../Models/Driver.php';
        $this->model = new Driver($this->conn);
    }

    public function index()
    {
        // Prevent the browser from caching the page so auto-refresh pulls live data
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        $this->requireAuth();
        $this->boot();

        // --- POST handlers ---
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['toggle_status'])) { $this->handleToggleStatus(); return; }
            if (isset($_POST['assign_order']))  { $this->handleAssignOrder();  return; }
        }

        // --- PDF export ---
        if (isset($_GET['export_pdf'])) { $this->handlePdfExport(); return; }

        // --- Stats ---
        $stats = $this->model->getStats();

        // --- Filter & search ---
        $filter = $_GET['filter'] ?? 'all';
        $search = ''; // Search removed from view
        $where  = $this->model->buildWhereClause($filter, '');

        // --- Pagination ---
        $limit  = 6;
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $offset = ($page - 1) * $limit;

        $total_rows  = $this->model->count($where);
        $total_pages = (int)ceil($total_rows / $limit);

        $drivers     = $this->model->getAll($where, $limit, $offset);
        $unassigned  = $this->model->getUnassignedOrders();
        $histories   = $this->model->getDeliveryHistories();

        $this->view('drivers/index', [
            'total_drivers'     => $stats['total'],
            'available_drivers' => $stats['available'],
            'on_delivery'       => $stats['on_delivery'],
            'filter'            => $filter,
            'search'            => $search,
            'search'            => $search,
            'page'              => $page,
            'total_pages'       => $total_pages,
            'drivers'           => $drivers,
            'unassigned'        => $unassigned,
            'histories'         => $histories,
        ]);
    }

    private function handleToggleStatus()
    {
        $driver_id  = (int)$_POST['driver_id'];
        $new_status = $_POST['new_status'];

        // Check if driver has an active delivery before hiding them
        if ($new_status === 'hidden') {
            $activeQuery = $this->conn->query("SELECT COUNT(*) AS c FROM delivery WHERE driver_id = $driver_id AND delivery_status IN ('assigned', 'picked_up', 'in_transit')");
            if ((int)$activeQuery->fetch_assoc()['c'] > 0) {
                // We will use a URL parameter to show an error message
                $this->redirect('/php-web/public/drivers?error=active_order');
                return;
            }
        }

        $this->model->setStatus($driver_id, $new_status);

        if ($new_status === 'hidden') {
            logActivity($this->conn, $_SESSION['admin_id'], "Deactivated Driver #$driver_id");
        } elseif ($new_status === 'available') {
            logActivity($this->conn, $_SESSION['admin_id'], "Manually forced Driver #$driver_id to Available");
            assignWaitingOrders($this->conn);
        } else {
            logActivity($this->conn, $_SESSION['admin_id'], "Activated Driver #$driver_id (Set to Offline)");
        }

        $this->redirect('/php-web/public/drivers');
    }

    private function handleAssignOrder()
    {
        $order_id  = (int)$_POST['order_id'];
        $driver_id = (int)$_POST['driver_id'];

        if (manualAssignDriver($this->conn, $order_id, $driver_id)) {
            logActivity($this->conn, $_SESSION['admin_id'], "Manually assigned Order #$order_id to Driver #$driver_id");
        } else {
            logActivity($this->conn, $_SESSION['admin_id'], "Manual assign FAILED for Order #$order_id → Driver #$driver_id");
        }
        $this->redirect('/php-web/public/drivers');
    }

    private function handlePdfExport()
    {
        logActivity($this->conn, $_SESSION['admin_id'], "Exported Drivers to PDF");

        require_once __DIR__ . '/../../lib/fpdf/fpdf.php';
        require_once __DIR__ . '/../helpers/KimFreshPdf.php';

        $pdf = new KimFreshPdf();
        $pdf->title = 'KimFresh Driver Roster';
        $pdf->AddPage();
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(0, 0, 0);

        // Header row
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor(195, 66, 0);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(50, 8, 'Driver Name', 1, 0, 'C', true);
        $pdf->Cell(40, 8, 'License',     1, 0, 'C', true);
        $pdf->Cell(50, 8, 'Email',       1, 0, 'C', true);
        $pdf->Cell(30, 8, 'Status',      1, 1, 'C', true);

        // Data rows
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(0, 0, 0);
        $rows = $this->model->getAllForPdf();
        while ($row = $rows->fetch_assoc()) {
            $status = ($row['status'] === 'hidden') ? 'INACTIVE' : strtoupper($row['status']);
            $pdf->Cell(50, 8, htmlspecialchars($row['name']), 1, 0, 'L');
            $pdf->Cell(40, 8, htmlspecialchars($row['license_number'] ?? 'N/A'), 1, 0, 'L');
            $pdf->Cell(50, 8, htmlspecialchars($row['email'] ?? 'N/A'), 1, 0, 'L');
            $pdf->Cell(30, 8, $status, 1, 1, 'C');
        }
        $pdf->Output('D', 'KimFresh_Drivers.pdf');
        exit();
    }
}

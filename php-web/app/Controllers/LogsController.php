<?php
// app/Controllers/LogsController.php — strict MVC Controller
class LogsController extends Controller
{
    public function index()
    {
        $this->requireAuth();

        require_once __DIR__ . '/../Models/Log.php';
        $model = new Log($this->conn);

        $current_admin_id = (int)$_SESSION['admin_id'];
        $is_superadmin = !empty($_SESSION['is_superadmin']); // PATCH-2: use session flag, not hardcoded id

        // --- PDF export ---
        if (isset($_GET['export_pdf'])) {
            $this->handlePdfExport($model, $is_superadmin, $current_admin_id);
            return;
        }

        // --- Pagination ---
        $limit  = 10;
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $offset = ($page - 1) * $limit;

        $total_rows  = $model->count($is_superadmin, $current_admin_id);
        $total_pages = (int)ceil($total_rows / $limit);
        $logs        = $model->getAll($is_superadmin, $current_admin_id, $limit, $offset);

        $this->view('logs/index', [
            'logs'           => $logs,
            'page'           => $page,
            'total_pages'    => $total_pages,
            'total_rows'     => $total_rows,
            'is_superadmin'  => $is_superadmin,
        ]);
    }

    private function handlePdfExport($model, $is_superadmin, $current_admin_id)
    {
        logActivity($this->conn, $current_admin_id, "Exported Activity Logs to PDF");

        require_once __DIR__ . '/../../lib/fpdf/fpdf.php';
        require_once __DIR__ . '/../helpers/KimFreshPdf.php';

        $pdf = new KimFreshPdf();
        $pdf->title = 'KimFresh Activity Logs';
        $pdf->AddPage();
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(0, 0, 0);

        // Header
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor(195, 66, 0);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(50, 8, 'Admin Name', 1, 0, 'C', true);
        $pdf->Cell(80, 8, 'Action', 1, 0, 'C', true);
        $pdf->Cell(40, 8, 'Timestamp', 1, 1, 'C', true);

        // Data
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(0, 0, 0);
        $rows = $model->getAllForPdf($is_superadmin, $current_admin_id);
        while ($row = $rows->fetch_assoc()) {
            $admin = $row['admin_name'] ? htmlspecialchars($row['admin_name']) : 'System';
            $pdf->Cell(50, 8, substr($admin, 0, 30), 1, 0, 'L');
            $pdf->Cell(80, 8, substr(htmlspecialchars($row['action']), 0, 60), 1, 0, 'L');
            $pdf->Cell(40, 8, date('M d, Y h:i A', strtotime($row['timestamp'])), 1, 1, 'C');
        }

        $pdf->Output('D', 'KimFresh_Activity_Logs.pdf');
        exit();
    }
}
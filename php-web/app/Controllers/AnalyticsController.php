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
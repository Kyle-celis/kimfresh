<?php
// app/Controllers/AnalyticsController.php — strict MVC Controller
class AnalyticsController extends Controller
{
    public function index()
    {
        $this->requireAuth();

        require_once __DIR__ . '/../Models/Analytics.php';
        $model = new Analytics($this->conn);

        $top_products  = $model->getTopProducts(5);
        $top_drivers   = $model->getTopDrivers(5);
        $stats         = $model->getStats();

        $this->view('analytics/index', [
            'top_products'   => $top_products,
            'top_drivers'    => $top_drivers,
            'total_orders'   => $stats['total_orders'],
            'delivered'      => $stats['delivered'],
            'rejected'       => $stats['rejected'],
            'total_earnings' => $stats['total_earnings'],
        ]);
    }
}
<?php
// app/Controllers/DashboardController.php — strict MVC Controller
class DashboardController extends Controller
{
    public function index()
    {
        if (!isset($_SESSION['admin_id'])) {
            $this->redirect('/php-web/public/login');
        }

        require_once __DIR__ . '/../Models/Dashboard.php';
        $model = new Dashboard($this->conn);

        // --- Filtering ---
        $filter_type = $_GET['filter'] ?? 'weekly';
        $weekOffset  = min(0, (int)($_GET['week'] ?? 0));
        $monthOffset = min(0, (int)($_GET['month'] ?? 0));

        $currentMonday = strtotime('monday this week');
        $chartLabels   = [];
        $chartData     = [];

        if ($filter_type === 'monthly') {
            $monthStartTimestamp = strtotime("first day of $monthOffset month");
            $startDate   = date('Y-m-01', $monthStartTimestamp);
            $endDate     = date('Y-m-t',  $monthStartTimestamp);
            $dateFilter  = "DATE(order_date) BETWEEN '$startDate' AND '$endDate'";
            $dateLabel   = date("F Y", $monthStartTimestamp);
            $filterLabel = "Month";
            $startOfWeek = $currentMonday; // fallback for chart model
        } else {
            $startOfWeek = strtotime("$weekOffset week", $currentMonday);
            $endOfWeek   = strtotime("+6 days", $startOfWeek);
            $startDate   = date('Y-m-d', $startOfWeek);
            $endDate     = date('Y-m-d', $endOfWeek);
            $dateFilter  = "DATE(order_date) BETWEEN '$startDate' AND '$endDate'";
            $dateLabel   = date("M d", $startOfWeek) . ' - ' . date("M d, Y", $endOfWeek);
            $filterLabel = "Week";
        }

        // --- Sorting ---
        $sort = $_GET['sort'] ?? 'date_desc';
        $orderBy = "o.order_date DESC";
        $sortMap = [
            'status_asc'    => "o.order_status ASC",
            'status_desc'   => "o.order_status DESC",
            'retailer_asc'  => "r.name ASC",
            'retailer_desc' => "r.name DESC",
            'driver_asc'    => "dr.name ASC",
            'driver_desc'   => "dr.name DESC",
        ];
        if (isset($sortMap[$sort])) $orderBy = $sortMap[$sort];

        // --- Data ---
        $stats = $model->getStats($dateFilter);
        extract($stats); // $pending, $waiting, $delivered, $revenue, etc.

        $chart = $model->getChartData($filter_type, $startDate, $endDate, $startOfWeek);
        $chartLabels = $chart['labels'];
        $chartData   = $chart['data'];

        $ordersThisPeriod = $pending + $waiting + $waitingForPickup + $active + $delivered + $rejected;

        $activeDeliveries = $model->getActiveDeliveries($dateFilter);
        $lowStockList     = $model->getLowStock();

        // --- Pagination ---
        $limit = 5;
        $page  = max(1, (int)($_GET['page'] ?? 1));
        $offset = ($page - 1) * $limit;

        $countRecent = $model->countOrdersInPeriod($dateFilter);
        $totalPages  = (int)ceil($countRecent / $limit);
        $recentOrders = $model->getRecentOrders($dateFilter, $orderBy, $limit, $offset);

        // --- Render the view ---
        $this->view('dashboard/index', [
            'filter_type'       => $filter_type,
            'weekOffset'        => $weekOffset,
            'monthOffset'       => $monthOffset,
            'dateLabel'         => $dateLabel,
            'filterLabel'       => $filterLabel,
            'sort'              => $sort,
            'pending'           => $pending,
            'waiting'           => $waiting,
            'waitingForPickup'  => $waitingForPickup,
            'active'            => $active,
            'delivered'         => $delivered,
            'rejected'          => $rejected,
            'lowStock'          => $lowStock,
            'totalProducts'     => $totalProducts,
            'availableDrivers'  => $availableDrivers,
            'totalDrivers'      => $totalDrivers,
            'revenue'           => $revenue,
            'ordersThisPeriod'  => $ordersThisPeriod,
            'chartLabels'       => $chartLabels,
            'chartData'         => $chartData,
            'activeDeliveries'  => $activeDeliveries,
            'lowStockList'      => $lowStockList,
            'recentOrders'      => $recentOrders,
            'page'              => $page,
            'totalPages'        => $totalPages,
        ]);
    }
}

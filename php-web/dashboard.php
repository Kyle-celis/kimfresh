<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// --- DATE FILTERS (Daily, Weekly, Monthly) ---
$filter_type = isset($_GET['filter']) ? $_GET['filter'] : 'weekly'; 
$weekOffset = isset($_GET['week']) ? (int)$_GET['week'] : 0;
if ($weekOffset > 0) { $weekOffset = 0; }

$currentMonday = strtotime('monday this week');

if ($filter_type == 'daily') {
    $startDate = date('Y-m-d');
    $endDate = $startDate;
    $dateFilter = "DATE(order_date) = '$startDate'";
    $dateLabel = date("M d, Y", strtotime($startDate));
    $chartLabels = ["Today"];
} elseif ($filter_type == 'monthly') {
    $startDate = date('Y-m-01');
    $endDate = date('Y-m-t');
    $dateFilter = "DATE(order_date) BETWEEN '$startDate' AND '$endDate'";
    $dateLabel = date("F Y", strtotime($startDate));
    $chartLabels = ["Week 1", "Week 2", "Week 3", "Week 4", "Week 5"];
} else {
    $startOfWeek = strtotime("$weekOffset week", $currentMonday);
    $endOfWeek = strtotime("+6 days", $startOfWeek);
    $startDate = date('Y-m-d', $startOfWeek);
    $endDate = date('Y-m-d', $endOfWeek);
    $dateFilter = "DATE(order_date) BETWEEN '$startDate' AND '$endDate'";
    $dateLabel = date("M d", $startOfWeek) . ' - ' . date("M d, Y", $endOfWeek);
    $chartLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
}

// --- SORTING LOGIC ---
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'date_desc';
$orderBy = "o.order_date DESC"; // Default

if ($sort == 'status_asc') $orderBy = "o.order_status ASC";
if ($sort == 'status_desc') $orderBy = "o.order_status DESC";
if ($sort == 'retailer_asc') $orderBy = "r.name ASC";
if ($sort == 'retailer_desc') $orderBy = "r.name DESC";
if ($sort == 'driver_asc') $orderBy = "dr.name ASC";
if ($sort == 'driver_desc') $orderBy = "dr.name DESC";

// --- 1. TOP STATS (Filtered) ---
$pending = $conn->query("SELECT COUNT(*) AS c FROM `order` WHERE order_status = 'pending' AND $dateFilter")->fetch_assoc()['c'];
$waiting = $conn->query("SELECT COUNT(*) AS c FROM `order` o LEFT JOIN delivery d ON o.order_id = d.order_id WHERE o.order_status = 'approved' AND d.delivery_id IS NULL AND $dateFilter")->fetch_assoc()['c'];
$active = $conn->query("SELECT COUNT(*) AS c FROM `order` WHERE order_status IN ('waiting_for_pickup', 'in_transit') AND $dateFilter")->fetch_assoc()['c'];
$delivered = $conn->query("SELECT COUNT(*) AS c FROM `order` WHERE order_status = 'delivered' AND $dateFilter")->fetch_assoc()['c'];
$rejected = $conn->query("SELECT COUNT(*) AS c FROM `order` WHERE order_status = 'rejected' AND $dateFilter")->fetch_assoc()['c'];

$lowStock = $conn->query("SELECT COUNT(*) AS c FROM product WHERE status = 'available' AND stock_quantity <= reorder_level")->fetch_assoc()['c'];
$totalProducts = $conn->query("SELECT COUNT(*) AS c FROM product WHERE status = 'available'")->fetch_assoc()['c'];
$availableDrivers = $conn->query("SELECT COUNT(*) AS c FROM driver WHERE status = 'available'")->fetch_assoc()['c'];
$totalDrivers = $conn->query("SELECT COUNT(*) AS c FROM driver")->fetch_assoc()['c'];

$ordersThisPeriod = $pending + $waiting + $active + $delivered + $rejected;
$revenue = $conn->query("SELECT SUM(total_amount) AS s FROM `order` WHERE order_status = 'delivered' AND $dateFilter")->fetch_assoc()['s'] ?? 0;

// --- CHART DATA ---
$chartData = [];
if ($filter_type == 'daily') {
    $chartData[] = $ordersThisPeriod;
} elseif ($filter_type == 'monthly') {
    for ($i = 1; $i <= 5; $i++) {
        $wStart = date('Y-m-d', strtotime("$startDate +" . (($i-1)*7) . " days"));
        $wEnd = date('Y-m-d', strtotime("$wStart +6 days"));
        if ($wStart > $endDate) break;
        $count = $conn->query("SELECT COUNT(*) AS c FROM `order` WHERE DATE(order_date) BETWEEN '$wStart' AND '$wEnd'")->fetch_assoc()['c'];
        $chartData[] = $count;
    }
} else {
    for ($i = 0; $i < 7; $i++) {
        $date = date('Y-m-d', strtotime("+$i days", $startOfWeek));
        $count = $conn->query("SELECT COUNT(*) AS c FROM `order` WHERE DATE(order_date) = '$date'")->fetch_assoc()['c'];
        $chartData[] = $count;
    }
}

// --- DELIVERY MONITORING ---
$activeDeliveries = $conn->query("
    SELECT o.order_id, r.customer_code, r.address, o.order_status, dr.name AS driver_name 
    FROM `order` o
    LEFT JOIN retailer r ON o.retailer_id = r.retailer_id
    LEFT JOIN delivery d ON o.order_id = d.order_id
    LEFT JOIN driver dr ON d.driver_id = dr.driver_id
    WHERE o.order_status IN ('approved', 'waiting_for_pickup', 'in_transit') AND $dateFilter
    ORDER BY o.order_date DESC LIMIT 5
");

// --- INVENTORY ALERTS ---
$lowStockList = $conn->query("
    SELECT name, stock_quantity, reorder_level 
    FROM product 
    WHERE status = 'available' AND stock_quantity <= reorder_level 
    ORDER BY stock_quantity ASC LIMIT 5
");

// --- RECENT ORDERS ---
$recentOrders = $conn->query("
    SELECT o.order_id, r.name AS retailer_name, o.order_status, o.total_amount, dr.name AS driver_name,
    (SELECT SUM(quantity) FROM order_item WHERE order_id = o.order_id) as total_items
    FROM `order` o
    LEFT JOIN retailer r ON o.retailer_id = r.retailer_id
    LEFT JOIN delivery d ON o.order_id = d.order_id
    LEFT JOIN driver dr ON d.driver_id = dr.driver_id
    WHERE $dateFilter
    ORDER BY $orderBy
    LIMIT 6
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KimFresh - Dashboard (PHP)</title>
    
    <!-- FIXED CSS PATHS -->
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/flow.css">
    
    <style>
        :root { --primary: #C34200; --primary-dark: #9a3400; }
        .week-controls a, .week-controls span.btn { background: #C34200 !important; color: white !important; border-radius: 5px; }
        .week-controls a:hover { background: #9a3400 !important; }
        .filter-select { padding: 8px 12px; border-radius: 5px; border: 1px solid #C34200; font-weight: bold; color: #C34200; background: white; cursor: pointer; outline: none; }
        th a:hover { color: #C34200 !important; }
    </style>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-brand">
                <img src="../logo.png" alt="KimFresh" class="logo">
                <p>Administration</p>
            </div>
            <nav class="menu">
                <a href="dashboard.php" class="active">Dashboard</a>
                <a href="orders.php">Orders</a>
                <a href="products.php">Products</a>
                <a href="drivers.php">Drivers</a>
                <a href="settings.php">Settings</a> 
                <a href="logs.php">Activity Logs</a>
            </nav>
        </aside>

        <div class="main-area">
            <header class="top-header">
                <div class="profile-menu">
                    <div class="user-profile">
                        <div class="avatar">A</div>
                        <div class="user-info">
                            <p class="user-name"><?php echo $_SESSION['admin_name']; ?></p>
                            <p class="user-role">Administrator</p>
                        </div>
                        <a href="logout.php" class="logout-btn" style="margin-left: 15px; text-decoration: none;">Logout</a>
                    </div>
                </div>
            </header>

            <main class="main-content">
                <p class="overview-label">Overview</p>
                <h1>Distribution Dashboard</h1>

                <!-- 8 Cards -->
                <section class="cards-section">
                    <div class="card"><h3>Pending Orders</h3><div class="value"><?php echo $pending; ?></div><p class="hint">Waiting for approval</p></div>
                    <div class="card"><h3>Awaiting Driver</h3><div class="value"><?php echo $waiting; ?></div><p class="hint">Approved, no driver yet</p></div>
                    <div class="card"><h3>Active Deliveries</h3><div class="value"><?php echo $active; ?></div><p class="hint">Pickup + on the way</p></div>
                    <div class="card"><h3>Delivered</h3><div class="value"><?php echo $delivered; ?></div><p class="hint">₱<?php echo number_format($revenue, 2); ?> fulfilled</p></div>
                    <div class="card"><h3>Low / Out of Stock</h3><div class="value"><?php echo $lowStock; ?></div><p class="hint"><?php echo $totalProducts; ?> active products</p></div>
                    <div class="card"><h3>Available Drivers</h3><div class="value"><?php echo $availableDrivers; ?></div><p class="hint">of <?php echo $totalDrivers; ?> drivers</p></div>
                    <div class="card"><h3>Orders This <?php echo ucfirst(rtrim($filter_type, 'ly')); ?></h3><div class="value"><?php echo $ordersThisPeriod; ?></div><p class="hint"><?php echo $dateLabel; ?></p></div>
                    <div class="card"><h3>Rejected</h3><div class="value"><?php echo $rejected; ?></div><p class="hint">All time</p></div>
                </section>

                <!-- Chart with Week Controls -->
                <section class="analytics-section">
                    <div class="week-controls" style="display: flex; gap: 10px; align-items: center;">
                        
                        <select class="filter-select" onchange="window.location.href='?filter=' + this.value;">
                            <option value="daily" <?php echo $filter_type == 'daily' ? 'selected' : ''; ?>>Daily</option>
                            <option value="weekly" <?php echo $filter_type == 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                            <option value="monthly" <?php echo $filter_type == 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                        </select>

                        <?php if ($filter_type == 'weekly'): ?>
                            <a href="?filter=weekly&week=<?php echo $weekOffset - 1; ?>" class="btn" style="text-decoration:none; padding: 5px 10px;">&#8249;</a>
                        <?php endif; ?>

                        <h3>Orders Per <?php echo ucfirst(rtrim($filter_type, 'ly')); ?></h3>
                        
                        <?php if ($filter_type == 'weekly'): ?>
                            <?php if ($weekOffset >= 0): ?>
                                <span class="btn" style="text-decoration:none; padding: 5px 10px; opacity: 0.3; cursor: not-allowed;">&#8250;</span>
                            <?php else: ?>
                                <a href="?filter=weekly&week=<?php echo $weekOffset + 1; ?>" class="btn" style="text-decoration:none; padding: 5px 10px;">&#8250;</a>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <span class="week-range"><?php echo $dateLabel; ?></span>
                    </div>
                    
                    <div class="chart-box" style="height: 300px; position: relative;">
                        <canvas id="ordersChart"></canvas>
                    </div>
                </section>

                <!-- Recent Orders with Sorting -->
                <section class="table-section">
                    <h3>Recent Orders (<?php echo $dateLabel; ?>)</h3>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th><a href="?filter=<?php echo $filter_type; ?>&sort=retailer_asc" style="text-decoration:none; color:inherit;">Name ↕</a></th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th><a href="?filter=<?php echo $filter_type; ?>&sort=driver_asc" style="text-decoration:none; color:inherit;">Driver ↕</a></th>
                                    <th><a href="?filter=<?php echo $filter_type; ?>&sort=status_asc" style="text-decoration:none; color:inherit;">Status ↕</a></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($recentOrders->num_rows > 0) {
                                    while($row = $recentOrders->fetch_assoc()) {
                                        $driver = $row['driver_name'] ? htmlspecialchars($row['driver_name']) : '-';
                                        echo "<tr>";
                                        echo "<td><span class='order-no'>#" . $row["order_id"] . "</span></td>";
                                        echo "<td>" . htmlspecialchars($row["retailer_name"]) . "</td>";
                                        echo "<td>" . ($row["total_items"] ?? 0) . " items</td>";
                                        echo "<td>₱" . number_format($row["total_amount"], 2) . "</td>";
                                        echo "<td>" . $driver . "</td>";
                                        echo "<td><span class='status-pill " . strtolower($row["order_status"]) . "'>" . strtoupper($row["order_status"]) . "</span></td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='6' style='text-align:center;'>No orders found for this period.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Order Flow -->
                <section class="panel" style="margin-bottom: 24px;">
                    <div class="panel-head">
                        <h3>Order Flow</h3>
                    </div>
                    <div class="pipeline">
                        <a class="pipe-step <?php echo $pending > 0 ? 'attention' : ''; ?>" href="orders.php">
                            <strong><?php echo $pending; ?></strong><span>Pending approval</span>
                        </a>
                        <a class="pipe-step <?php echo $waiting > 0 ? 'attention' : ''; ?>" href="orders.php">
                            <strong><?php echo $waiting; ?></strong><span>Waiting for driver</span>
                        </a>
                        <a class="pipe-step" href="orders.php">
                            <strong>0</strong><span>In pickup</span>
                        </a>
                        <a class="pipe-step" href="orders.php">
                            <strong><?php echo $active; ?></strong><span>Out for delivery</span>
                        </a>
                        <a class="pipe-step" href="orders.php">
                            <strong><?php echo $delivered; ?></strong><span>Delivered</span>
                        </a>
                    </div>
                </section>

                <!-- Delivery Monitoring & Inventory Alerts -->
                <section class="two-col">
                    <div class="panel">
                        <div class="panel-head">
                            <h3>Delivery Monitoring</h3>
                            <a href="orders.php" class="panel-link">Drivers &rsaquo;</a>
                        </div>
                        <div id="deliveryList">
                            <?php
                            if ($activeDeliveries->num_rows > 0) {
                                while($row = $activeDeliveries->fetch_assoc()) {
                                    $statusLabel = strtoupper(str_replace('_', ' ', $row['order_status']));
                                    if ($row['order_status'] == 'approved') $statusLabel = 'WAITING FOR DRIVER';
                                    $driver = $row['driver_name'] ? htmlspecialchars($row['driver_name']) : 'No driver assigned';
                                    
                                    echo "<div class='delivery'>";
                                    echo "<div class='delivery-top'>";
                                    echo "<div><strong>Order #" . $row['order_id'] . " &middot; " . htmlspecialchars($row['customer_code']) . "</strong></div>";
                                    echo "<span class='status " . $row['order_status'] . "'>" . $statusLabel . "</span>";
                                    echo "</div>";
                                    echo "<p class='delivery-driver'>Driver: <strong>" . $driver . "</strong> &middot; " . htmlspecialchars($row['address']) . "</p>";
                                    echo "</div>";
                                }
                            } else {
                                echo "<p class='empty-note'>No deliveries in progress.</p>";
                            }
                            ?>
                        </div>
                    </div>

                    <div class="panel">
                        <div class="panel-head">
                            <h3>Inventory Alerts</h3>
                            <a href="products.php" class="panel-link">Products &rsaquo;</a>
                        </div>
                        <div id="stockList">
                            <?php
                            if ($lowStockList->num_rows > 0) {
                                while($row = $lowStockList->fetch_assoc()) {
                                    $isOut = $row['stock_quantity'] <= 0;
                                    $label = $isOut ? 'Out of Stock' : 'Low Stock';
                                    $css = $isOut ? 'out-of-stock' : 'low-stock';
                                    $percent = min(100, round(($row['stock_quantity'] / max(1, $row['reorder_level'])) * 100));
                                    
                                    echo "<div class='stock-row'>";
                                    echo "<div>";
                                    echo "<strong>" . htmlspecialchars($row['name']) . "</strong>";
                                    echo "<small>" . $row['stock_quantity'] . " left &middot; reorder at " . $row['reorder_level'] . "</small>";
                                    echo "<div class='stock-bar'><i style='width:" . $percent . "%'></i></div>";
                                    echo "</div>";
                                    echo "<span class='status " . $css . "'>" . $label . "</span>";
                                    echo "</div>";
                                }
                            } else {
                                echo "<p class='empty-note'>All products are well stocked.</p>";
                            }
                            ?>
                        </div>
                    </div>
                </section>

            </main>
        </div>
    </div>

    <!-- Chart Script -->
    <script>
        const ctx = document.getElementById('ordersChart').getContext('2d');
        const chartData = <?php echo json_encode($chartData); ?>;
        const chartLabels = <?php echo json_encode($chartLabels); ?>;
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartLabels,
                datasets: [{
                    label: 'Orders',
                    data: chartData,
                    borderColor: '#C34200',          
                    backgroundColor: 'rgba(195, 66, 0, 0.1)', 
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false, 
                plugins: { legend: { display: false } },
                scales: { 
                    y: { 
                        beginAtZero: true, 
                        ticks: { 
                            precision: 0,
                            font: { size: 12, family: 'Nunito Sans, Arial' } 
                        } 
                    },
                    x: {
                        ticks: {
                            font: { size: 12, family: 'Nunito Sans, Arial' } 
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
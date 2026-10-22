<?php
session_start();
require 'db.php';
require 'log_helper.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// --- HANDLE CSV EXPORT ---
if (isset($_POST['export_orders_csv'])) {
    logActivity($conn, $_SESSION['admin_id'], "Exported Orders to CSV");

    $filename = "KimFresh_Orders_" . date('Y-m-d') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fputcsv($output, array('Order ID', 'Retailer', 'Date', 'Items', 'Total', 'Status'));

    $export_query = $conn->query("
        SELECT o.order_id, r.name as retailer_name, o.order_date, o.total_amount, o.order_status,
        (SELECT SUM(quantity) FROM order_item WHERE order_id = o.order_id) as total_items
        FROM `order` o
        LEFT JOIN retailer r ON o.retailer_id = r.retailer_id
        ORDER BY o.order_date DESC
    ");

    while ($row = $export_query->fetch_assoc()) {
        fputcsv($output, array(
            '#' . $row['order_id'],
            $row['retailer_name'],
            date('M d, Y', strtotime($row['order_date'])),
            $row['total_items'] . ' items',
            'PHP ' . number_format($row['total_amount'], 2),
            strtoupper($row['order_status'])
        ));
    }
    fclose($output);
    exit();
}

// Handle Approve Action
if (isset($_POST['approve_order'])) {
    $order_id = $_POST['order_id'];
    $stmt = $conn->prepare("UPDATE `order` SET order_status = 'approved' WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    
    logActivity($conn, $_SESSION['admin_id'], "Approved Order #" . $order_id);
    
    $current_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
    header("Location: orders.php?status=" . $current_filter); 
    exit();
}

// --- SORTING LOGIC ---
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'date_desc';
$orderBy = "o.order_date DESC"; // Default

if ($sort == 'id_asc') $orderBy = "o.order_id ASC";
if ($sort == 'id_desc') $orderBy = "o.order_id DESC";
if ($sort == 'retailer_asc') $orderBy = "r.name ASC";
if ($sort == 'retailer_desc') $orderBy = "r.name DESC";
if ($sort == 'status_asc') $orderBy = "o.order_status ASC";
if ($sort == 'status_desc') $orderBy = "o.order_status DESC";
if ($sort == 'total_asc') $orderBy = "o.total_amount ASC";
if ($sort == 'total_desc') $orderBy = "o.total_amount DESC";

// Get counts
$counts = [
    'all' => $conn->query("SELECT COUNT(*) AS c FROM `order`")->fetch_assoc()['c'],
    'pending' => $conn->query("SELECT COUNT(*) AS c FROM `order` WHERE order_status = 'pending'")->fetch_assoc()['c'],
    'approved' => $conn->query("SELECT COUNT(*) AS c FROM `order` WHERE order_status = 'approved'")->fetch_assoc()['c'],
    'waiting_for_pickup' => $conn->query("SELECT COUNT(*) AS c FROM `order` WHERE order_status = 'waiting_for_pickup'")->fetch_assoc()['c'],
    'in_transit' => $conn->query("SELECT COUNT(*) AS c FROM `order` WHERE order_status = 'in_transit'")->fetch_assoc()['c'],
    'delivered' => $conn->query("SELECT COUNT(*) AS c FROM `order` WHERE order_status = 'delivered'")->fetch_assoc()['c'],
    'rejected' => $conn->query("SELECT COUNT(*) AS c FROM `order` WHERE order_status = 'rejected'")->fetch_assoc()['c']
];

$current_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

$sql = "
    SELECT o.order_id, r.name as retailer_name, r.customer_code, r.address, o.order_status, o.total_amount, o.order_date
    FROM `order` o
    LEFT JOIN retailer r ON o.retailer_id = r.retailer_id
    WHERE 1=1
";

if ($current_filter !== 'all') {
    $safe_filter = $conn->real_escape_string($current_filter);
    $sql .= " AND o.order_status = '$safe_filter' ";
}

$sql .= " ORDER BY $orderBy";
$orders = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>KimFresh - PHP Orders</title>
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="stylesheet" href="css/orders.css">
    <link rel="stylesheet" href="css/flow.css">
    <style>
        :root { --primary: #C34200; --primary-dark: #9a3400; }
        th a:hover { color: #C34200 !important; }

        /* PROFILE DROPDOWN STYLES */
        .profile-menu { position: relative; }
        .profile-trigger { display: flex; align-items: center; gap: 10px; background: transparent; border: none; cursor: pointer; padding: 8px; border-radius: 10px; transition: 0.2s; }
        .profile-trigger:hover { background: var(--primary-soft); }
        .profile-dropdown { position: absolute; top: 100%; right: 0; background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); width: 200px; padding: 8px; display: none; z-index: 100; border: 1px solid #eee; }
        .profile-dropdown.show { display: block; }
        .dropdown-account { padding: 12px; border-bottom: 1px solid #eee; margin-bottom: 8px; }
        .dropdown-account strong { display: block; font-size: 14px; color: #333; }
        .dropdown-account span { font-size: 12px; color: #777; }
        .dropdown-item { display: flex; align-items: center; gap: 10px; padding: 10px 12px; text-decoration: none; color: #333; font-size: 13px; font-weight: 600; border-radius: 8px; transition: 0.2s; }
        .dropdown-item:hover { background: #fdf0e6; color: #C34200; }

        /* EXPORT BUTTON */
        .btn-export { background: #C34200; color: white; padding: 10px 20px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.2s; }
        .btn-export:hover { background: #9a3400; }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-brand">
                <img src="logo.png" alt="KimFresh" class="logo">
                <p>Administration</p>
            </div>
            <nav class="menu">
                <a href="dashboard.php">Dashboard</a>
                <a href="orders.php" class="active">Orders</a>
                <a href="products.php">Products</a>
                <a href="drivers.php">Drivers</a>
                <?php if ($_SESSION['admin_id'] == 1): ?>
                    <a href="superadmin.php">Manage Admins</a>
                <?php endif; ?>
            </nav>
        </aside>

        <div class="main-area">
            <header class="top-header">
                <div class="profile-menu" id="profileMenu">
                    <button type="button" class="profile-trigger" onclick="toggleProfileMenu(event)">
                        <div class="avatar">A</div>
                        <div class="user-info" style="text-align: left;">
                            <p class="user-name" style="margin: 0;"><?php echo $_SESSION['admin_name']; ?></p>
                            <p class="user-role" style="margin: 0;">Administrator</p>
                        </div>
                        <span style="font-size: 18px; color: #999; margin-left: 5px;">⌄</span>
                    </button>
                    <div class="profile-dropdown" id="profileDropdown">
                        <div class="dropdown-account">
                            <strong><?php echo $_SESSION['admin_name']; ?></strong>
                            <span>admin@kimfresh.com</span>
                        </div>
                        <a href="settings.php" class="dropdown-item">Settings</a>
                        <a href="logs.php" class="dropdown-item">Activity Logs</a>
                        <a href="logout.php" class="dropdown-item" style="color: #C34200;">Logout</a>
                    </div>
                </div>
            </header>

            <main class="main-content">
                <div style="display:flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                    <div>
                        <p class="overview-label">Manage</p>
                        <h1 style="margin:0;">Orders</h1>
                        <p class="page-sub" style="margin-top:5px;">Review retailer orders. Approve or reject pending requests; delivery progress and cold-chain readings are monitored here.</p>
                    </div>
                    <form method="POST" style="margin:0;">
                        <button type="submit" name="export_orders_csv" class="btn-export">Export CSV</button>
                    </form>
                </div>

                <!-- STATUS TABS -->
                <div class="toolbar">
                    <div class="tabs">
                        <a href="?status=all&sort=<?php echo $sort; ?>" class="tab <?php echo $current_filter == 'all' ? 'active' : ''; ?>" style="text-decoration:none;">All <span><?php echo $counts['all']; ?></span></a>
                        <a href="?status=pending&sort=<?php echo $sort; ?>" class="tab <?php echo $current_filter == 'pending' ? 'active' : ''; ?>" style="text-decoration:none;">Pending <span><?php echo $counts['pending']; ?></span></a>
                        <a href="?status=approved&sort=<?php echo $sort; ?>" class="tab <?php echo $current_filter == 'approved' ? 'active' : ''; ?>" style="text-decoration:none;">Approved <span><?php echo $counts['approved']; ?></span></a>
                        <a href="?status=waiting_for_pickup&sort=<?php echo $sort; ?>" class="tab <?php echo $current_filter == 'waiting_for_pickup' ? 'active' : ''; ?>" style="text-decoration:none;">In Pickup <span><?php echo $counts['waiting_for_pickup']; ?></span></a>
                        <a href="?status=in_transit&sort=<?php echo $sort; ?>" class="tab <?php echo $current_filter == 'in_transit' ? 'active' : ''; ?>" style="text-decoration:none;">Out for Delivery <span><?php echo $counts['in_transit']; ?></span></a>
                        <a href="?status=delivered&sort=<?php echo $sort; ?>" class="tab <?php echo $current_filter == 'delivered' ? 'active' : ''; ?>" style="text-decoration:none;">Delivered <span><?php echo $counts['delivered']; ?></span></a>
                        <a href="?status=rejected&sort=<?php echo $sort; ?>" class="tab <?php echo $current_filter == 'rejected' ? 'active' : ''; ?>" style="text-decoration:none;">Rejected <span><?php echo $counts['rejected']; ?></span></a>
                    </div>
                </div>

                <div class="orders-box">
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th><a href="?status=<?php echo $current_filter; ?>&sort=<?php echo ($sort == 'id_asc' ? 'id_desc' : 'id_asc'); ?>" style="text-decoration:none; color:inherit;">Order ↕</a></th>
                                <th><a href="?status=<?php echo $current_filter; ?>&sort=<?php echo ($sort == 'retailer_asc' ? 'retailer_desc' : 'retailer_asc'); ?>" style="text-decoration:none; color:inherit;">Retailer ↕</a></th>
                                <th>Date</th>
                                <th>Items</th>
                                <th><a href="?status=<?php echo $current_filter; ?>&sort=<?php echo ($sort == 'total_asc' ? 'total_desc' : 'total_asc'); ?>" style="text-decoration:none; color:inherit;">Total ↕</a></th>
                                <th><a href="?status=<?php echo $current_filter; ?>&sort=<?php echo ($sort == 'status_asc' ? 'status_desc' : 'status_asc'); ?>" style="text-decoration:none; color:inherit;">Status ↕</a></th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($orders->num_rows > 0) {
                                while($row = $orders->fetch_assoc()) {
                                    $status = strtolower($row["order_status"]);
                                    $displayStatus = strtoupper(str_replace('_', ' ', $row["order_status"]));
                                    
                                    $item_query = $conn->query("SELECT SUM(quantity) as total FROM order_item WHERE order_id = " . $row['order_id']);
                                    $item_count = $item_query->fetch_assoc()['total'] ?? 0;
                                    
                                    echo "<tr>";
                                    echo "<td><span class='order-no'>#" . $row["order_id"] . "</span></td>";
                                    echo "<td><strong>" . htmlspecialchars($row["retailer_name"]) . "</strong></td>";
                                    echo "<td>" . date('M d, Y, g:i A', strtotime($row['order_date'])) . "</td>";
                                    echo "<td>" . $item_count . " items</td>";
                                    echo "<td class='num'>₱" . number_format($row["total_amount"], 2) . "</td>";
                                    echo "<td><span class='status-pill " . $status . "'>" . $displayStatus . "</span></td>";
                                    
                                    echo "<td>";
                                    if ($row["order_status"] == 'pending') {
                                        echo "<form method='POST' style='margin:0; display:inline-block;'>";
                                        echo "<input type='hidden' name='order_id' value='" . $row["order_id"] . "'>";
                                        echo "<button type='submit' name='approve_order' class='view-btn pending-btn' style='margin-right:5px;'>Approve</button>";
                                        echo "</form>";
                                    }
                                    echo "<a href='view_order.php?id=" . $row["order_id"] . "' class='view-btn' style='text-decoration:none;'>View</a>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='7' style='text-align:center; padding: 20px;'>No orders found for this filter.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <!-- CUSTOM CTRL+F FIND BAR -->
    <div id="customFindBar" style="display: none; position: fixed; top: 10px; right: 20px; background: white; padding: 10px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 9999; border: 1px solid #ddd;">
        <input type="text" id="findInput" placeholder="Find order..." style="padding: 8px; border: 1px solid #ccc; border-radius: 4px; outline: none; width: 200px;">
        <button id="closeFind" style="padding: 8px 12px; margin-left: 5px; background: #eee; border: none; border-radius: 4px; cursor: pointer;">X</button>
    </div>

    <script>
        function toggleProfileMenu(event) {
            event.stopPropagation();
            document.getElementById('profileDropdown').classList.toggle('show');
        }
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('profileMenu');
            if (!menu.contains(event.target)) {
                document.getElementById('profileDropdown').classList.remove('show');
            }
        });

        // --- CUSTOM CTRL+F INTERCEPTOR ---
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                e.preventDefault(); 
                const findBar = document.getElementById('customFindBar');
                findBar.style.display = 'flex';
                document.getElementById('findInput').focus();
            }
            if (e.key === 'Escape') {
                document.getElementById('customFindBar').style.display = 'none';
                document.getElementById('findInput').value = '';
                document.querySelectorAll('table tbody tr').forEach(row => row.style.display = '');
            }
        });

        document.getElementById('closeFind').addEventListener('click', function() {
            document.getElementById('customFindBar').style.display = 'none';
            document.getElementById('findInput').value = '';
            document.querySelectorAll('table tbody tr').forEach(row => row.style.display = '');
        });

        document.getElementById('findInput').addEventListener('keyup', function() {
            const query = this.value.toLowerCase();
            const rows = document.querySelectorAll('table tbody tr');
            rows.forEach(row => {
                if (row.cells.length < 2) return; 
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    </script>
</body>
</html>
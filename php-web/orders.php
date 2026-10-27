<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
date_default_timezone_set('Asia/Manila');
require 'db.php';
require 'log_helper.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// --- HANDLE BULK ACTIONS ---
if (isset($_POST['bulk_approve']) || isset($_POST['bulk_reject'])) {
    if (!empty($_POST['order_ids'])) {
        $is_approve = isset($_POST['bulk_approve']);
        
        foreach ($_POST['order_ids'] as $order_id) {
            $order_id = (int)$order_id;
            
            if ($is_approve) {
                $stmt = $conn->prepare("UPDATE `order` SET order_status = 'approved' WHERE order_id = ? AND order_status = 'pending'");
                $stmt->bind_param("i", $order_id);
                $stmt->execute();
                
                if ($stmt->affected_rows > 0) {
                    $assigned_driver = autoAssignDriver($conn, $order_id);
                    if ($assigned_driver) {
                        logActivity($conn, $_SESSION['admin_id'], "Bulk Approved Order #$order_id (Driver #$assigned_driver)");
                    } else {
                        logActivity($conn, $_SESSION['admin_id'], "Bulk Approved Order #$order_id (No drivers)");
                    }
                }
            } else {
                $item_query = $conn->prepare("SELECT product_id, quantity FROM order_item WHERE order_id = ?");
                $item_query->bind_param("i", $order_id);
                $item_query->execute();
                $items_result = $item_query->get_result();
                
                while ($item = $items_result->fetch_assoc()) {
                    $update_stock = $conn->prepare("UPDATE product SET stock_quantity = stock_quantity + ? WHERE product_id = ?");
                    $update_stock->bind_param("ii", $item['quantity'], $item['product_id']);
                    $update_stock->execute();
                }
                
                $stmt = $conn->prepare("UPDATE `order` SET order_status = 'rejected' WHERE order_id = ? AND order_status = 'pending'");
                $stmt->bind_param("i", $order_id);
                $stmt->execute();
                logActivity($conn, $_SESSION['admin_id'], "Bulk Rejected Order #$order_id and refunded stock");
            }
        }
    }
    header("Location: orders.php"); 
    exit();
}

// --- HANDLE SINGLE ACTIONS ---
if (isset($_POST['approve_order']) || isset($_POST['reject_order'])) {
    $order_id = $_POST['order_id'];
    
    if (isset($_POST['approve_order'])) {
        $stmt = $conn->prepare("UPDATE `order` SET order_status = 'approved' WHERE order_id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        autoAssignDriver($conn, $order_id);
        logActivity($conn, $_SESSION['admin_id'], "Approved Order #$order_id");
    } else {
        $item_query = $conn->prepare("SELECT product_id, quantity FROM order_item WHERE order_id = ?");
        $item_query->bind_param("i", $order_id);
        $item_query->execute();
        $items_result = $item_query->get_result();
        while ($item = $items_result->fetch_assoc()) {
            $update_stock = $conn->prepare("UPDATE product SET stock_quantity = stock_quantity + ? WHERE product_id = ?");
            $update_stock->bind_param("ii", $item['quantity'], $item['product_id']);
            $update_stock->execute();
        }
        $stmt = $conn->prepare("UPDATE `order` SET order_status = 'rejected' WHERE order_id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        logActivity($conn, $_SESSION['admin_id'], "Rejected Order #$order_id and refunded stock");
    }
    header("Location: orders.php"); 
    exit();
}

// --- SORTING & FILTERING ---
$current_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'date_desc';
$orderBy = "o.order_date DESC";

if ($sort == 'id_asc') $orderBy = "o.order_id ASC";
if ($sort == 'id_desc') $orderBy = "o.order_id DESC";
if ($sort == 'retailer_asc') $orderBy = "r.name ASC";
if ($sort == 'total_asc') $orderBy = "o.total_amount ASC";
if ($sort == 'total_desc') $orderBy = "o.total_amount DESC";

$counts = [
    'all' => $conn->query("SELECT COUNT(*) AS c FROM `order`")->fetch_assoc()['c'],
    'pending' => $conn->query("SELECT COUNT(*) AS c FROM `order` WHERE order_status = 'pending'")->fetch_assoc()['c'],
    'approved' => $conn->query("SELECT COUNT(*) AS c FROM `order` WHERE order_status = 'approved'")->fetch_assoc()['c'],
    'delivered' => $conn->query("SELECT COUNT(*) AS c FROM `order` WHERE order_status = 'delivered'")->fetch_assoc()['c'],
    'rejected' => $conn->query("SELECT COUNT(*) AS c FROM `order` WHERE order_status = 'rejected'")->fetch_assoc()['c']
];

// --- PAGINATION ---
$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$baseSql = "FROM `order` o LEFT JOIN retailer r ON o.retailer_id = r.retailer_id WHERE 1=1";

if ($current_filter !== 'all') {
    $safe_filter = $conn->real_escape_string($current_filter);
    $baseSql .= " AND o.order_status = '$safe_filter' ";
}

$countQuery = $conn->query("SELECT COUNT(*) AS total $baseSql");
$total_rows = $countQuery->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

$sql = "SELECT o.order_id, r.name as retailer_name, o.order_status, o.total_amount, o.order_date
        $baseSql ORDER BY $orderBy LIMIT $limit OFFSET $offset";
$orders = $conn->query($sql);

$show_checkboxes = ($current_filter == 'all' || $current_filter == 'pending') && $counts['pending'] > 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>KimFresh - Orders</title>
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="stylesheet" href="css/orders.css">
    <style>
        :root { --primary: #C34200; }
        .profile-menu { position: relative; }
        .profile-trigger { display: flex; align-items: center; gap: 10px; background: transparent; border: none; cursor: pointer; padding: 8px; border-radius: 10px; transition: 0.2s; }
        .profile-trigger:hover { background: var(--primary-soft); }
        .profile-dropdown { position: absolute; top: 100%; right: 0; background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); width: 200px; padding: 8px; display: none; z-index: 100; border: 1px solid #eee; }
        .profile-dropdown.show { display: block; }
        .dropdown-account { padding: 12px; border-bottom: 1px solid #eee; margin-bottom: 8px; }
        .dropdown-item { display: flex; align-items: center; gap: 10px; padding: 10px 12px; text-decoration: none; color: #333; font-size: 13px; font-weight: 600; border-radius: 8px; transition: 0.2s; }
        .dropdown-item:hover { background: #fdf0e6; color: #C34200; }
        
        .btn-export { background: #C34200; color: white; padding: 10px 20px; border: none; border-radius: 8px; font-weight: bold; text-decoration: none; display: inline-block; }
        
        .action-group { display: flex; gap: 6px; justify-content: flex-end; align-items: center; }
        .btn-approve { background: #15803d; color: white; padding: 7px 14px; font-size: 12px; font-weight: 700; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; }
        .btn-reject { background: #dc2626; color: white; padding: 7px 14px; font-size: 12px; font-weight: 700; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; }
        .btn-view { background: #fff; color: #333; padding: 7px 14px; font-size: 12px; font-weight: 700; border: 1px solid #ddd; border-radius: 6px; cursor: pointer; text-decoration: none; }

        .pagination { display: flex; justify-content: center; gap: 6px; margin-top: 24px; }
        .pagination a, .pagination span { display: inline-flex; align-items: center; justify-content: center; min-width: 36px; height: 36px; padding: 0 12px; border-radius: 8px; font-size: 13px; font-weight: 700; text-decoration: none; border: 1px solid #ddd; color: #555; background: white; }
        .pagination .active { background: #C34200; border-color: #C34200; color: white; }
        
        #bulk-action-bar { display: none; background: #f0fdf4; border: 1px solid #bbf7d0; padding: 12px 20px; border-radius: 8px; margin-bottom: 15px; justify-content: space-between; align-items: center; }
        .bulk-checkbox { width: 18px; height: 18px; cursor: pointer; accent-color: #15803d; }
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
                            <span><?php echo $_SESSION['admin_email'] ?? 'admin@kimfresh.com'; ?></span>
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
                        <p class="page-sub" style="margin-top:5px;">Review retailer orders. Approve or reject pending requests.</p>
                    </div>
                </div>

                <div class="toolbar">
                    <div class="tabs">
                        <a href="?status=all" class="tab <?php echo $current_filter == 'all' ? 'active' : ''; ?>">All <span><?php echo $counts['all']; ?></span></a>
                        <a href="?status=pending" class="tab <?php echo $current_filter == 'pending' ? 'active' : ''; ?>">Pending <span><?php echo $counts['pending']; ?></span></a>
                        <a href="?status=approved" class="tab <?php echo $current_filter == 'approved' ? 'active' : ''; ?>">Approved <span><?php echo $counts['approved']; ?></span></a>
                        <a href="?status=delivered" class="tab <?php echo $current_filter == 'delivered' ? 'active' : ''; ?>">Delivered <span><?php echo $counts['delivered']; ?></span></a>
                        <a href="?status=rejected" class="tab <?php echo $current_filter == 'rejected' ? 'active' : ''; ?>">Rejected <span><?php echo $counts['rejected']; ?></span></a>
                    </div>
                </div>

                <?php if ($show_checkboxes): ?>
                <div id="bulk-action-bar">
                    <span style="font-weight: 600; color: #166534;"><span id="selected-count">0</span> orders selected</span>
                    <div style="display: flex; gap: 8px;">
                        <button type="submit" form="bulk-form" name="bulk_reject" class="btn-reject" onclick="return confirm('Reject selected orders and refund stock?');" style="padding: 10px 20px; font-size: 13px;">Reject Selected</button>
                        <button type="submit" form="bulk-form" name="bulk_approve" class="btn-approve" onclick="return confirm('Approve selected orders and auto-assign drivers?');" style="padding: 10px 20px; font-size: 13px;">Approve Selected</button>
                    </div>
                </div>
                <?php endif; ?>

                <form id="bulk-form" method="POST">
                    <div class="orders-box">
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <?php if ($show_checkboxes): ?>
                                    <th style="width: 40px; padding-left: 15px;">
                                        <input type="checkbox" id="selectAll" class="bulk-checkbox" title="Select All Pending">
                                    </th>
                                    <?php endif; ?>
                                    <th>Order</th>
                                    <th>Retailer</th>
                                    <th>Date</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th style="text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($orders->num_rows > 0) {
                                    while($row = $orders->fetch_assoc()) {
                                        $status = strtolower($row["order_status"]);
                                        $displayStatus = strtoupper(str_replace('_', ' ', $row["order_status"]));
                                        
                                        echo "<tr>";
                                        
                                        if ($show_checkboxes) {
                                            echo "<td style='padding-left: 15px;'>";
                                            if ($row["order_status"] == 'pending') {
                                                echo "<input type='checkbox' name='order_ids[]' value='" . $row["order_id"] . "' class='bulk-checkbox order-checkbox'>";
                                            }
                                            echo "</td>";
                                        }

                                        echo "<td><span class='order-no'>#" . $row["order_id"] . "</span></td>";
                                        echo "<td><strong>" . htmlspecialchars($row["retailer_name"]) . "</strong></td>";
                                        echo "<td>" . date('M d, Y g:i A', strtotime($row['order_date'])) . "</td>";
                                        echo "<td class='num'>₱" . number_format($row["total_amount"], 2) . "</td>";
                                        echo "<td><span class='status-pill " . $status . "'>" . $displayStatus . "</span></td>";
                                        
                                        echo "<td>";
                                        echo "<div class='action-group'>";
                                        
                                        if ($row["order_status"] == 'pending') {
                                            echo "<button type='submit' name='reject_order' value='1' formaction='orders.php' class='btn-reject' onclick='this.form.insertAdjacentHTML(\"beforeend\", \"<input type=\\\"hidden\\\" name=\\\"order_id\\\" value=\\\"".$row["order_id"]."\\\">\"); return confirm(\"Reject this order?\");'>Reject</button>";
                                            echo "<button type='submit' name='approve_order' value='1' formaction='orders.php' class='btn-approve' onclick='this.form.insertAdjacentHTML(\"beforeend\", \"<input type=\\\"hidden\\\" name=\\\"order_id\\\" value=\\\"".$row["order_id"]."\\\">\");'>Approve</button>";
                                        }
                                        
                                        echo "<a href='view_order.php?id=" . $row["order_id"] . "' class='btn-view'>View</a>";
                                        echo "</div>";
                                        echo "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    $colspan = $show_checkboxes ? 7 : 6;
                                    echo "<tr><td colspan='$colspan' style='text-align:center; padding: 20px;'>No orders found.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </form>

                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?status=<?php echo $current_filter; ?>&page=<?php echo $page - 1; ?>">Prev</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?status=<?php echo $current_filter; ?>&page=<?php echo $i; ?>" class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?status=<?php echo $current_filter; ?>&page=<?php echo $page + 1; ?>">Next</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

            </main>
        </div>
    </div>

    <script>
        const selectAllCheckbox = document.getElementById('selectAll');
        const orderCheckboxes = document.querySelectorAll('.order-checkbox');
        const bulkActionBar = document.getElementById('bulk-action-bar');
        const selectedCount = document.getElementById('selected-count');

        function updateBulkBar() {
            if (!selectedCount || !bulkActionBar) return;
            const checkedCount = document.querySelectorAll('.order-checkbox:checked').length;
            selectedCount.textContent = checkedCount;
            bulkActionBar.style.display = checkedCount > 0 ? 'flex' : 'none';
        }

        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                orderCheckboxes.forEach(cb => cb.checked = this.checked);
                updateBulkBar();
            });
        }

        orderCheckboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                if (!this.checked && selectAllCheckbox) selectAllCheckbox.checked = false;
                const allChecked = document.querySelectorAll('.order-checkbox:checked').length === orderCheckboxes.length;
                if (selectAllCheckbox) selectAllCheckbox.checked = allChecked;
                updateBulkBar();
            });
        });

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
    </script>
    <!-- SECURITY: Force reload on back button & hide body on leave -->
    <script>
        window.addEventListener('pageshow', function (event) {
            if (event.persisted || (typeof window.performance != "undefined" && window.performance.navigation.type === 2)) {
                window.location.reload();
            }
        });
        window.addEventListener('pagehide', function (event) {
            document.body.style.display = 'none';
        });
    </script>
</body>
</html>

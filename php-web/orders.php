<?php
session_start();
require 'db.php';
require 'log_helper.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// --- HANDLE APPROVE ACTION (WITH AUTO-ASSIGN) ---
if (isset($_POST['approve_order'])) {
    $order_id = $_POST['order_id'];
    
    // 1. Update order status to approved
    $stmt = $conn->prepare("UPDATE `order` SET order_status = 'approved' WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    
    // 2. Call the auto-assign function from db.php
    $assigned_driver = autoAssignDriver($conn, $order_id);
    
    if ($assigned_driver) {
        logActivity($conn, $_SESSION['admin_id'], "Approved Order #$order_id and Auto-Assigned to Driver #$assigned_driver");
    } else {
        logActivity($conn, $_SESSION['admin_id'], "Approved Order #$order_id (No drivers available)");
    }
    
    header("Location: orders.php?status=" . (isset($_GET['status']) ? $_GET['status'] : 'all')); 
    exit();
}

// --- HANDLE REJECT ACTION ---
if (isset($_POST['reject_order'])) {
    $order_id = $_POST['order_id'];
    $stmt = $conn->prepare("UPDATE `order` SET order_status = 'rejected' WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    
    logActivity($conn, $_SESSION['admin_id'], "Rejected Order #" . $order_id);
    
    header("Location: orders.php?status=" . (isset($_GET['status']) ? $_GET['status'] : 'all')); 
    exit();
}

// --- HANDLE PDF EXPORT ---
if (isset($_GET['export_pdf'])) {
    logActivity($conn, $_SESSION['admin_id'], "Exported Orders to PDF");
    
    $print_query = $conn->query("
        SELECT o.order_id, r.name as retailer_name, r.address, o.order_date, o.total_amount, o.order_status,
        (SELECT SUM(quantity) FROM order_item WHERE order_id = o.order_id) as total_items
        FROM `order` o
        LEFT JOIN retailer r ON o.retailer_id = r.retailer_id
        ORDER BY o.order_date DESC
    ");
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>KimFresh - Orders Report</title>
        <style>
            body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #333; margin: 0; padding: 40px; }
            .header { text-align: center; margin-bottom: 40px; border-bottom: 2px solid #C34200; padding-bottom: 20px; }
            .header h1 { margin: 0; color: #C34200; font-size: 28px; }
            .header p { margin: 5px 0 0; color: #666; font-size: 14px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th { background: #fdf0e6; color: #C34200; padding: 12px; text-align: left; font-size: 12px; text-transform: uppercase; border-bottom: 2px solid #C34200; }
            td { padding: 12px; border-bottom: 1px solid #eee; font-size: 13px; }
            .num { text-align: right; }
            .footer { margin-top: 40px; text-align: center; font-size: 11px; color: #999; }
            @media print { body { padding: 0; } .no-print { display: none; } }
        </style>
    </head>
    <body onload="window.print();">
        <div class="header">
            <h1>KimFresh Orders Report</h1>
            <p>Generated on <?php echo date('F j, Y, g:i A'); ?></p>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Retailer</th>
                    <th>Date</th>
                    <th>Items</th>
                    <th class="num">Total</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $print_query->fetch_assoc()): ?>
                <tr>
                    <td>#<?php echo $row['order_id']; ?></td>
                    <td><?php echo htmlspecialchars($row['retailer_name']); ?></td>
                    <td><?php echo date('M d, Y', strtotime($row['order_date'])); ?></td>
                    <td><?php echo $row['total_items'] ?? 0; ?> items</td>
                    <td class="num">₱<?php echo number_format($row['total_amount'], 2); ?></td>
                    <td><?php echo strtoupper($row['order_status']); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <div class="footer">KimFresh IoT System - Confidential Report</div>
    </body>
    </html>
    <?php
    exit();
}

// --- SORTING LOGIC ---
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'date_desc';
$orderBy = "o.order_date DESC";
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
    'delivered' => $conn->query("SELECT COUNT(*) AS c FROM `order` WHERE order_status = 'delivered'")->fetch_assoc()['c'],
    'rejected' => $conn->query("SELECT COUNT(*) AS c FROM `order` WHERE order_status = 'rejected'")->fetch_assoc()['c']
];

$current_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// --- PAGINATION LOGIC ---
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

$sql = "SELECT o.order_id, r.name as retailer_name, r.customer_code, r.address, o.order_status, o.total_amount, o.order_date
        $baseSql ORDER BY $orderBy LIMIT $limit OFFSET $offset";
$orders = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KimFresh - Orders</title>
    
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/flow.css">
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="stylesheet" href="css/orders.css">
    
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

        .btn-export { background: #C34200; color: white; padding: 10px 20px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.2s; text-decoration: none; display: inline-block; }
        .btn-export:hover { background: #9a3400; }

        /* --- ACTION BUTTON STYLES --- */
        .action-group { display: flex; gap: 6px; justify-content: flex-end; align-items: center; }
        .btn-approve { background: #15803d; color: white; padding: 7px 14px; font-size: 12px; font-weight: 700; border: none; border-radius: 6px; cursor: pointer; transition: 0.2s; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; }
        .btn-approve:hover { background: #166534; }
        .btn-reject { background: #dc2626; color: white; padding: 7px 14px; font-size: 12px; font-weight: 700; border: none; border-radius: 6px; cursor: pointer; transition: 0.2s; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; }
        .btn-reject:hover { background: #b91c1c; }
        .btn-view { background: #fff; color: #333; padding: 7px 14px; font-size: 12px; font-weight: 700; border: 1px solid #ddd; border-radius: 6px; cursor: pointer; transition: 0.2s; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; }
        .btn-view:hover { background: #f8f9fa; border-color: #C34200; color: #C34200; }

        .pagination { display: flex; justify-content: center; gap: 6px; margin-top: 24px; }
        .pagination a, .pagination span {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 36px; height: 36px; padding: 0 12px;
            border-radius: 8px; font-size: 13px; font-weight: 700;
            text-decoration: none; border: 1px solid #ddd; color: #555; background: white;
            transition: 0.2s;
        }
        .pagination a:hover { background: #fdf0e6; border-color: #C34200; color: #C34200; }
        .pagination .active { background: #C34200; border-color: #C34200; color: white; }
        .pagination .disabled { opacity: 0.5; pointer-events: none; }

        /* =========================================
           MOBILE CARD LAYOUT (Direct Override)
           ========================================= */
        @media (max-width: 768px) {
            .orders-box, 
            .orders-box.table-responsive {
                overflow-x: hidden !important;
                width: 100% !important;
                border-radius: 0 !important;
                border: none !important;
                background: transparent !important;
                box-shadow: none !important;
            }

            .orders-table thead {
                display: none !important;
            }

            .orders-table, 
            .orders-table tbody, 
            .orders-table tr, 
            .orders-table td {
                display: block !important;
                width: 100% !important;
                min-width: 0 !important;
            }

            .orders-table tr {
                background: #fff !important;
                border: 1px solid #eee !important;
                border-radius: 12px !important;
                padding: 14px !important;
                margin-bottom: 16px !important;
                box-shadow: 0 2px 6px rgba(0,0,0,0.04) !important;
            }

            .orders-table td {
                display: flex !important;
                justify-content: space-between !important;
                align-items: center !important;
                padding: 8px 0 !important;
                border: none !important;
                border-bottom: 1px dashed #f0f0f0 !important;
                text-align: right !important;
                font-size: 13px !important;
            }

            .orders-table td:last-child {
                border-bottom: none !important;
                padding-top: 12px !important;
            }

            .orders-table td::before {
                content: attr(data-label);
                font-weight: 700 !important;
                color: #777 !important;
                text-transform: uppercase !important;
                font-size: 10px !important;
                letter-spacing: 0.5px !important;
                text-align: left !important;
                margin-right: 12px !important;
            }

            .orders-table td[data-label="Order"] {
                font-size: 15px !important;
            }

            .orders-table td[data-label="Order"] .order-no {
                font-size: 15px !important;
                font-weight: 800 !important;
                color: #C34200 !important;
            }

            .orders-table td[data-label="Action"] {
                display: block !important;
                text-align: left !important;
            }

            .orders-table td[data-label="Action"]::before {
                display: block !important;
                margin-bottom: 8px !important;
            }

            .orders-table td[data-label="Action"] .action-group {
                justify-content: flex-start !important;
            }

            .orders-table td[colspan] {
                display: block !important;
                text-align: center !important;
            }

            .orders-table td[colspan]::before {
                display: none !important;
            }
        }
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
                        </div>
                        <a href="settings.php" class="dropdown-item">Settings</a>
                        <a href="logs.php" class="dropdown-item">Activity Logs</a>
                        <a href="logout.php" class="dropdown-item" style="color: #C34200;">Logout</a>
                    </div>
                </div>
            </header>

            <main class="main-content">
                <div class="page-head" style="display:flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                    <div>
                        <p class="overview-label">Manage</p>
                        <h1 style="margin:0;">Orders</h1>
                        <p class="page-sub" style="margin-top:5px;">Review retailer orders. Approve or reject pending requests.</p>
                    </div>
                    <a href="?export_pdf=1" target="_blank" class="btn-export">Export PDF</a>
                </div>

                <div class="toolbar">
                    <div class="tabs">
                        <a href="?status=all&sort=<?php echo $sort; ?>" class="tab <?php echo $current_filter == 'all' ? 'active' : ''; ?>" style="text-decoration:none;">All <span><?php echo $counts['all']; ?></span></a>
                        <a href="?status=pending&sort=<?php echo $sort; ?>" class="tab <?php echo $current_filter == 'pending' ? 'active' : ''; ?>" style="text-decoration:none;">Pending <span><?php echo $counts['pending']; ?></span></a>
                        <a href="?status=approved&sort=<?php echo $sort; ?>" class="tab <?php echo $current_filter == 'approved' ? 'active' : ''; ?>" style="text-decoration:none;">Approved <span><?php echo $counts['approved']; ?></span></a>
                        <a href="?status=waiting_for_pickup&sort=<?php echo $sort; ?>" class="tab <?php echo $current_filter == 'waiting_for_pickup' ? 'active' : ''; ?>" style="text-decoration:none;">In Pickup <span><?php echo $counts['waiting_for_pickup']; ?></span></a>
                        <a href="?status=delivered&sort=<?php echo $sort; ?>" class="tab <?php echo $current_filter == 'delivered' ? 'active' : ''; ?>" style="text-decoration:none;">Delivered <span><?php echo $counts['delivered']; ?></span></a>
                        <a href="?status=rejected&sort=<?php echo $sort; ?>" class="tab <?php echo $current_filter == 'rejected' ? 'active' : ''; ?>" style="text-decoration:none;">Rejected <span><?php echo $counts['rejected']; ?></span></a>
                    </div>
                </div>

                <div class="orders-box table-responsive">
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th><a href="?status=<?php echo $current_filter; ?>&sort=<?php echo ($sort == 'id_asc' ? 'id_desc' : 'id_asc'); ?>" style="text-decoration:none; color:inherit;">Order ↕</a></th>
                                <th><a href="?status=<?php echo $current_filter; ?>&sort=<?php echo ($sort == 'retailer_asc' ? 'retailer_desc' : 'retailer_asc'); ?>" style="text-decoration:none; color:inherit;">Retailer ↕</a></th>
                                <th>Date</th>
                                <th>Items</th>
                                <th><a href="?status=<?php echo $current_filter; ?>&sort=<?php echo ($sort == 'total_asc' ? 'total_desc' : 'total_asc'); ?>" style="text-decoration:none; color:inherit;">Total ↕</a></th>
                                <th><a href="?status=<?php echo $current_filter; ?>&sort=<?php echo ($sort == 'status_asc' ? 'status_desc' : 'status_asc'); ?>" style="text-decoration:none; color:inherit;">Status ↕</a></th>
                                <th style="text-align: right;">Action</th>
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
                                    echo "<td data-label='Order'><span class='order-no'>#" . $row["order_id"] . "</span></td>";
                                    echo "<td data-label='Retailer'><strong>" . htmlspecialchars($row["retailer_name"]) . "</strong></td>";
                                    echo "<td data-label='Date'>" . date('M d, Y, g:i A', strtotime($row['order_date'])) . "</td>";
                                    echo "<td data-label='Items'>" . $item_count . " items</td>";
                                    echo "<td data-label='Total' class='num'>₱" . number_format($row["total_amount"], 2) . "</td>";
                                    echo "<td data-label='Status'><span class='status-pill " . $status . "'>" . $displayStatus . "</span></td>";
                                    
                                    echo "<td data-label='Action'>";
                                    echo "<div class='action-group'>";
                                    
                                    if ($row["order_status"] == 'pending') {
                                        echo "<form method='POST' style='margin:0;'>";
                                        echo "<input type='hidden' name='order_id' value='" . $row["order_id"] . "'>";
                                        echo "<button type='submit' name='approve_order' class='btn-approve'>Approve</button>";
                                        echo "</form>";
                                        
                                        echo "<form method='POST' style='margin:0;'>";
                                        echo "<input type='hidden' name='order_id' value='" . $row["order_id"] . "'>";
                                        echo "<button type='submit' name='reject_order' class='btn-reject' onclick='return confirm(\"Are you sure you want to reject this order?\");'>Reject</button>";
                                        echo "</form>";
                                    }
                                    
                                    echo "<a href='view_order.php?id=" . $row["order_id"] . "' class='btn-view'>View</a>";
                                    
                                    echo "</div>";
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

                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?status=<?php echo $current_filter; ?>&sort=<?php echo $sort; ?>&page=<?php echo $page - 1; ?>">Prev</a>
                    <?php else: ?>
                        <span class="disabled">Prev</span>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?status=<?php echo $current_filter; ?>&sort=<?php echo $sort; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?status=<?php echo $current_filter; ?>&sort=<?php echo $sort; ?>&page=<?php echo $page + 1; ?>">Next</a>
                    <?php else: ?>
                        <span class="disabled">Next</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

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

        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                e.preventDefault(); 
                const findBar = document.getElementById('customFindBar');
                findBar.style.display = 'flex';
                document.getElementById('findInput').focus();
            }
            if (e.key === 'Escape') {
                const findBar = document.getElementById('customFindBar');
                if (findBar.style.display === 'flex') {
                    findBar.style.display = 'none';
                    document.getElementById('findInput').value = '';
                    document.querySelectorAll('table tbody tr').forEach(row => row.style.display = '');
                    e.stopImmediatePropagation();
                }
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

    <!-- KimFresh mobile burger menu. Include this just before </body> on every admin page. -->
    <script>
    /* KimFresh mobile burger menu.
       Builds the burger button, a mobile logo, a close button and the overlay,
       so every admin page only needs to include this one script. */
    (function () {
        var sidebar = document.querySelector('.sidebar');
        var header = document.querySelector('.top-header');
        if (!sidebar || !header) return;

        sidebar.id = sidebar.id || 'sidebar';

        // Burger button (first item in the top bar)
        var burger = document.createElement('button');
        burger.type = 'button';
        burger.className = 'menu-toggle';
        burger.setAttribute('aria-label', 'Open menu');
        burger.setAttribute('aria-controls', sidebar.id);
        burger.setAttribute('aria-expanded', 'false');
        burger.innerHTML = '<span></span><span></span><span></span>';
        header.insertBefore(burger, header.firstChild);

        // Logo in the top bar (copied from the sidebar, since the sidebar is hidden)
        var logo = sidebar.querySelector('.logo');
        if (logo) {
            var mobileLogo = logo.cloneNode(true);
            mobileLogo.className = 'mobile-logo';
            header.insertBefore(mobileLogo, burger.nextSibling);
        }

        // Close button inside the drawer
        var closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'sidebar-close';
        closeBtn.setAttribute('aria-label', 'Close menu');
        closeBtn.innerHTML = '&times;';
        sidebar.appendChild(closeBtn);

        // Dark overlay behind the drawer
        var overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);

        function setOpen(open) {
            sidebar.classList.toggle('open', open);
            document.body.classList.toggle('sidebar-open', open);
            burger.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        burger.addEventListener('click', function () {
            setOpen(!sidebar.classList.contains('open'));
        });
        closeBtn.addEventListener('click', function () { setOpen(false); });
        overlay.addEventListener('click', function () { setOpen(false); });
        sidebar.addEventListener('click', function (e) {
            if (e.target.closest('a')) setOpen(false);
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') setOpen(false);
        });
        // If the window grows past phone size, reset the drawer
        window.matchMedia('(min-width: 769px)').addEventListener('change', function (e) {
            if (e.matches) setOpen(false);
        });
    })();
    </script>
</body>
</html>
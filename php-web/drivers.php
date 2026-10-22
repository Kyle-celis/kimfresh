<?php
session_start();
require 'db.php';
require 'log_helper.php';

if (!isset($_SESSION['admin_id'])) { header("Location: login.php"); exit(); }

// --- HANDLE PDF EXPORT ---
$trigger_print = false;
if (isset($_POST['export_pdf'])) {
    logActivity($conn, $_SESSION['admin_id'], "Exported Drivers to PDF");
    $trigger_print = true;
}

// --- HANDLE ACTIVATE/DEACTIVATE ---
if (isset($_POST['toggle_status'])) {
    $driver_id = $_POST['driver_id'];
    $new_status = $_POST['new_status'];
    
    $stmt = $conn->prepare("UPDATE driver SET status = ? WHERE driver_id = ?");
    $stmt->bind_param("si", $new_status, $driver_id);
    $stmt->execute();
    
    $action = ($new_status == 'hidden') ? "Deactivated Driver #" : "Activated Driver #";
    logActivity($conn, $_SESSION['admin_id'], $action . $driver_id);
    
    header("Location: drivers.php");
    exit();
}

// --- HANDLE MANUAL ASSIGN ---
if (isset($_POST['assign_order'])) {
    $order_id = $_POST['order_id'];
    $driver_id = $_POST['driver_id'];
    
    // 1. Create the delivery record
    $stmt = $conn->prepare("INSERT INTO delivery (order_id, driver_id, delivery_status) VALUES (?, ?, 'assigned')");
    $stmt->bind_param("ii", $order_id, $driver_id);
    $stmt->execute();
    
    // 2. Mark driver as on_delivery
    $conn->query("UPDATE driver SET status = 'on_delivery' WHERE driver_id = $driver_id");
    
    // 3. Log the action
    logActivity($conn, $_SESSION['admin_id'], "Manually assigned Order #$order_id to Driver #$driver_id");
    
    header("Location: drivers.php");
    exit();
}

// --- FILTER LOGIC ---
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all'; 
$orderBy = "d.name ASC"; 

$sql = "
    SELECT d.*,
           (SELECT dl.delivery_status 
            FROM delivery dl 
            INNER JOIN `order` o ON o.order_id = dl.order_id
            WHERE dl.driver_id = d.driver_id AND o.order_status != 'delivered' 
            ORDER BY dl.delivery_id DESC LIMIT 1) AS active_delivery_status,
           (SELECT COUNT(*) 
            FROM delivery dl 
            INNER JOIN `order` o ON o.order_id = dl.order_id
            WHERE dl.driver_id = d.driver_id AND o.order_status != 'delivered') AS active_orders
    FROM driver d
    WHERE 1=1
";

if (!$trigger_print) {
    if ($filter === 'all') {
        $sql .= " AND d.status != 'hidden'";
    } elseif ($filter === 'available') {
        $sql .= " AND (SELECT COUNT(*) FROM delivery dl INNER JOIN `order` o ON o.order_id = dl.order_id WHERE dl.driver_id = d.driver_id AND o.order_status != 'delivered') = 0 AND d.status != 'hidden'";
    } elseif ($filter === 'assigned') {
        $sql .= " AND (SELECT dl.delivery_status FROM delivery dl INNER JOIN `order` o ON o.order_id = dl.order_id WHERE dl.driver_id = d.driver_id AND o.order_status != 'delivered' ORDER BY dl.delivery_id DESC LIMIT 1) = 'assigned'";
    } elseif ($filter === 'on_delivery') {
        $sql .= " AND (SELECT dl.delivery_status FROM delivery dl INNER JOIN `order` o ON o.order_id = dl.order_id WHERE dl.driver_id = d.driver_id AND o.order_status != 'delivered' ORDER BY dl.delivery_id DESC LIMIT 1) IN ('in_transit', 'picked_up')";
    } elseif ($filter === 'inactive') {
        $sql .= " AND d.status = 'hidden'";
    } 
}

$sql .= " ORDER BY $orderBy";
$drivers = $conn->query($sql);

// --- FETCH UNASSIGNED ORDERS ---
$unassigned = $conn->query("
    SELECT o.order_id, r.name AS retailer_name 
    FROM `order` o
    JOIN retailer r ON o.retailer_id = r.retailer_id
    LEFT JOIN delivery d ON o.order_id = d.order_id
    WHERE o.order_status = 'approved' AND d.delivery_id IS NULL
");

// --- LOAD PAST DELIVERIES ---
$histories = [];
$histRes = $conn->query("
    SELECT dl.driver_id, o.order_id, o.order_status, o.total_amount, o.order_date, dl.delivery_status, dl.delivery_date AS assigned_at, r.name AS retailer_name, (SELECT SUM(oi.quantity) FROM order_item oi WHERE oi.order_id = o.order_id) AS total_items
    FROM delivery dl
    INNER JOIN `order` o ON o.order_id = dl.order_id
    LEFT  JOIN retailer r ON r.retailer_id = o.retailer_id
    ORDER BY dl.delivery_date DESC, o.order_id DESC
");
if ($histRes) {
    while ($h = $histRes->fetch_assoc()) {
        $histories[(int)$h['driver_id']][] = $h;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>KimFresh - Drivers</title>
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/drivers.css">
    <link rel="stylesheet" href="css/responsive.css">
    
    <style>
        :root { --primary: #C34200; --primary-dark: #9a3400; }
        
        .status.available { color: #15803d; background: #dcfce7; }
        .status.assigned { color: #1d4ed8; background: #dbeafe; }
        .status.on-delivery { color: #b45309; background: #fef3c7; }
        .status.inactive { color: #666; background: #eee; }

        .modal-backdrop { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 1000; }
        .modal-backdrop.open { display: flex; }
        .modal { background: white; border-radius: 12px; padding: 24px; width: 90%; max-width: 400px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .modal-wide { max-width: 800px; }
        .modal-actions { display: flex; justify-content: space-between; align-items: center; gap: 10px; }
        .modal-actions button { padding: 10px 20px; border-radius: 8px; font-weight: bold; cursor: pointer; border: none; }
        .modal-actions .cancel { background: #eee; color: #333; }
        
        .history-table { width: 100%; border-collapse: collapse; }
        .history-table th { text-align: left; padding: 12px; font-size: 12px; color: #666; border-bottom: 1px solid #eee; background: #f8f9fa; }
        .history-table td { padding: 12px; font-size: 13px; border-bottom: 1px solid #eee; }

        .history-btn { padding: 8px 16px; font-size: 12px; font-weight: 700; color: #fff; background: #C34200; border: none; border-radius: 8px; cursor: pointer; width: 100%; margin-bottom: 8px; }
        .history-btn:disabled { background: #ccc; cursor: not-allowed; }
        
        .btn-deactivate { padding: 8px 16px; font-size: 12px; font-weight: 700; color: #fff; background: #dc2626; border: 1px solid #dc2626; border-radius: 8px; cursor: pointer; width: 100%; }
        .btn-deactivate:hover { background: #b91c1c; }
        .btn-activate { padding: 8px 16px; font-size: 12px; font-weight: 700; color: #fff; background: #15803d; border: 1px solid #15803d; border-radius: 8px; cursor: pointer; width: 100%; }
        .btn-activate:hover { background: #166534; }

        .filters { display: flex; gap: 12px; margin-top: 24px; margin-bottom: 24px; justify-content: flex-end; }
        .filters select { width: 220px; height: 44px; padding: 0 16px; border: 1px solid #ddd; border-radius: 10px; font-size: 13px; outline: none; background: #fff; font-weight: bold; color: #333; }
        .filters select:focus { border-color: #C34200; }

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

        .btn-export { background: #C34200; color: white; padding: 10px 20px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.2s; }
        .btn-export:hover { background: #9a3400; }

        /* Assign Form */
        .assign-form { display: flex; gap: 5px; margin-top: 5px; }
        .assign-form select { flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 8px; font-size: 12px; }
        .assign-form button { padding: 8px 12px; background: #C34200; color: white; border: none; border-radius: 8px; font-size: 12px; font-weight: bold; cursor: pointer; }

        /* --- PRINT STYLES FOR PDF --- */
        @media print {
            .sidebar, .top-header, .filters, .page-head, .btn-export, .driver-actions, .no-print { display: none !important; }
            body { background: white !important; margin: 0 !important; padding: 0 !important; }
            .dashboard-layout { display: block !important; }
            .main-area { margin-left: 0 !important; width: 100% !important; }
            .main-content { padding: 0 !important; }
            h1 { text-align: center; color: #000 !important; margin-bottom: 5px; }
            .overview-label { display: none !important; }
            
            .driver-grid { display: block !important; }
            .driver-card { 
                border: 1px solid #000 !important; 
                box-shadow: none !important; 
                margin-bottom: 15px !important; 
                padding: 15px !important;
                page-break-inside: avoid;
            }
            .driver-contact p, .driver-license { color: #000 !important; }
            .status { border: 1px solid #000 !important; color: #000 !important; background: transparent !important; }
        }
    </style>
</head>
<body <?php if ($trigger_print) echo 'onload="window.print()"'; ?>>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-brand">
                <img src="logo.png" class="logo" alt="KimFresh">
                <p>Administration</p>
            </div>
            <nav class="menu">
                <a href="dashboard.php">Dashboard</a>
                <a href="orders.php">Orders</a>
                <a href="products.php">Products</a>
                <a href="drivers.php" class="active">Drivers</a>
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
                <div style="display:flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;" class="page-head">
                    <div>
                        <p class="overview-label">Manage</p>
                        <h1 style="margin:0;">Drivers</h1>
                    </div>
                    <form method="POST" style="margin:0;">
                        <button type="submit" name="export_pdf" class="btn-export">Export to PDF</button>
                    </form>
                </div>

                <?php if($trigger_print): ?>
                    <div style="text-align: center; margin-bottom: 30px;">
                        <h1 style="color: #C34200; margin-bottom: 5px;">KimFresh Driver Roster</h1>
                        <p style="color: #666;">Generated on <?php echo date('F j, Y, g:i A'); ?></p>
                    </div>
                <?php endif; ?>

                <form method="GET" class="filters no-print">
                    <select name="filter" onchange="this.form.submit()">
                        <option value="all" <?php echo $filter == 'all' ? 'selected' : ''; ?>>All Drivers</option>
                        <option value="available" <?php echo $filter == 'available' ? 'selected' : ''; ?>>Available</option>
                        <option value="assigned" <?php echo $filter == 'assigned' ? 'selected' : ''; ?>>Assigned</option>
                        <option value="on_delivery" <?php echo $filter == 'on_delivery' ? 'selected' : ''; ?>>On Delivery</option>
                        <option value="inactive" <?php echo $filter == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </form>

                <div class="driver-grid" style="margin-top: 20px;">
                    <?php if ($drivers->num_rows > 0): ?>
                        <?php while($row = $drivers->fetch_assoc()):
                            $activeCount = (int)$row['active_orders'];
                            $deliveryStatus = $row['active_delivery_status'];
                            $isInactive = $row['status'] === 'hidden';

                            if ($isInactive) {
                                $statusClass = 'inactive';
                                $statusLabel = 'Inactive';
                            } elseif ($activeCount == 0) {
                                $statusClass = 'available';
                                $statusLabel = 'Available';
                            } elseif ($deliveryStatus == 'assigned') {
                                $statusClass = 'assigned';
                                $statusLabel = 'Assigned';
                            } else {
                                $statusClass = 'on-delivery';
                                $statusLabel = 'On Delivery';
                            }

                            $initial     = strtoupper(substr($row['name'], 0, 1));
                            $driverId    = (int)$row['driver_id'];
                            $driverPast  = $histories[$driverId] ?? [];
                        ?>
                            <div class='driver-card'>
                                <div class='driver-top'>
                                    <div class='driver-avatar'><?php echo $initial; ?></div>
                                    <div class='driver-title'>
                                        <div class='driver-name'><?php echo htmlspecialchars($row['name']); ?></div>
                                        <div class='driver-license'><?php echo htmlspecialchars($row['license_number'] ?? 'No license'); ?></div>
                                    </div>
                                    <span class='status <?php echo $statusClass; ?>'><?php echo $statusLabel; ?></span>
                                </div>
                                <div class='driver-contact'>
                                    <p><?php echo htmlspecialchars($row['email'] ?? 'No email'); ?></p>
                                    <p><?php echo htmlspecialchars($row['phone'] ?? 'No phone'); ?></p>
                                </div>
                                <div class='driver-section'>
                                    <h4>Active Orders: <?php echo $activeCount; ?></h4>
                                    <p class="done-count"><?php echo count($driverPast); ?> past deliveries</p>
                                </div>

                                <div class="driver-actions no-print" style="display:flex; flex-direction:column; gap:8px;">
                                    
                                    <!-- MANUAL ASSIGN FORM -->
                                    <?php if ($activeCount == 0 && !$isInactive && $unassigned->num_rows > 0): ?>
                                        <form method="POST" class="assign-form">
                                            <input type="hidden" name="driver_id" value="<?php echo $driverId; ?>">
                                            <select name="order_id" required>
                                                <option value="">Assign an order...</option>
                                                <?php 
                                                    $unassigned->data_seek(0); 
                                                    while($uo = $unassigned->fetch_assoc()) {
                                                        echo "<option value='".$uo['order_id']."'>#".$uo['order_id']." - ".htmlspecialchars($uo['retailer_name'])."</option>";
                                                    }
                                                ?>
                                            </select>
                                            <button type="submit" name="assign_order">Assign</button>
                                        </form>
                                    <?php endif; ?>

                                    <button
                                        class="history-btn"
                                        type="button"
                                        data-driver-id="<?php echo $driverId; ?>"
                                        data-driver-name="<?php echo htmlspecialchars($row['name']); ?>"
                                        <?php if (empty($driverPast)) echo 'disabled title="No past deliveries for this driver"'; ?>
                                    >
                                        View Delivery History
                                    </button>

                                    <?php if ($isInactive): ?>
                                        <form method='POST' style='margin:0;'>
                                            <input type='hidden' name='driver_id' value='<?php echo $driverId; ?>'>
                                            <input type='hidden' name='new_status' value='available'>
                                            <button type='submit' name='toggle_status' class='btn-activate'>Activate Driver</button>
                                        </form>
                                    <?php else: ?>
                                        <form method='POST' style='margin:0;'>
                                            <input type='hidden' name='driver_id' value='<?php echo $driverId; ?>'>
                                            <input type='hidden' name='new_status' value='hidden'>
                                            <button type='submit' name='toggle_status' class='btn-deactivate'>Deactivate Driver</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <template id="history-tpl-<?php echo $driverId; ?>">
                                <?php if (!empty($driverPast)): ?>
                                    <table class="history-table">
                                        <thead>
                                            <tr>
                                                <th>Order</th>
                                                <th>Retailer</th>
                                                <th>Items</th>
                                                <th>Total</th>
                                                <th>Order Status</th>
                                                <th>Delivery Status</th>
                                                <th>Assigned</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($driverPast as $h):
                                            $orderClass    = strtolower(str_replace(' ', '_', $h['order_status']));
                                            $deliveryClass = strtolower(str_replace(' ', '_', $h['delivery_status']));
                                        ?>
                                            <tr>
                                                <td><span class="order-no">#<?php echo (int)$h['order_id']; ?></span></td>
                                                <td><?php echo htmlspecialchars($h['retailer_name'] ?? '—'); ?></td>
                                                <td><?php echo (int)($h['total_items'] ?? 0); ?></td>
                                                <td>₱<?php echo number_format((float)$h['total_amount'], 2); ?></td>
                                                <td><span class="status-pill <?php echo $orderClass; ?>"><?php echo strtoupper(str_replace('_', ' ', $h['order_status'])); ?></span></td>
                                                <td><span class="status-pill <?php echo $deliveryClass; ?>"><?php echo strtoupper(str_replace('_', ' ', $h['delivery_status'])); ?></span></td>
                                                <td><?php echo htmlspecialchars($h['assigned_at'] ?? $h['order_date']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <p class="empty-note">This driver has no past deliveries yet.</p>
                                <?php endif; ?>
                            </template>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="text-align:center; padding: 20px;">No drivers found matching your filter.</p>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Order History Modal -->
    <div class="modal-backdrop no-print" id="historyModal">
        <div class="modal modal-wide">
            <h3 style="margin-top:0; color: #C34200;">Order History — <span id="historyDriverName">—</span></h3>
            <div id="historyBody" style="max-height: 60vh; overflow: auto; margin-top: 14px;"></div>
            <div class="modal-actions" style="margin-top: 16px;">
                <button type="button" class="cancel" onclick="closeHistory()">Close</button>
                <button type="button" class="btn-export" onclick="exportHistoryPDF()">Export to PDF</button>
            </div>
        </div>
    </div>

    <!-- CUSTOM CTRL+F FIND BAR -->
    <div id="customFindBar" class="no-print" style="display: none; position: fixed; top: 10px; right: 20px; background: white; padding: 10px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 9999; border: 1px solid #ddd;">
        <input type="text" id="findInput" placeholder="Find driver..." style="padding: 8px; border: 1px solid #ccc; border-radius: 4px; outline: none; width: 200px;">
        <button id="closeFind" style="padding: 8px 12px; margin-left: 5px; background: #eee; border: none; border-radius: 4px; cursor: pointer;">X</button>
    </div>

    <script>
        const historyModal = document.getElementById('historyModal');
        const historyName  = document.getElementById('historyDriverName');
        const historyBody  = document.getElementById('historyBody');

        document.querySelectorAll('.history-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                if (btn.disabled) return;
                const id   = btn.dataset.driverId;
                const name = btn.dataset.driverName;
                const tpl  = document.getElementById('history-tpl-' + id);

                historyName.textContent = name;
                historyBody.innerHTML   = '';
                if (tpl) historyBody.appendChild(tpl.content.cloneNode(true));

                historyModal.classList.add('open');
            });
        });

        function closeHistory() { historyModal.classList.remove('open'); }

        historyModal.addEventListener('click', e => {
            if (e.target === historyModal) closeHistory();
        });

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') closeHistory();
        });

        function exportHistoryPDF() {
            const driverName = historyName.textContent;
            const tableHTML = historyBody.innerHTML;

            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Delivery History - ${driverName}</title>
                    <style>
                        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #333; padding: 40px; }
                        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #C34200; padding-bottom: 15px; }
                        .header h1 { color: #C34200; margin: 0; font-size: 24px; }
                        .header p { color: #666; margin: 5px 0 0; font-size: 14px; }
                        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                        th { background: #fdf0e6; color: #C34200; padding: 10px; text-align: left; font-size: 12px; text-transform: uppercase; border-bottom: 2px solid #C34200; }
                        td { padding: 10px; border-bottom: 1px solid #eee; font-size: 12px; }
                        .footer { margin-top: 40px; text-align: center; font-size: 10px; color: #999; }
                    </style>
                </head>
                <body onload="window.print();">
                    <div class="header">
                        <h1>KimFresh Delivery History</h1>
                        <p>Driver: <strong>${driverName}</strong> | Generated on <?php echo date('F j, Y'); ?></p>
                    </div>
                    ${tableHTML}
                    <div class="footer">KimFresh IoT System - Confidential Report</div>
                </body>
                </html>
            `);
            printWindow.document.close();
        }

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
                document.getElementById('customFindBar').style.display = 'none';
                document.getElementById('findInput').value = '';
                document.querySelectorAll('.driver-card').forEach(card => card.style.display = '');
            }
        });

        document.getElementById('closeFind').addEventListener('click', function() {
            document.getElementById('customFindBar').style.display = 'none';
            document.getElementById('findInput').value = '';
            document.querySelectorAll('.driver-card').forEach(card => card.style.display = '');
        });

        document.getElementById('findInput').addEventListener('keyup', function() {
            const query = this.value.toLowerCase();
            const cards = document.querySelectorAll('.driver-card');
            cards.forEach(card => {
                const text = card.innerText.toLowerCase();
                card.style.display = text.includes(query) ? '' : 'none';
            });
        });
    </script>
</body>
</html>
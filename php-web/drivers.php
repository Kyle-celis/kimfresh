<?php
session_start();
require 'db.php';
require 'log_helper.php';

if (!isset($_SESSION['admin_id'])) { header("Location: login.php"); exit(); }

// --- HANDLE HIDE/RESTORE ---
if (isset($_POST['toggle_status'])) {
    $driver_id = $_POST['driver_id'];
    $new_status = $_POST['new_status'];
    
    $stmt = $conn->prepare("UPDATE driver SET status = ? WHERE driver_id = ?");
    $stmt->bind_param("si", $new_status, $driver_id);
    $stmt->execute();
    
    $action = ($new_status == 'hidden') ? "Hidden Driver #" : "Restored Driver #";
    logActivity($conn, $_SESSION['admin_id'], $action . $driver_id);
    
    header("Location: drivers.php");
    exit();
}

// --- FILTER & SORT LOGIC ---
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all'; 
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'name_asc';

$orderBy = "d.name ASC"; // Default
if ($sort == 'name_desc') $orderBy = "d.name DESC";
if ($sort == 'id_asc') $orderBy = "d.driver_id ASC";
if ($sort == 'id_desc') $orderBy = "d.driver_id DESC";

$sql = "
    SELECT d.*,
           (SELECT dl.delivery_status 
            FROM delivery dl
            WHERE dl.driver_id = d.driver_id
              AND dl.delivery_status != 'delivered'
            ORDER BY dl.delivery_id DESC LIMIT 1) AS active_delivery_status,
           (SELECT COUNT(*) FROM delivery dl
            WHERE dl.driver_id = d.driver_id
              AND dl.delivery_status != 'delivered') AS active_orders
    FROM driver d
    WHERE 1=1
";

if ($filter === 'available') {
    // Drivers with 0 active orders
    $sql .= " AND (SELECT COUNT(*) FROM delivery dl WHERE dl.driver_id = d.driver_id AND dl.delivery_status != 'delivered') = 0";
} elseif ($filter === 'assigned') {
    // Drivers whose active order is currently 'assigned'
    $sql .= " AND (SELECT dl.delivery_status FROM delivery dl WHERE dl.driver_id = d.driver_id AND dl.delivery_status != 'delivered' ORDER BY dl.delivery_id DESC LIMIT 1) = 'assigned'";
} elseif ($filter === 'on_delivery') {
    // Drivers whose active order is currently 'in_transit' or 'picked_up'
    $sql .= " AND (SELECT dl.delivery_status FROM delivery dl WHERE dl.driver_id = d.driver_id AND dl.delivery_status != 'delivered' ORDER BY dl.delivery_id DESC LIMIT 1) IN ('in_transit', 'picked_up')";
} elseif ($filter === 'hidden') {
    $sql .= " AND d.status = 'hidden'";
} 

$sql .= " ORDER BY $orderBy";
$drivers = $conn->query($sql);

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
    
    <style>
        :root { --primary: #C34200; --primary-dark: #9a3400; }
        
        /* Status Badges */
        .status.available { color: #15803d; background: #dcfce7; }
        .status.assigned { color: #1d4ed8; background: #dbeafe; }
        .status.on-delivery { color: #b45309; background: #fef3c7; }
        .status.hidden { color: #666; background: #eee; }

        /* Modal Styles */
        .modal-backdrop { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 1000; }
        .modal-backdrop.open { display: flex; }
        .modal { background: white; border-radius: 12px; padding: 24px; width: 90%; max-width: 400px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .modal-wide { max-width: 800px; }
        .modal-actions { display: flex; justify-content: flex-end; gap: 10px; }
        .modal-actions button { padding: 10px 20px; border-radius: 8px; font-weight: bold; cursor: pointer; border: none; }
        .modal-actions .cancel { background: #eee; color: #333; }
        
        /* Table inside modal */
        .history-table { width: 100%; border-collapse: collapse; }
        .history-table th { text-align: left; padding: 12px; font-size: 12px; color: #666; border-bottom: 1px solid #eee; background: #f8f9fa; }
        .history-table td { padding: 12px; font-size: 13px; border-bottom: 1px solid #eee; }

        /* Action Buttons */
        .history-btn { padding: 8px 16px; font-size: 12px; font-weight: 700; color: #fff; background: #C34200; border: none; border-radius: 8px; cursor: pointer; width: 100%; margin-bottom: 8px; }
        .history-btn:disabled { background: #ccc; cursor: not-allowed; }
        
        .btn-hide { padding: 8px 16px; font-size: 12px; font-weight: 700; color: #C34200; background: #fff; border: 1px solid #C34200; border-radius: 8px; cursor: pointer; width: 100%; }
        .btn-hide:hover { background: #fdf0e6; }
        .btn-restore { padding: 8px 16px; font-size: 12px; font-weight: 700; color: #fff; background: #C34200; border: 1px solid #C34200; border-radius: 8px; cursor: pointer; width: 100%; }

        /* Filters */
        .filters { display: flex; gap: 12px; margin-top: 24px; margin-bottom: 24px; justify-content: flex-end; }
        .filters select { width: 220px; height: 44px; padding: 0 16px; border: 1px solid #ddd; border-radius: 10px; font-size: 13px; outline: none; background: #fff; font-weight: bold; color: #333; }
        .filters select:focus { border-color: #C34200; }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-brand">
                <img src="../logo.png" class="logo" alt="KimFresh">
                <p>Administration</p>
            </div>
            <nav class="menu">
                <a href="dashboard.php">Dashboard</a>
                <a href="orders.php">Orders</a>
                <a href="products.php">Products</a>
                <a href="drivers.php" class="active">Drivers</a>
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
                            <p class="user-name"><?php echo htmlspecialchars($_SESSION['admin_name']); ?></p>
                            <p class="user-role">Administrator</p>
                        </div>
                        <a href="logout.php" class="logout-btn" style="margin-left: 15px; text-decoration: none;">Logout</a>
                    </div>
                </div>
            </header>

            <main class="main-content">
                <p class="overview-label">Manage</p>
                <h1>Drivers</h1>

                <!-- FILTERS (No Search Bar) -->
                <form method="GET" class="filters">
                    <select name="filter" onchange="this.form.submit()">
                        <option value="all" <?php echo $filter == 'all' ? 'selected' : ''; ?>>All Drivers</option>
                        <option value="available" <?php echo $filter == 'available' ? 'selected' : ''; ?>>Available</option>
                        <option value="assigned" <?php echo $filter == 'assigned' ? 'selected' : ''; ?>>Assigned</option>
                        <option value="on_delivery" <?php echo $filter == 'on_delivery' ? 'selected' : ''; ?>>On Delivery</option>
                        <option value="hidden" <?php echo $filter == 'hidden' ? 'selected' : ''; ?>>Hidden</option>
                    </select>
                    
                    <select name="sort" onchange="this.form.submit()">
                        <option value="name_asc" <?php echo $sort == 'name_asc' ? 'selected' : ''; ?>>Sort by Name (A-Z)</option>
                        <option value="name_desc" <?php echo $sort == 'name_desc' ? 'selected' : ''; ?>>Sort by Name (Z-A)</option>
                    </select>
                </form>

                <div class="driver-grid" style="margin-top: 20px;">
                    <?php if ($drivers->num_rows > 0): ?>
                        <?php while($row = $drivers->fetch_assoc()):
                            $activeCount = (int)$row['active_orders'];
                            $deliveryStatus = $row['active_delivery_status'];
                            $isHidden = $row['status'] === 'hidden';

                            if ($isHidden) {
                                $statusClass = 'hidden';
                                $statusLabel = 'Hidden';
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

                                <div class="driver-actions" style="display:flex; flex-direction:column; gap:8px;">
                                    <button
                                        class="history-btn"
                                        type="button"
                                        data-driver-id="<?php echo $driverId; ?>"
                                        data-driver-name="<?php echo htmlspecialchars($row['name']); ?>"
                                        <?php if (empty($driverPast)) echo 'disabled title="No past deliveries for this driver"'; ?>
                                    >
                                        View Delivery History
                                    </button>

                                    <?php if ($isHidden): ?>
                                        <form method='POST' style='margin:0;'>
                                            <input type='hidden' name='driver_id' value='<?php echo $driverId; ?>'>
                                            <input type='hidden' name='new_status' value='available'>
                                            <button type='submit' name='toggle_status' class='btn-restore'>Restore Driver</button>
                                        </form>
                                    <?php else: ?>
                                        <form method='POST' style='margin:0;'>
                                            <input type='hidden' name='driver_id' value='<?php echo $driverId; ?>'>
                                            <input type='hidden' name='new_status' value='hidden'>
                                            <button type='submit' name='toggle_status' class='btn-hide'>Hide Driver</button>
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
    <div class="modal-backdrop" id="historyModal">
        <div class="modal modal-wide">
            <h3 style="margin-top:0; color: #C34200;">Order History — <span id="historyDriverName">—</span></h3>
            <div id="historyBody" style="max-height: 60vh; overflow: auto; margin-top: 14px;"></div>
            <div class="modal-actions" style="margin-top: 16px;">
                <button type="button" class="cancel" onclick="closeHistory()">Close</button>
            </div>
        </div>
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
    </script>
</body>
</html>
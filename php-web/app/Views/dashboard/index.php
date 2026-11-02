<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KimFresh - Dashboard</title>

    <link rel="stylesheet" href="/php-web/public/css/theme.css">
    <link rel="stylesheet" href="/php-web/public/css/dashboard.css">
    <link rel="stylesheet" href="/php-web/public/css/flow.css">
    <link rel="stylesheet" href="/php-web/public/css/responsive.css">

    <style>
    /* --- Core Layout Reset --- */
    body { margin: 0; padding: 0; background: #f4f7f6; }
    .dashboard-layout { min-height: 100vh; width: 100%; position: relative; }
    
    /* --- Sidebar Fixed --- */
    .sidebar { 
        position: fixed !important; 
        top: 0; left: 0; 
        width: 260px !important; 
        height: 100vh !important; 
        background: #C34200 !important; 
        z-index: 1000;
        overflow-y: auto;
    }
    
    /* --- Main Area Pushed Right --- */
    .main-area { 
        margin-left: 260px !important; 
        width: calc(100% - 260px) !important; 
        display: flex; 
        flex-direction: column; 
        min-height: 100vh;
    }
    
    /* --- Top Header --- */
    .top-header { 
        width: 100% !important; 
        background: #ffffff !important; 
        padding: 15px 30px !important; 
        border-bottom: 1px solid #eaeaea !important; 
        border-radius: 0 !important; 
        margin: 0 !important;
        box-shadow: none !important;
        display: flex !important; 
        justify-content: flex-end !important; 
        align-items: center !important; 
        box-sizing: border-box !important; 
        position: sticky;
        top: 0;
        z-index: 999;
    }
    
    /* --- Main Content Padding --- */
    .main-content { padding: 24px 30px !important; flex-grow: 1 !important; }

    /* --- Existing custom styles --- */
    :root { --primary: #C34200; --primary-dark: #9a3400; }
    .week-controls a, .week-controls span.btn { background: #C34200 !important; color: white !important; border-radius: 5px; }
    .week-controls a:hover { background: #9a3400 !important; }
    .filter-select { padding: 8px 12px; border-radius: 5px; border: 1px solid #C34200; font-weight: bold; color: #C34200; background: white; cursor: pointer; outline: none; }
    th a { color: #666; transition: color 0.2s ease; text-decoration: none; }
    th a:hover { color: #C34200 !important; text-decoration: underline; }
    .profile-menu { position: relative; }
    .profile-trigger { display: flex; align-items: center; gap: 10px; background: transparent; border: none; cursor: pointer; padding: 8px; border-radius: 10px; transition: 0.2s; }
    .profile-trigger:hover { background: #fdf0e6; }
    .profile-dropdown { position: absolute; top: 100%; right: 0; background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); width: 200px; padding: 8px; display: none; z-index: 100; border: 1px solid #eee; }
    .profile-dropdown.show { display: block; }
    .dropdown-account { padding: 12px; border-bottom: 1px solid #eee; margin-bottom: 8px; }
    .dropdown-account strong { display: block; font-size: 14px; color: #333; }
    .dropdown-account span { font-size: 12px; color: #777; }
    .dropdown-item { display: flex; align-items: center; gap: 10px; padding: 10px 12px; text-decoration: none; color: #333; font-size: 13px; font-weight: 600; border-radius: 8px; transition: 0.2s; }
    .dropdown-item:hover { background: #fdf0e6; color: #C34200; }
    .pagination { display: flex; justify-content: center; gap: 6px; margin-top: 24px; }
    .pagination a, .pagination span { display: inline-flex; align-items: center; justify-content: center; min-width: 36px; height: 36px; padding: 0 12px; border-radius: 8px; font-size: 13px; font-weight: 700; text-decoration: none; border: 1px solid #ddd; color: #555; background: white; transition: 0.2s; }
    .pagination a:hover { background: #fdf0e6; border-color: #C34200; color: #C34200; }
    .pagination .active { background: #C34200; border-color: #C34200; color: white; }
    .pagination .disabled { opacity: 0.5; pointer-events: none; }
    .notification-container { position: relative; margin-right: 15px; }
    .notification-bell { position: relative; display: flex; align-items: center; justify-content: center; width: 44px; height: 44px; border-radius: 50%; background: transparent; cursor: pointer; transition: background 0.2s; }
    .notification-bell:hover { background: #f0f0f0; }
    .notif-badge { position: absolute; top: 2px; right: 2px; background: #dc2626; color: white; font-size: 11px; font-weight: 800; width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
    .notif-dropdown { position: absolute; top: 55px; right: 0; width: 320px; background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); border: 1px solid #eee; display: none; z-index: 1000; color: #333; overflow: hidden; }
    .notif-dropdown.show { display: block; }
    .notif-header { display: flex; justify-content: space-between; align-items: center; padding: 15px; border-bottom: 1px solid #eee; }
    .notif-header h3 { margin: 0; font-size: 16px; font-weight: 800; color: #333; }
    .notif-mark-read { color: #C34200; text-decoration: none; font-size: 13px; font-weight: 600; }
    .notif-mark-read:hover { text-decoration: underline; }
    .notif-list { max-height: 350px; overflow-y: auto; }
    .notif-item { display: flex; gap: 12px; padding: 12px 15px; border-bottom: 1px solid #f5f5f5; text-decoration: none; color: #333; transition: background 0.2s; }
    .notif-item:hover { background: #fdf0e6; }
    .notif-icon { width: 45px; height: 45px; border-radius: 50%; background: #C34200; color: white; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-weight: bold; font-size: 16px; }
    .notif-content { flex: 1; display: flex; flex-direction: column; justify-content: center; }
    .notif-content p { margin: 0 0 4px 0; font-size: 13px; line-height: 1.3; color: #444; }
    .notif-content strong { font-weight: 800; color: #333; }
    .notif-time { color: #888; font-size: 11px; font-weight: 600; }
    .notif-empty { padding: 20px; text-align: center; color: #888; font-size: 13px; }
        /* Force Table Headers to White */
        table thead th { color: #ffffff !important; }
        table thead th a, table thead th a:visited { color: #ffffff !important; text-decoration: none; }
        table thead th a:hover { text-decoration: underline; }
        /* Colored Status Pills */
        .status-pill {
            display: inline-block;
            padding: 4px 10px;
            font-size: 11px;
            font-weight: 700;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Approved (Green) */
        .status-pill.approved, 
        .status-pill.delivered { 
            background-color: #dcfce7 !important; 
            color: #15803d !important; 
        }
        
        /* Pending (Yellow) */
        .status-pill.pending { 
            background-color: #fef3c7 !important; 
            color: #b45309 !important; 
        }
        
        /* Rejected (Red) */
        .status-pill.rejected, 
        .status-pill.cancelled { 
            background-color: #fee2e2 !important; 
            color: #b91c1c !important; 
        }
        
        /* In Transit / On Delivery (Orange/Blue) */
        .status-pill.in_transit,
        .status-pill.waiting_for_pickup { 
            background-color: #ffedd5 !important; 
            color: #c2410c !important; 
        }
</style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-brand">
                <img src="/php-web/public/logo.png" alt="KimFresh" class="logo">
                <p>Administration</p>
            </div>
            <nav class="menu">
                <a href="/php-web/public/dashboard" class="active">Dashboard</a>
                <a href="/php-web/public/orders">Orders</a>
                <a href="/php-web/public/products">Products</a>
                <a href="/php-web/public/drivers">Drivers</a>
                <a href="/php-web/public/analytics">Analytics</a>
                <?php if (!empty($_SESSION['is_superadmin'])): ?>
                    <a href="/php-web/public/superadmin">Manage Admins</a>
                <?php endif; ?>
            </nav>
        </aside>

        <div class="main-area">
            <header class="top-header">
                <div class="notification-container" id="notifContainer">
                    <div class="notification-bell" onclick="toggleNotifications(event)">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#555" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                        </svg>
                        <span id="notif-badge" class="notif-badge" style="display: none;">0</span>
                    </div>
                    <div class="notif-dropdown" id="notifDropdown">
                        <div class="notif-header">
                            <h3>Notifications</h3>
                            <a href="/php-web/public/orders" class="notif-mark-read">View All</a>
                        </div>
                        <div class="notif-list" id="notifList">
                            <div class="notif-empty">No new orders</div>
                        </div>
                    </div>
                </div>

                <div class="profile-menu" id="profileMenu">
                    <button type="button" class="profile-trigger" onclick="toggleProfileMenu(event)">
                        <div class="avatar">A</div>
                        <div class="user-info" style="text-align: left;">
                            <p class="user-name" style="margin: 0;"><?php echo htmlspecialchars($_SESSION['admin_name']); ?></p>
                            <p class="user-role" style="margin: 0;">Administrator</p>
                        </div>
                        <span style="font-size: 18px; color: #999; margin-left: 5px;">⌄</span>
                    </button>
                    <div class="profile-dropdown" id="profileDropdown">
                        <div class="dropdown-account">
                            <strong><?php echo htmlspecialchars($_SESSION['admin_name']); ?></strong>
                            <span><?php echo htmlspecialchars($_SESSION['admin_email'] ?? 'admin@kimfresh.com'); ?></span>
                        </div>
                        <a href="/php-web/public/settings" class="dropdown-item">Settings</a>
                        <a href="/php-web/public/logs" class="dropdown-item">Activity Logs</a>
                        <a href="/php-web/public/logout" class="dropdown-item" style="color: #C34200;">Logout</a>
                    </div>
                </div>
            </header>

            <main class="main-content">
                <p class="overview-label">Overview</p>
                <h1>Distribution Dashboard</h1>

                <section class="panel" style="margin-bottom: 24px;">
                    <div class="panel-head"><h3>Order Flow</h3></div>
                    <div class="pipeline">
                        <a class="pipe-step <?= $pending > 0 ? 'attention' : '' ?>" href="/php-web/public/orders"><strong><?= $pending ?></strong><span>Pending approval</span></a>
                        <a class="pipe-step <?= $waiting > 0 ? 'attention' : '' ?>" href="/php-web/public/orders"><strong><?= $waiting ?></strong><span>Waiting for driver</span></a>
                        <a class="pipe-step" href="/php-web/public/orders"><strong><?= $waitingForPickup ?></strong><span>In pickup</span></a>
                        <a class="pipe-step" href="/php-web/public/orders"><strong><?= $active ?></strong><span>Out for delivery</span></a>
                        <a class="pipe-step" href="/php-web/public/orders"><strong><?= $delivered ?></strong><span>Delivered</span></a>
                    </div>
                </section>

                <section class="cards-section">
                    <div class="card"><h3>Pending Orders</h3><div class="value"><?= $pending ?></div><p class="hint">Waiting for approval</p></div>
                    <div class="card"><h3>Awaiting Driver</h3><div class="value"><?= $waiting ?></div><p class="hint">Approved, no driver yet</p></div>
                    <div class="card"><h3>Active Deliveries</h3><div class="value"><?= $active ?></div><p class="hint">Pickup + on the way</p></div>
                    <div class="card"><h3>Delivered</h3><div class="value"><?= $delivered ?></div><p class="hint">&#8369;<?= number_format($revenue, 2) ?> fulfilled</p></div>
                    <div class="card"><h3>Low / Out of Stock</h3><div class="value"><?= $lowStock ?></div><p class="hint"><?= $totalProducts ?> active products</p></div>
                    <div class="card"><h3>Available Drivers</h3><div class="value"><?= $availableDrivers ?></div><p class="hint">of <?= $totalDrivers ?> drivers</p></div>
                    <div class="card"><h3>Orders This <?= $filterLabel ?></h3><div class="value"><?= $ordersThisPeriod ?></div><p class="hint"><?= $dateLabel ?></p></div>
                    <div class="card"><h3>Rejected</h3><div class="value"><?= $rejected ?></div><p class="hint">All time</p></div>
                </section>

                <section class="analytics-section" style="margin-top: 24px;">
                    <div class="week-controls" style="display: flex; gap: 10px; align-items: center;">
                        <select class="filter-select" onchange="window.location.href='?filter=' + this.value;">
                            <option value="weekly"  <?= $filter_type === 'weekly'  ? 'selected' : '' ?>>Weekly</option>
                            <option value="monthly" <?= $filter_type === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                        </select>

                        <?php if ($filter_type === 'weekly'): ?>
                            <a href="?filter=weekly&week=<?= $weekOffset - 1 ?>" class="btn" style="text-decoration:none; padding: 5px 10px;">‹</a>
                            <h3>Orders Per Week</h3>
                            <?php if ($weekOffset >= 0): ?>
                                <span class="btn" style="text-decoration:none; padding: 5px 10px; opacity: 0.3; cursor: not-allowed;">›</span>
                            <?php else: ?>
                                <a href="?filter=weekly&week=<?= $weekOffset + 1 ?>" class="btn" style="text-decoration:none; padding: 5px 10px;">›</a>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if ($filter_type === 'monthly'): ?>
                            <a href="?filter=monthly&month=<?= $monthOffset - 1 ?>" class="btn" style="text-decoration:none; padding: 5px 10px;">‹</a>
                            <h3>Orders Per Month</h3>
                            <?php if ($monthOffset >= 0): ?>
                                <span class="btn" style="text-decoration:none; padding: 5px 10px; opacity: 0.3; cursor: not-allowed;">›</span>
                            <?php else: ?>
                                <a href="?filter=monthly&month=<?= $monthOffset + 1 ?>" class="btn" style="text-decoration:none; padding: 5px 10px;">›</a>
                            <?php endif; ?>
                        <?php endif; ?>

                        <span class="week-range"><?= $dateLabel ?></span>
                    </div>
                    <div class="chart-box" style="height: 300px; position: relative;">
                        <canvas id="ordersChart"></canvas>
                    </div>
                </section>

                <section class="table-section" style="margin-top: 24px;">
                    <h3>Recent Orders (<?= $dateLabel ?>)</h3>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th><a href="?filter=<?= $filter_type ?>&sort=retailer_asc">Name &#8597;</a></th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th><a href="?filter=<?= $filter_type ?>&sort=driver_asc">Driver &#8597;</a></th>
                                    <th><a href="?filter=<?= $filter_type ?>&sort=status_asc">Status &#8597;</a></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recentOrders->num_rows > 0): while ($row = $recentOrders->fetch_assoc()): ?>
                                    <tr>
                                        <td><span class="order-no">#<?= $row['order_id'] ?></span></td>
                                        <td><?= htmlspecialchars($row['retailer_name']) ?></td>
                                        <td><?= $row['total_items'] ?? 0 ?> items</td>
                                        <td>&#8369;<?= number_format($row['total_amount'], 2) ?></td>
                                        <td><?= $row['driver_name'] ? htmlspecialchars($row['driver_name']) : '-' ?></td>
                                        <td><span class="status-pill <?= strtolower($row['order_status']) ?>"><?= strtoupper($row['order_status']) ?></span></td>
                                    </tr>
                                <?php endwhile; else: ?>
                                    <tr><td colspan="6" style="text-align:center;">No orders found for this period.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?filter=<?= $filter_type ?>&sort=<?= $sort ?>&page=<?= $page - 1 ?>">Prev</a>
                        <?php else: ?>
                            <span class="disabled">Prev</span>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i === $page): ?>
                                <span class="active"><?= $i ?></span>
                            <?php else: ?>
                                <a href="?filter=<?= $filter_type ?>&sort=<?= $sort ?>&page=<?= $i ?>"><?= $i ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        <?php if ($page < $totalPages): ?>
                            <a href="?filter=<?= $filter_type ?>&sort=<?= $sort ?>&page=<?= $page + 1 ?>">Next</a>
                        <?php else: ?>
                            <span class="disabled">Next</span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </section>

                <section class="panel" style="margin-top: 24px;">
                    <div class="panel-head">
                        <h3>Inventory Alerts</h3>
                        <a href="/php-web/public/products" class="panel-link">Products ›</a>
                    </div>
                    <div id="stockList">
                        <?php if ($lowStockList->num_rows > 0): while ($row = $lowStockList->fetch_assoc()): ?>
                            <?php $isOut = $row['stock_quantity'] <= 0; $percent = min(100, round(($row['stock_quantity'] / max(1, $row['reorder_level'])) * 100)); ?>
                            <div class="stock-row">
                                <div>
                                    <strong><?= htmlspecialchars($row['name']) ?></strong>
                                    <small><?= $row['stock_quantity'] ?> left · reorder at <?= $row['reorder_level'] ?></small>
                                    <div class="stock-bar"><i style="width:<?= $percent ?>%"></i></div>
                                </div>
                                <span class="status <?= $isOut ? 'out-of-stock' : 'low-stock' ?>"><?= $isOut ? 'Out of Stock' : 'Low Stock' ?></span>
                            </div>
                        <?php endwhile; else: ?>
                            <p class="empty-note">All products are well stocked.</p>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="panel" style="margin-top: 24px;">
                    <div class="panel-head">
                        <h3>Delivery Monitoring</h3>
                        <a href="/php-web/public/orders" class="panel-link">Drivers ›</a>
                    </div>
                    <div id="deliveryList">
                        <?php if ($activeDeliveries->num_rows > 0): while ($row = $activeDeliveries->fetch_assoc()): ?>
                            <?php
                                $statusLabel = strtoupper(str_replace('_', ' ', $row['order_status']));
                                if ($row['order_status'] === 'approved') $statusLabel = 'WAITING FOR DRIVER';
                                $driver = $row['driver_name'] ? htmlspecialchars($row['driver_name']) : 'No driver assigned';
                            ?>
                            <div class="delivery">
                                <div class="delivery-top">
                                    <div><strong>Order #<?= $row['order_id'] ?> · <?= htmlspecialchars($row['customer_code']) ?></strong></div>
                                    <span class="status <?= $row['order_status'] ?>"><?= $statusLabel ?></span>
                                </div>
                                <p class="delivery-driver">Driver: <strong><?= $driver ?></strong> · <?= htmlspecialchars($row['address']) ?></p>
                            </div>
                        <?php endwhile; else: ?>
                            <p class="empty-note">No deliveries in progress.</p>
                        <?php endif; ?>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('ordersChart').getContext('2d');
        const chartData = <?= json_encode($chartData) ?>;
        const chartLabels = <?= json_encode($chartLabels) ?>;
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartLabels,
                datasets: [{ label: 'Orders', data: chartData, borderColor: '#C34200', backgroundColor: 'rgba(195, 66, 0, 0.1)', borderWidth: 2, fill: true, tension: 0.3 }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { ticks: {} } } }
        });

        function toggleProfileMenu(e) { e.stopPropagation(); document.getElementById('profileDropdown').classList.toggle('show'); }
        document.addEventListener('click', function(e) {
            const m = document.getElementById('profileMenu');
            if (m && !m.contains(e.target)) document.getElementById('profileDropdown').classList.remove('show');
        });
    </script>



<!-- SCROLL MEMORY -->
<script>
    if (sessionStorage.getItem('scrollPos')) { 
        document.documentElement.style.visibility = 'hidden'; 
    }
    window.addEventListener('load', function() {
        const savedPosition = sessionStorage.getItem('scrollPos');
        if (savedPosition) {
            window.scrollTo(0, parseInt(savedPosition));
            document.documentElement.style.visibility = 'visible';
            sessionStorage.removeItem('scrollPos');
        }
    });
    document.addEventListener('mousedown', function(event) {
        if (event.target.closest('th a') || 
            event.target.closest('.pagination a') || 
            event.target.closest('.filter-select') ||
            event.target.closest('.week-controls a') ||
            event.target.closest('button[type="submit"]')) {
            sessionStorage.setItem('scrollPos', window.scrollY);
        }
    });
</script>
<!-- NOTIFICATIONS SCRIPT -->
<script>
    let lastNotifCount = 0;
    function playBeep() {
        try {
            let context = new (window.AudioContext || window.webkitAudioContext)();
            let osc = context.createOscillator();
            osc.type = 'sine';
            osc.frequency.value = 800;
            osc.connect(context.destination);
            osc.start();
            osc.stop(context.currentTime + 0.15);
        } catch (e) {
            console.log('Audio not supported in this browser.');
        }
    }

    function toggleNotifications(event) {
        event.stopPropagation();
        document.getElementById('notifDropdown').classList.toggle('show');
    }

    document.addEventListener('click', function(event) {
        const container = document.getElementById('notifContainer');
        const dropdown = document.getElementById('notifDropdown');
        if (container && !container.contains(event.target)) {
            if(dropdown) dropdown.classList.remove('show');
        }
    });

    function checkNotifications() {
        fetch('/php-web/public/notifications', { credentials: 'same-origin' })
            .then(response => response.json())
            .then(data => {
                const badge = document.getElementById('notif-badge');
                const list = document.getElementById('notifList');
                
                if (badge && list) {
                    let readIds = JSON.parse(localStorage.getItem('readNotifIds') || '[]');
                    let unreadCount = 0;

                    data.orders.forEach(item => {
                        const itemId = item.type === 'order' ? 'order-' + item.order_id : 'log-' + item.log_id;
                        if (!readIds.includes(itemId)) {
                            unreadCount++;
                        }
                    });

                    if (unreadCount > 0) {
                        badge.style.display = 'flex';
                        badge.textContent = unreadCount;
                        if (unreadCount > lastNotifCount) {
                            playBeep();
                        }
                    } else {
                        badge.style.display = 'none';
                    }
                    lastNotifCount = unreadCount;

                    list.innerHTML = ''; 
                    data.orders.forEach(item => {
                        const date = new Date(item.type === 'order' ? item.order_date : item.timestamp);
                        const timeAgo = date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
                        
                        const itemId = item.type === 'order' ? 'order-' + item.order_id : 'log-' + item.log_id;
                        const isRead = readIds.includes(itemId);
                        const readStyle = isRead ? 'opacity: 0.6;' : '';

                        if (item.type === 'order') {
                            list.innerHTML += `
                                <a href="/php-web/public/orders/view?id=${item.order_id}" class="notif-item" style="${readStyle}" data-notif-id="${itemId}" onclick="markAsRead('${itemId}')">
                                    <div class="notif-icon">#${item.order_id}</div>
                                    <div class="notif-content">
                                        <p><strong>${item.retailer_name}</strong> placed a new order.</p>
                                        <span class="notif-time">${timeAgo} &middot; ₱${Number(item.total_amount).toFixed(2)}</span>
                                    </div>
                                </a>
                            `;
                        } else {
                            list.innerHTML += `
                                <a href="/php-web/public/drivers?search=${encodeURIComponent(item.driver_name)}" class="notif-item" style="${readStyle}" data-notif-id="${itemId}" onclick="markAsRead('${itemId}')">
                                    <div class="notif-icon" style="background: #15803d; border-radius: 50%;">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                    </div>
                                    <div class="notif-content">
                                        <p>${item.action}</p>
                                        <span class="notif-time">${timeAgo} &middot; Click to view driver</span>
                                    </div>
                                </a>
                            `;
                        }
                    });
                }
            })
            .catch(err => console.error('Error fetching notifications:', err));
    }

    function markAsRead(itemId) {
        let readIds = JSON.parse(localStorage.getItem('readNotifIds') || '[]');
        if (!readIds.includes(itemId)) {
            readIds.push(itemId);
            localStorage.setItem('readNotifIds', JSON.stringify(readIds));
        }
    }

    checkNotifications();
    setInterval(checkNotifications, 15000);
</script>
</body>
</html>

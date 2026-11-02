<?php /* Order detail view - vars from ViewOrderController::show() */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?php echo $order_id; ?> - Receipt</title>
    <link rel="stylesheet" href="/php-web/public/css/theme.css">
    <link rel="stylesheet" href="/php-web/public/css/dashboard.css">
    <link rel="stylesheet" href="/php-web/public/css/orders.css">
    <link rel="stylesheet" href="/php-web/public/css/flow.css">
    <link rel="stylesheet" href="/php-web/public/css/responsive.css">
    
    <style>
        :root { --primary: #C34200; --primary-dark: #9a3400; }

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
        
        /* Action Buttons */
        .btn-approve { background: #15803d; color: white; padding: 10px 20px; font-size: 13px; font-weight: 700; border: none; border-radius: 8px; cursor: pointer; transition: 0.2s; }
        .btn-approve:hover { background: #166534; }
        .btn-reject { background: #dc2626; color: white; padding: 10px 20px; font-size: 13px; font-weight: 700; border: none; border-radius: 8px; cursor: pointer; transition: 0.2s; }
        .btn-reject:hover { background: #b91c1c; }

        /* =========================================
           NOTIFICATION DROPDOWN (LIGHT THEME)
           ========================================= */
        .notification-container { position: relative; margin-right: 15px; }
        
        .notification-bell {
            position: relative; display: flex; align-items: center; justify-content: center;
            width: 44px; height: 44px; border-radius: 50%; background: transparent; cursor: pointer; transition: background 0.2s;
        }
        .notification-bell:hover { background: #f0f0f0; }
        
        .notif-badge {
            position: absolute; top: 2px; right: 2px; background: #dc2626; color: white;
            font-size: 11px; font-weight: 800; width: 20px; height: 20px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .notif-dropdown {
            position: absolute; top: 55px; right: 0; width: 320px;
            background: white; border-radius: 12px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.15); border: 1px solid #eee;
            display: none; z-index: 1000; color: #333; overflow: hidden;
        }
        .notif-dropdown.show { display: block; }
        
        .notif-header {
            display: flex; justify-content: space-between; align-items: center;
            padding: 15px; border-bottom: 1px solid #eee;
        }
        .notif-header h3 { margin: 0; font-size: 16px; font-weight: 800; color: #333; }
        .notif-mark-read { color: #C34200; text-decoration: none; font-size: 13px; font-weight: 600; }
        .notif-mark-read:hover { text-decoration: underline; }

        .notif-list { max-height: 350px; overflow-y: auto; }
        
        .notif-item {
            display: flex; gap: 12px; padding: 12px 15px;
            border-bottom: 1px solid #f5f5f5; text-decoration: none; color: #333;
            transition: background 0.2s;
        }
        .notif-item:hover { background: #fdf0e6; }
        
        .notif-icon {
            width: 45px; height: 45px; border-radius: 50%;
            background: #C34200; color: white; 
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; font-weight: bold; font-size: 16px;
        }
        
        .notif-content { flex: 1; display: flex; flex-direction: column; justify-content: center; }
        .notif-content p { margin: 0 0 4px 0; font-size: 13px; line-height: 1.3; color: #444; }
        .notif-content strong { font-weight: 800; color: #333; }
        .notif-time { color: #888; font-size: 11px; font-weight: 600; }
        
        .notif-empty { padding: 20px; text-align: center; color: #888; font-size: 13px; }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar no-print">
            <div class="sidebar-brand">
                <img src="/php-web/public/logo.png" alt="KimFresh" class="logo">
                <p>Administration</p>
            </div>
            <nav class="menu">
                <a href="/php-web/public/dashboard">Dashboard</a>
                <a href="/php-web/public/orders" class="active">Orders</a>
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
                
                <!-- NOTIFICATION BELL & DROPDOWN -->
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
                            <a href="orders.php?status=pending" class="notif-mark-read">View All</a>
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
                
                <!-- ERROR BANNER -->
                <?php if (isset($_GET['error']) && $_GET['error'] === 'stock'): ?>
                  <div style="background:#fee2e2;color:#991b1b;padding:12px;border-radius:8px;margin-bottom:12px;font-weight:700; border: 1px solid #f87171;" class="no-print">
                    Approval failed: one or more products don't have enough stock to fulfill this order.
                  </div>
                <?php endif; ?>

                <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;" class="no-print">
                    <a href="/php-web/public/orders" class="back-link" style="color: #C34200; text-decoration: none; font-weight: bold; font-size: 14px;">&larr; Back to Orders</a>
                    
                    <div style="display: flex; gap: 10px;">
                        <?php if ($order['order_status'] === 'pending'): ?>
                            <form method="POST" id="rejectForm" style="margin:0;">
                                <input type="hidden" name="reject_order" value="1">
                                <button type="button" class="btn-reject" onclick="openRejectModal(event)">Reject</button>
                            </form>
                            <form method="POST" style="margin:0;">
                                <button type="submit" name="approve_order" class="btn-approve">Approve Order</button>
                            </form>
                        <?php endif; ?>
                        
                        <a href="?id=<?php echo $order_id; ?>&export_pdf=1" download="KimFresh_Order_<?php echo $order_id; ?>.pdf" class="btn-export">Export PDF</a>
                    </div>
                </div>

                <div class="table-section" style="margin-bottom: 24px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #C34200; padding-bottom: 15px; margin-bottom: 20px;">
                        <h2 style="color: #C34200; margin: 0;">Order #<?php echo $order_id; ?></h2>
                        <span class="status-pill <?php echo strtolower($order['order_status']); ?>">
                            <?php echo strtoupper(str_replace('_', ' ', $order['order_status'])); ?>
                        </span>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                        <div>
                            <h4 style="color: #555; margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 5px;">Retailer Information</h4>
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($order['retailer_name']); ?></p>
                            <p><strong>Code:</strong> <?php echo htmlspecialchars($order['customer_code']); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
                            <p><strong>Address:</strong> <?php echo htmlspecialchars($order['address']); ?></p>
                        </div>
                        <div>
                            <h4 style="color: #555; margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 5px;">Order Information</h4>
                            <p><strong>Date:</strong> <?php echo date('M d, Y h:i A', strtotime($order['order_date'])); ?></p>
                            <p><strong>Assigned Driver:</strong> <?php echo htmlspecialchars($driver_name); ?></p>
                            <p><strong>Total Amount:</strong> <span style="color: #C34200; font-weight: bold; font-size: 16px;">₱<?php echo number_format($order['total_amount'], 2); ?></span></p>
                        </div>
                    </div>

                    <h3 style="margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 5px;">Items Ordered</h3>
                    <div class="table-responsive">
                        <table class="items-table" style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                            <thead>
                                <tr>
                                    <th style="text-align: left; padding: 10px;">Product</th>
                                    <th style="text-align: left; padding: 10px;">Quantity</th>
                                    <th style="text-align: left; padding: 10px;">Unit Price</th>
                                    <th style="text-align: left; padding: 10px;">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($items->num_rows > 0) {
                                    $items->data_seek(0);
                                    while($item = $items->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td style='padding: 10px; border-bottom: 1px solid #eee;'>" . htmlspecialchars($item['product_name']) . "</td>";
                                        echo "<td style='padding: 10px; border-bottom: 1px solid #eee;'>" . $item['quantity'] . "</td>";
                                        echo "<td style='padding: 10px; border-bottom: 1px solid #eee;'>₱" . number_format($item['unit_price'], 2) . "</td>";
                                        echo "<td style='padding: 10px; border-bottom: 1px solid #eee;'>₱" . number_format($item['subtotal'], 2) . "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='4' style='padding: 20px; text-align: center; color: #777; font-weight: bold;'>Product Not Available (Deleted)</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Cold Chain History -->
                <div class="table-section">
                    <h3 style="margin-bottom: 5px;">Cold-chain History</h3>
                    <p style="font-size: 12px; color: #777; margin-bottom: 20px;">IoT temperature readings recorded during this delivery (Last 10 records).</p>
                    
                    <div class="table-responsive">
                        <table class="items-table" style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr>
                                    <th style="text-align: left; padding: 10px;">Recorded At</th>
                                    <th style="text-align: left; padding: 10px;">Temperature</th>
                                    <th style="text-align: left; padding: 10px;">Humidity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($sensor_data->num_rows > 0) {
                                    while($sensor = $sensor_data->fetch_assoc()) {
                                        $tempColor = $sensor['is_alert'] ? '#991b1b' : '#166534'; 
                                        echo "<tr>";
                                        echo "<td style='padding: 10px; border-bottom: 1px solid #eee;'>" . date('M d, Y h:i A', strtotime($sensor['reading_timestamp'])) . "</td>";
                                        echo "<td style='padding: 10px; border-bottom: 1px solid #eee; font-weight: bold; color: " . $tempColor . ";'>" . $sensor['temperature'] . "°C</td>";
                                        echo "<td style='padding: 10px; border-bottom: 1px solid #eee;'>" . $sensor['humidity'] . "%</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='3' style='padding: 20px; text-align: center; color: #777;'>No temperature readings recorded for this order.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // PROFILE MENU
        function toggleProfileMenu(event) {
            event.stopPropagation();
            document.getElementById('profileDropdown').classList.toggle('show');
        }
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('profileMenu');
            if (menu && !menu.contains(event.target)) {
                document.getElementById('profileDropdown').classList.remove('show');
            }
        });

    let lastNotifCount = 0;
    // 🔥 NEW: Built-in browser beep synthesizer
function playBeep() {
    try {
        let context = new (window.AudioContext || window.webkitAudioContext)();
        let osc = context.createOscillator();
        osc.type = 'sine';
        osc.frequency.value = 800; // 800Hz pitch
        osc.connect(context.destination);
        osc.start();
        osc.stop(context.currentTime + 0.15); // Beep for 0.15 seconds
    } catch (e) {
        console.log('Audio not supported in this browser.');
    }
}

    function toggleNotifications(event) {
        event.stopPropagation();
        const dropdown = document.getElementById('notifDropdown');
        // Just toggle the open/close state
        dropdown.classList.toggle('show');
    }

    // Close dropdown when clicking outside
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

                    // 1. Count unread
                    data.orders.forEach(item => {
                        const itemId = item.type === 'order' ? 'order-' + item.order_id : 'log-' + item.log_id;
                        if (!readIds.includes(itemId)) {
                            unreadCount++;
                        }
                    });

                    // 2. Update Badge
                    if (unreadCount > 0) {
                        badge.style.display = 'flex';
                        badge.textContent = unreadCount;
                        
                    if (unreadCount > lastNotifCount) {
                        playBeep(); // 👈 Call our new beep function
                    }
                    } else {
                        badge.style.display = 'none';
                    }
                    lastNotifCount = unreadCount;

                    // 3. Re-render list
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
                                <a href="drivers.php?search=${encodeURIComponent(item.driver_name)}" class="notif-item" style="${readStyle}" data-notif-id="${itemId}" onclick="markAsRead('${itemId}')">
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

    // 🔥 NEW FUNCTION: Marks only the clicked item as read
    function markAsRead(itemId) {
        let readIds = JSON.parse(localStorage.getItem('readNotifIds') || '[]');
        if (!readIds.includes(itemId)) {
            readIds.push(itemId);
            localStorage.setItem('readNotifIds', JSON.stringify(readIds));
        }
        // The badge will automatically update on the next polling interval
    }

    checkNotifications();
    setInterval(checkNotifications, 15000);
</script>
    <!-- CUSTOM REJECT CONFIRMATION MODAL -->
    <dialog id="rejectModal" style="width: 90%; max-width: 420px; margin: auto; padding: 28px; border: none; border-radius: 18px; box-shadow: 0 20px 60px rgba(0,0,0,0.35);">
        <h2 style="color: #dc2626; margin-bottom: 10px;">Reject this order?</h2>
        <p style="margin-bottom: 20px; font-size: 14px; color: #555; line-height: 1.5;">
            Are you sure you want to reject <strong>Order #<?= (int)$order_id ?></strong>? This action cannot be undone and the order will be marked as rejected.
        </p>
        <div style="display: flex; justify-content: flex-end; gap: 10px;">
            <button type="button" onclick="document.getElementById('rejectModal').close()" style="padding: 10px 20px; background: #eee; border: none; border-radius: 8px; cursor: pointer; font-weight: bold;">Cancel</button>
            <button type="button" onclick="submitReject()" style="padding: 10px 20px; background: #dc2626; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold;">Yes, Reject</button>
        </div>
    </dialog>

    <script>
        function openRejectModal(e) {
            e.preventDefault();
            document.getElementById('rejectModal').showModal();
        }
        function submitReject() {
            document.getElementById('rejectForm').submit();
        }
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
        } catch (e) {}
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
                        if (!readIds.includes(itemId)) unreadCount++;
                    });
                    if (unreadCount > 0) {
                        badge.style.display = 'flex';
                        badge.textContent = unreadCount;
                        if (unreadCount > lastNotifCount) playBeep();
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
                            list.innerHTML += `<a href="/php-web/public/orders/view?id=${item.order_id}" class="notif-item" style="${readStyle}" data-notif-id="${itemId}" onclick="markAsRead('${itemId}')"><div class="notif-icon">#${item.order_id}</div><div class="notif-content"><p><strong>${item.retailer_name}</strong> placed a new order.</p><span class="notif-time">${timeAgo} &middot; &#8369;${Number(item.total_amount).toFixed(2)}</span></div></a>`;
                        } else {
                            list.innerHTML += `<a href="/php-web/public/drivers?search=${encodeURIComponent(item.driver_name)}" class="notif-item" style="${readStyle}" data-notif-id="${itemId}" onclick="markAsRead('${itemId}')"><div class="notif-icon" style="background: #15803d; border-radius: 50%;"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg></div><div class="notif-content"><p>${item.action}</p><span class="notif-time">${timeAgo} &middot; Click to view driver</span></div></a>`;
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
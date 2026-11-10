<?php /* Analytics view - vars from AnalyticsController::index() */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KimFresh - Analytics</title>
    <link rel="stylesheet" href="/php-web/public/css/theme.css">
    <link rel="stylesheet" href="/php-web/public/css/dashboard.css">
    <link rel="stylesheet" href="/php-web/public/css/responsive.css">
    
    <style>
        :root { --primary: #C34200; --primary-dark: #9a3400; }
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 24px; margin-top: 20px; }
        .stat-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border-left: 5px solid #C34200; }
        .stat-card.green { border-left-color: #15803d; }
        .stat-card.blue { border-left-color: #2563eb; }
        .stat-card p { margin: 0; color: #666; font-size: 13px; font-weight: 700; }
        .stat-card h2 { margin: 5px 0 0; font-size: 28px; color: #333; }

        .table-section { background: white; border-radius: 16px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 24px; }
        .table-section h3 { margin-top: 0; color: #333; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px; font-size: 13px; color: #666; border-bottom: 1px solid #eee; background: #f8f9fa; }
        td { padding: 12px; border-bottom: 1px solid #f5f5f5; font-size: 13px; }

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
        <aside class="sidebar">
            <div class="sidebar-brand">
                <img src="/php-web/public/logo.png" alt="KimFresh" class="logo">
                <p>Administration</p>
            </div>
            <nav class="menu">
                <a href="/php-web/public/dashboard">Dashboard</a>
                <a href="/php-web/public/orders">Orders</a>
                <a href="/php-web/public/products">Products</a>
                <a href="/php-web/public/drivers">Drivers</a>
                <a href="/php-web/public/analytics" class="active">Analytics</a>
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
                <div style="display:flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                    <div>
                        <p class="overview-label">Reports</p>
                        <h1 style="margin:0;">Analytics & Insights</h1>
                        <p class="page-sub" style="margin-top:5px;">Analyze business performance and driver efficiency.</p>
                    </div>
                </div>

                <!-- STATS GRID -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <p>Total Orders (All Time)</p>
                        <h2><?php echo $total_orders; ?></h2>
                    </div>
                    <div class="stat-card green">
                        <p>Delivered Orders</p>
                        <h2><?php echo $delivered; ?></h2>
                    </div>
                    <!-- 🔥 REPLACED SUCCESS RATE WITH ESTIMATED EARNINGS -->
                    <div class="stat-card blue">
                        <p>Estimated Earnings</p>
                        <h2>₱<?php echo number_format($total_earnings, 2); ?></h2>
                    </div>
                </div>

                <!-- TOP PRODUCTS TABLE -->
                <section class="table-section">
                    <h3>Top 5 Best-Selling Products</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Product Name</th>
                                <th>Total Sold</th>
                                <th>Total Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $rank = 1;
                            if ($top_products->num_rows > 0) {
                                while($row = $top_products->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td><strong>#" . $rank++ . "</strong></td>";
                                    echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                                    echo "<td>" . $row['total_sold'] . " units</td>";
                                    echo "<td>₱" . number_format((float)($row['total_revenue'] ?? 0), 2) . "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='4' style='text-align:center;'>No delivered orders yet.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </section>

                <!-- DRIVER LEADERBOARD -->
                <section class="table-section">
                    <h3>Driver Leaderboard</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Driver Name</th>
                                <th>Completed Deliveries</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $rank = 1;
                            if ($top_drivers->num_rows > 0) {
                                while($row = $top_drivers->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td><strong>#" . $rank++ . "</strong></td>";
                                    echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                                    echo "<td>" . $row['total_deliveries'] . " deliveries</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='3' style='text-align:center;'>No drivers have completed deliveries yet.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </section>
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
            if (!menu.contains(event.target)) {
                document.getElementById('profileDropdown').classList.remove('show');
            }
        });

        // =========================================
        // REMOVE HIGHLIGHT & CLEAR NOTIFICATION ON "VIEW"
        // =========================================
        function markOrderAsViewed(orderId) {
            let viewedOrders = JSON.parse(localStorage.getItem('viewedOrders') || '[]');
            if (!viewedOrders.includes(orderId.toString())) {
                viewedOrders.push(orderId.toString());
                localStorage.setItem('viewedOrders', JSON.stringify(viewedOrders));
            }

            let readNotifIds = JSON.parse(localStorage.getItem('readNotifIds') || '[]');
            const notifId = 'order-' + orderId;
            if (!readNotifIds.includes(notifId)) {
                readNotifIds.push(notifId);
                localStorage.setItem('readNotifIds', JSON.stringify(readNotifIds));
            }
        }

        // =========================================
        // REAL-TIME NOTIFICATIONS & DROPDOWN
        // =========================================
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
            const dropdown = document.getElementById('notifDropdown');
            dropdown.classList.toggle('show');
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
                                    <a href="orders.php?status=pending&highlight=${item.order_id}" class="notif-item" style="${readStyle}" data-notif-id="${itemId}" onclick="markAsRead('${itemId}'); markOrderAsViewed(${item.order_id});">
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
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

        /* Filter Dropdown Style */
        .filter-select {
            appearance: none; -webkit-appearance: none; padding: 9px 38px 9px 18px; border-radius: 8px;
            border: 1px solid var(--primary); font-weight: 800; font-size: 14px; color: var(--primary);
            background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' fill='none' stroke='%23C34200' stroke-width='2' stroke-linecap='round'/%3E%3C/svg%3E") no-repeat right 14px center;
            cursor: pointer; outline: none; font-family: inherit;
        }

        /* ---------- Sidebar (matches Figma) ---------- */
        .sidebar { background: linear-gradient(165deg, #d94a00 0%, #cf561a 55%, #c96a3a 100%) !important; padding-right: 0 !important; }
        .sidebar .sidebar-brand p { font-size: 13px; font-weight: 600; color: #fff; opacity: .95; }
        .sidebar .menu { display: flex; flex-direction: column; gap: 4px; padding: 0 !important; margin-top: 40px; }
        .sidebar .menu a { display: flex; align-items: center; gap: 14px; margin: 0 0 0 12px; padding: 15px 22px; color: #fff; font-size: 17px; font-weight: 800; text-decoration: none; border-radius: 999px 0 0 999px; background: transparent; transition: background .15s; }
        .sidebar .menu a svg { width: 18px; height: 18px; flex-shrink: 0; fill: currentColor; }
        .sidebar .menu a:hover { background: rgba(255,255,255,.14); }
        .sidebar .menu a.active { background: #f4f5f9; color: #d9480f; box-shadow: none; }
        /* ---------- Sidebar brand (same as Drivers) ---------- */
        .sidebar .sidebar-brand { display: flex !important; flex-direction: column !important; align-items: center !important; justify-content: center !important; text-align: center !important; padding: 28px 16px 22px !important; margin: 0 !important; border-bottom: 1px solid rgba(255,255,255,.18) !important; }
        .sidebar .sidebar-brand .logo { display: block !important; margin: 0 auto !important; }
        .sidebar .sidebar-brand p { margin: 12px 0 0 !important; text-align: center !important; }

        .table-section { background: white; border-radius: 16px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 24px; }
        .table-section h3 { margin-top: 0; color: #333; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px; font-size: 13px; color: #666; border-bottom: 1px solid #eee; background: #f8f9fa; }
        td { padding: 12px; border-bottom: 1px solid #f5f5f5; font-size: 13px; }

        .profile-menu { position: relative; }
        .profile-trigger { display: flex; align-items: center; gap: 10px; background: transparent; border: none; cursor: pointer; padding: 8px; border-radius: 10px; transition: 0.2s; }
        .profile-trigger:hover { background: var(--primary-soft); }
        .profile-dropdown { position: absolute; top: 100%; right: 0; background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); width: 200px; padding: 8px; display: none; z-index: 999; border: 1px solid #eee; }
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
        
        .notification-bell { position: relative; display: flex; align-items: center; justify-content: center; width: 48px; height: 48px; border-radius: 50%; background: var(--primary); cursor: pointer; }
        .notification-bell:hover { background: #f0f0f0; }
        
        .notif-badge { position: absolute; top: -6px; right: -8px; background: #dc2626; color: #fff; font-size: 11px; font-weight: 800; min-width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2px solid #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }

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
    
        /* ---------- Sidebar typography (same on all pages) ---------- */
        .sidebar, .sidebar * { font-family: 'Nunito Sans', sans-serif !important; }
        .sidebar .sidebar-brand .logo { width: 120px !important; max-width: 100% !important; height: auto !important; }
        .sidebar .sidebar-brand p { font-size: 13px !important; font-weight: 600 !important; color: #fff !important; opacity: .95 !important; }
        .sidebar .menu a { font-size: 17px !important; font-weight: 800 !important; }
    .pop.show { display: block !important; }
.menu-wrap { position: relative; display: inline-block; }
</style>
<link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/php-web/public/css/app-shell.css?v=1">
        <style>
        .menu-wrap { position: relative; display: inline-block; }
        .pill-btn.solid { height: 42px; padding: 0 16px; font-size: 18px; font-weight: 900; letter-spacing: 1px; min-width: 42px; justify-content: center; background: #C34200; color: #fff; border: 1px solid #C34200; border-radius: 999px; cursor: pointer; display: inline-flex; align-items: center; }
        .pop { position: absolute; top: 46px; right: 0; min-width: 190px; background: #fff; border: 1px solid #eee; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); padding: 8px; display: none; z-index: 999; }
        .pop.show { display: block; }
        .pop a { display: block; width: 100%; text-align: left; padding: 9px 12px; font-size: 13px; font-weight: 600; color: #333; text-decoration: none; border-radius: 8px; }
        .pop a:hover { background: #fdf0e6; color: #C34200; }
        .pop.show { display: block !important; }
.menu-wrap { position: relative; display: inline-block; }
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
                <a href="/php-web/public/dashboard">
                    <svg viewBox="0 0 24 24"><path d="M4 4h16a1 1 0 0 1 1 1v4H3V5a1 1 0 0 1 1-1zm-1 7h8v9H4a1 1 0 0 1-1-1v-8zm10 0h8v8a1 1 0 0 1-1 1h-7v-9z"/></svg>
                    Dashboard
                </a>
                <a href="/php-web/public/products">
                    <svg viewBox="0 0 24 24"><path d="M6 2h12a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2zm2 5v2h8V7H8zm0 4v2h8v-2H8zm0 4v2h5v-2H8z"/></svg>
                    Products
                </a>
                <a href="/php-web/public/orders" >
                    <svg viewBox="0 0 24 24"><path d="M12 2 2 7l10 5 10-5-10-5zm-8.5 8.2L2 11l10 5 10-5-1.5-.8L12 14 3.5 10.2zm0 4L2 15l10 5 10-5-1.5-.8L12 18l-8.5-3.8z"/></svg>
                    Orders
                </a>
                <a href="/php-web/public/drivers">
                    <svg viewBox="0 0 24 24"><path d="M12 2a4 4 0 0 1 4 4v1h1a2 2 0 0 1 2 2v3a7 7 0 0 1-14 0V9a2 2 0 0 1 2-2h1V6a4 4 0 0 1 4-4zM5 20h14v2H5v-2z"/></svg>
                    Drivers
                </a>
                
                <!-- ADDED ANALYTICS LINK -->
                <a href="/php-web/public/analytics" class="active">
                    <svg viewBox="0 0 24 24"><path d="M3 3v18h18V3H3zm16 16H5V5h14v14zM7 16h2V9H7v7zm4 0h2V7h-2v9zm4 0h2v-4h-2v4z"/></svg>
                    Analytics
                </a>
            </nav>
        </aside>

        <div class="main-area">
            <header class="top-header">
                
                <div class="notification-container" id="notifContainer">
                    <div class="notification-bell" onclick="toggleNotifications(event)">
                        <svg viewBox="0 0 24 24" fill="#fff" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                        <span id="notif-badge" class="notif-badge" style="display: none;">0</span>
                    </div>
                    <div class="notif-dropdown" id="notifDropdown">
                        <div class="notif-header"><h3>Notifications</h3><a href="/php-web/public/orders" class="notif-mark-read">View All</a></div>
                        <div class="notif-list" id="notifList"><div class="notif-empty">No new orders</div></div>
                    </div>
                </div>

                <div class="profile-menu" id="profileMenu">
                    <button type="button" class="profile-trigger" onclick="toggleProfileMenu(event)">
                        <div class="avatar">A</div>
                        <div class="user-info" style="text-align: left;">
                            <p class="user-name" style="margin: 0;"><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?></p>
                            <p class="user-role" style="margin: 0;">Admin</p>
                        </div>
                        <span class="profile-chevron"><svg width="8" height="8" viewBox="0 0 12 8"><path d="M1 1l5 5 5-5" fill="none" stroke="#666" stroke-width="2" stroke-linecap="round"/></svg></span>
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
    <!-- Page Header with Filter and Kebab Menu -->
    <div class="page-head" style="display:flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; gap: 12px; flex-wrap: wrap;">
        <div>
            <p class="overview-label" style="margin:0 0 2px; color:#C34200; font-weight:800; font-size:18px;">Reports</p>
            <h1 style="margin:0;">Analytics</h1>
            <p style="margin-top:5px;">Analyze business performance and driver efficiency.</p>
        </div>
        
        <div style="display: flex; gap: 15px; align-items: center;">
            <!-- NEW FILTER DROPDOWN -->
            <select class="filter-select" onchange="window.location.href='?filter=' + this.value;">
                <option value="all"     <?= ($filter ?? 'all') === 'all'     ? 'selected' : '' ?>>All Time</option>
                <option value="weekly"  <?= ($filter ?? 'all') === 'weekly'  ? 'selected' : '' ?>>This Week</option>
                <option value="monthly" <?= ($filter ?? 'all') === 'monthly' ? 'selected' : '' ?>>This Month</option>
                <option value="yearly"  <?= ($filter ?? 'all') === 'yearly'  ? 'selected' : '' ?>>This Year</option>
            </select>

            <div class="menu-wrap">
                <button type="button" class="pill-btn solid" onclick="togglePop(event, 'exportPopAnalytics')" style="position: relative; z-index: 999; padding: 0 16px; font-size: 18px; font-weight: 900; letter-spacing: 1px; min-width: 42px; justify-content: center; background: #C34200; color: #fff; border-radius: 999px; border: none; height: 48px; cursor: pointer;" title="Options">&#8942;</button>
                <div class="pop" id="exportPopAnalytics" style="position: absolute; top: 52px; right: 0; min-width: 190px; background: #fff; border: 1px solid #eee; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); padding: 8px; display: none; z-index: 999;">
                    <!-- Add filter parameter to export links -->
                    <a href="/php-web/public/analytics/export_pdf?filter=<?= $filter ?? 'all' ?>" style="display: block; padding: 9px 12px; font-size: 13px; font-weight: 600; color: #333; text-decoration: none; border-radius: 8px;">Export PDF</a>
                    <a href="/php-web/public/analytics/export_csv?filter=<?= $filter ?? 'all' ?>" style="display: block; padding: 9px 12px; font-size: 13px; font-weight: 600; color: #333; text-decoration: none; border-radius: 8px;">Export CSV</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="stats-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 24px;">
        <div class="stat-card" style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border-left: 5px solid #C34200;">
            <p style="margin: 0; color: #666; font-size: 13px; font-weight: 700;">Total Orders (<?= ucfirst($filter ?? 'all') ?>)</p>
            <h2 style="margin: 5px 0 0; font-size: 28px; color: #333;"><?= $total_orders ?? 0 ?></h2>
        </div>
        <div class="stat-card" style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border-left: 5px solid #15803d;">
            <p style="margin: 0; color: #666; font-size: 13px; font-weight: 700;">Delivered Orders</p>
            <h2 style="margin: 5px 0 0; font-size: 28px; color: #333;"><?= $delivered_orders ?? 0 ?></h2>
        </div>
        <div class="stat-card" style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border-left: 5px solid #1d4ed8;">
            <p style="margin: 0; color: #666; font-size: 13px; font-weight: 700;">Estimated Earnings</p>
            <h2 style="margin: 5px 0 0; font-size: 28px; color: #333;">₱<?= number_format($total_earnings ?? 0, 2) ?></h2>
        </div>
    </div>

    <!-- Top Selling Products -->
    <div class="table-section" style="background: #fff; border-radius: 16px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 24px;">
        <h3 style="margin-top: 0; font-size: 18px; color: #333;">Top 5 Best-Selling Products</h3>
        <div class="table-responsive" style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f1f2f6; text-align: left;">
                        <th style="padding: 12px; font-size: 12px; color: #666;">Rank</th>
                        <th style="padding: 12px; font-size: 12px; color: #666;">Product Name</th>
                        <th style="padding: 12px; font-size: 12px; color: #666;">Total Sold</th>
                        <th style="padding: 12px; font-size: 12px; color: #666;">Total Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (isset($top_products) && $top_products->num_rows > 0): $rank = 1; while($row = $top_products->fetch_assoc()): ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 12px; font-size: 13px; font-weight: bold;">#<?= $rank++ ?></td>
                            <td style="padding: 12px; font-size: 13px;"><?= htmlspecialchars($row['name']) ?></td>
                            <td style="padding: 12px; font-size: 13px;"><?= (int)$row['total_sold'] ?> units</td>
                            <td style="padding: 12px; font-size: 13px;">₱<?= number_format($row['total_revenue'] ?? 0, 2) ?></td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="4" style="padding: 20px; text-align: center; color: #777;">No products sold for this period.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Driver Leaderboard -->
    <div class="table-section" style="background: #fff; border-radius: 16px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
        <h3 style="margin-top: 0; font-size: 18px; color: #333;">Driver Leaderboard</h3>
        <div class="table-responsive" style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f1f2f6; text-align: left;">
                        <th style="padding: 12px; font-size: 12px; color: #666;">Rank</th>
                        <th style="padding: 12px; font-size: 12px; color: #666;">Driver Name</th>
                        <th style="padding: 12px; font-size: 12px; color: #666;">Completed Deliveries</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (isset($top_drivers) && $top_drivers->num_rows > 0): $rank = 1; while($row = $top_drivers->fetch_assoc()): ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 12px; font-size: 13px; font-weight: bold;">#<?= $rank++ ?></td>
                            <td style="padding: 12px; font-size: 13px;"><?= htmlspecialchars($row['name']) ?></td>
                            <td style="padding: 12px; font-size: 13px;"><?= (int)$row['total_deliveries'] ?> deliveries</td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="3" style="padding: 20px; text-align: center; color: #777;">No completed deliveries for this period.</td></tr>
                    <?php endif; ?>
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
            const notif = document.getElementById('notifDropdown');
            if (notif) notif.classList.remove('show');
            document.querySelectorAll('.pop').forEach(p => p.classList.remove('show'));
            const prof = document.getElementById('profileDropdown');
            if (prof) prof.classList.toggle('show');
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
            const prof = document.getElementById('profileDropdown');
            if (prof) prof.classList.remove('show');
            document.querySelectorAll('.pop').forEach(p => p.classList.remove('show'));
            const notif = document.getElementById('notifDropdown');
            if (notif) notif.classList.toggle('show');
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
            
        // Close all when clicking outside
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('profileMenu');
            if (menu && !menu.contains(event.target)) {
                const prof = document.getElementById('profileDropdown');
                if (prof) prof.classList.remove('show');
            }
            const c = document.getElementById('notifContainer');
            if (c && !c.contains(event.target)) {
                const d = document.getElementById('notifDropdown');
                if (d) d.classList.remove('show');
            }
            if (!event.target.closest('.menu-wrap')) {
                document.querySelectorAll('.pop').forEach(p => p.classList.remove('show'));
            }
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
        
        // Close all when clicking outside
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('profileMenu');
            if (menu && !menu.contains(event.target)) {
                const prof = document.getElementById('profileDropdown');
                if (prof) prof.classList.remove('show');
            }
            const c = document.getElementById('notifContainer');
            if (c && !c.contains(event.target)) {
                const d = document.getElementById('notifDropdown');
                if (d) d.classList.remove('show');
            }
            if (!event.target.closest('.menu-wrap')) {
                document.querySelectorAll('.pop').forEach(p => p.classList.remove('show'));
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
            const prof = document.getElementById('profileDropdown');
            if (prof) prof.classList.remove('show');
            document.querySelectorAll('.pop').forEach(p => p.classList.remove('show'));
            const notif = document.getElementById('notifDropdown');
            if (notif) notif.classList.toggle('show');
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
        
        // Close all when clicking outside
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('profileMenu');
            if (menu && !menu.contains(event.target)) {
                const prof = document.getElementById('profileDropdown');
                if (prof) prof.classList.remove('show');
            }
            const c = document.getElementById('notifContainer');
            if (c && !c.contains(event.target)) {
                const d = document.getElementById('notifDropdown');
                if (d) d.classList.remove('show');
            }
            if (!event.target.closest('.menu-wrap')) {
                document.querySelectorAll('.pop').forEach(p => p.classList.remove('show'));
            }
        });
    </script>


<script>
    function togglePop(event, id) {
        event.stopPropagation();
        var pop = document.getElementById(id);
        if (!pop) return;
        
        // Hide all other popups
        var allPops = document.querySelectorAll('.pop');
        for (var i = 0; i < allPops.length; i++) {
            allPops[i].style.display = 'none';
            allPops[i].classList.remove('show');
        }

        // Toggle the clicked one
        if (pop.style.display === 'block') {
            pop.style.display = 'none';
            pop.classList.remove('show');
        } else {
            pop.style.display = 'block';
            pop.classList.add('show');
        }
    }

    // Close popup if clicking outside
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.menu-wrap')) {
            var allPops = document.querySelectorAll('.pop');
            for (var i = 0; i < allPops.length; i++) {
                allPops[i].style.display = 'none';
                allPops[i].classList.remove('show');
            }
        }
    });
</script>
</body>
</html>
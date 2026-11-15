<?php /* Order detail view - vars from ViewOrderController::show() */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?php echo $order_id; ?> - Receipt</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/php-web/public/css/theme.css">
    <link rel="stylesheet" href="/php-web/public/css/dashboard.css">
    <link rel="stylesheet" href="/php-web/public/css/orders.css">
    <link rel="stylesheet" href="/php-web/public/css/flow.css">
    <link rel="stylesheet" href="/php-web/public/css/responsive.css">
    <link rel="stylesheet" href="/php-web/public/css/app-shell.css?v=1">
    
    <style>
        :root { --primary: #C34200; --primary-dark: #9a3400; }

        /* ---------- Sidebar ---------- */
        .sidebar { background: linear-gradient(165deg, #d94a00 0%, #cf561a 55%, #c96a3a 100%) !important; padding-right: 0 !important; }
        .sidebar .sidebar-brand p { font-size: 13px; font-weight: 600; color: #fff; opacity: .95; }
        .sidebar .menu { display: flex; flex-direction: column; gap: 4px; padding: 0 !important; margin-top: 40px; }
        .sidebar .menu a { display: flex; align-items: center; gap: 14px; margin: 0 0 0 12px; padding: 15px 22px; color: #fff; font-size: 17px; font-weight: 800; text-decoration: none; border-radius: 999px 0 0 999px; background: transparent; transition: background .15s; }
        .sidebar .menu a svg { width: 18px; height: 18px; flex-shrink: 0; fill: currentColor; }
        .sidebar .menu a:hover { background: rgba(255,255,255,.14); }
        .sidebar .menu a.active { background: #f4f5f9; color: #d9480f; box-shadow: none; }
        .sidebar .sidebar-brand { display: flex !important; flex-direction: column !important; align-items: center !important; justify-content: center !important; text-align: center !important; padding: 28px 16px 22px !important; margin: 0 !important; border-bottom: 1px solid rgba(255,255,255,.18) !important; }
        .sidebar .sidebar-brand .logo { display: block !important; margin: 0 auto !important; }
        .sidebar .sidebar-brand p { margin: 12px 0 0 !important; text-align: center !important; }
        
        /* ---------- Layout ---------- */
        .main-area { margin-left: 270px !important; width: calc(100% - 270px) !important; display: flex; flex-direction: column; min-height: 100vh; }
        .top-header { width: 100% !important; background: #fff !important; padding: 30px 50px !important; border-bottom: 1px solid #f0f0f0 !important; border-radius: 0 !important; margin: 0 !important; box-shadow: 0 2px 10px rgba(0,0,0,0.02) !important; display: flex !important; justify-content: flex-end !important; align-items: center !important; gap: 30px; box-sizing: border-box !important; position: sticky; top: 0; z-index: 999; }
        .main-content { padding: 24px 40px 40px !important; background: #f4f6fa; flex-grow: 1 !important; }
        .overview-label { margin: 0 0 2px; color: var(--primary); font-weight: 800; font-size: 18px; }

        /* ---------- Profile & Notifications ---------- */
        .profile-menu { position: relative; }
        .profile-trigger { display: flex; align-items: center; gap: 12px; background: transparent; border: none; cursor: pointer; padding: 4px 8px; border-radius: 10px; transition: 0.2s; }
        .profile-trigger:hover { background: #fdf0e6; }
        .profile-trigger .avatar { width: 54px; height: 54px; border-radius: 50%; background: #F2B58F; color: var(--primary); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 20px; }
        .profile-trigger .user-name { font-size: 16px; font-weight: 700; color: #222; }
        .profile-trigger .user-role { font-size: 13px; color: #666; }
        .profile-chevron { width: 16px; height: 16px; border-radius: 50%; border: 1px solid #ccc; display: flex; align-items: center; justify-content: center; margin-left: 10px; }
        .profile-dropdown { position: absolute; top: 100%; right: 0; background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); width: 200px; padding: 8px; display: none; z-index: 100; border: 1px solid #eee; }
        .profile-dropdown.show { display: block; }
        .dropdown-account { padding: 12px; border-bottom: 1px solid #eee; margin-bottom: 8px; }
        .dropdown-account strong { display: block; font-size: 14px; color: #333; }
        .dropdown-account span { font-size: 12px; color: #777; }
        .dropdown-item { display: flex; align-items: center; gap: 10px; padding: 10px 12px; text-decoration: none; color: #333; font-size: 13px; font-weight: 600; border-radius: 8px; transition: 0.2s; }
        .dropdown-item:hover { background: #fdf0e6; color: var(--primary); }
        
        .notification-container { position: relative; margin-right: 0; }
        .notification-bell { position: relative; display: flex; align-items: center; justify-content: center; width: 48px; height: 48px; border-radius: 50%; background: var(--primary); cursor: pointer; }
        .notification-bell svg { width: 24px; height: 24px; }
        .notif-badge { position: absolute; top: -6px; right: -8px; background: #dc2626; color: #fff; font-size: 11px; font-weight: 800; min-width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2px solid #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .notif-dropdown { position: absolute; top: 45px; right: 0; width: 320px; background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); border: 1px solid #eee; display: none; z-index: 1000; color: #333; overflow: hidden; }
        .notif-dropdown.show { display: block; }
        .notif-header { display: flex; justify-content: space-between; align-items: center; padding: 15px; border-bottom: 1px solid #eee; }
        .notif-header h3 { margin: 0; font-size: 16px; font-weight: 800; color: #333; }
        .notif-mark-read { color: var(--primary); text-decoration: none; font-size: 13px; font-weight: 600; }
        .notif-mark-read:hover { text-decoration: underline; }
        .notif-list { max-height: 350px; overflow-y: auto; }
        .notif-item { display: flex; gap: 12px; padding: 12px 15px; border-bottom: 1px solid #f5f5f5; text-decoration: none; color: #333; transition: background 0.2s; }
        .notif-item:hover { background: #fdf0e6; }
        .notif-icon { width: 45px; height: 45px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-weight: bold; font-size: 16px; }
        .notif-content { flex: 1; display: flex; flex-direction: column; justify-content: center; }
        .notif-content p { margin: 0 0 4px 0; font-size: 13px; line-height: 1.3; color: #444; }
        .notif-content strong { font-weight: 800; color: #333; }
        .notif-time { color: #888; font-size: 11px; font-weight: 600; }
        .notif-empty { padding: 20px; text-align: center; color: #888; font-size: 13px; }

        /* ---------- Kebab & Export ---------- */
        .menu-wrap { position: relative; display: inline-block; z-index: 999; }
        .pill-btn.solid { height: 44px; padding: 0 16px; font-size: 20px; font-weight: 900; letter-spacing: 1px; min-width: 48px; justify-content: center; background: #C34200; color: #fff; border-radius: 999px; border: none; cursor: pointer; display: inline-flex; align-items: center; }
        .pop { position: absolute; top: 52px; right: 0; min-width: 190px; background: #fff; border: 1px solid #eee; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); padding: 8px; display: none; z-index: 999; }
        .pop.show { display: block; }
        .pop a { display: block; width: 100%; text-align: left; padding: 9px 12px; font-size: 13px; font-weight: 600; color: #333; text-decoration: none; border-radius: 8px; }
        .pop a:hover { background: #fdf0e6; color: #C34200; }

        /* ---------- Status ---------- */
        .status-pill.pending { color: #b45309; background: #fef3c7; }
        .status-pill.approved { color: #15803d; background: #dcfce7; }
        .status-pill.delivered { color: #1d4ed8; background: #dbeafe; }
        .status-pill.rejected, .status-pill.cancelled { color: #991b1b; background: #fee2e2; }
        .status-pill.waiting_for_pickup, .status-pill.in_transit { color: #c2410c; background: #ffedd5; }

        /* ---------- Info Grid ---------- */
        .info-grid { display: grid; grid-template-columns: minmax(200px, 1fr) minmax(200px, 1fr) minmax(300px, 1.5fr); gap: 24px; background: #fff; padding: 24px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-top: 20px; }
        .info-grid h3 { margin-top: 0; font-size: 18px; border-bottom: 1px solid #eee; padding-bottom: 8px; color: #333; }
        .info-grid p { margin: 10px 0; font-size: 15px; color: #555; }
        .info-table { width: 100%; border-collapse: collapse; font-size: 15px; }
        .info-table th { text-align: left; color: #777; background: #f9f9f9; padding: 12px; border-bottom: 2px solid #eee; font-size: 14px; }
        .info-table td { padding: 12px; border-bottom: 1px solid #eee; }
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
                <a href="/php-web/public/dashboard"><svg viewBox="0 0 24 24"><path d="M4 4h16a1 1 0 0 1 1 1v4H3V5a1 1 0 0 1 1-1zm-1 7h8v9H4a1 1 0 0 1-1-1v-8zm10 0h8v8a1 1 0 0 1-1 1h-7v-9z"/></svg>Dashboard</a>
                <a href="/php-web/public/products"><svg viewBox="0 0 24 24"><path d="M6 2h12a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2zm2 5v2h8V7H8zm0 4v2h8v-2H8zm0 4v2h5v-2H8z"/></svg>Products</a>
                <a href="/php-web/public/orders" class="active"><svg viewBox="0 0 24 24"><path d="M12 2 2 7l10 5 10-5-10-5zm-8.5 8.2L2 11l10 5 10-5-1.5-.8L12 14 3.5 10.2zm0 4L2 15l10 5 10-5-1.5-.8L12 18l-8.5-3.8z"/></svg>Orders</a>
                <a href="/php-web/public/drivers"><svg viewBox="0 0 24 24"><path d="M12 2a4 4 0 0 1 4 4v1h1a2 2 0 0 1 2 2v3a7 7 0 0 1-14 0V9a2 2 0 0 1 2-2h1V6a4 4 0 0 1 4-4zM5 20h14v2H5v-2z"/></svg>Drivers</a>
                <a href="/php-web/public/analytics"><svg viewBox="0 0 24 24"><path d="M3 3v18h18V3H3zm16 16H5V5h14v14zM7 16h2V9H7v7zm4 0h2V7h-2v9zm4 0h2v-4h-2v4z"/></svg>Analytics</a>
            </nav>
        </aside>
        
        <div class="main-area">
            <header class="top-header">
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
                            <strong><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?></strong>
                            <span><?= htmlspecialchars($_SESSION['admin_email'] ?? 'admin@kimfresh.com') ?></span>
                        </div>
                        <a href="/php-web/public/settings" class="dropdown-item">Settings</a>
                        <a href="/php-web/public/logs" class="dropdown-item">Activity Logs</a>
                        <a href="/php-web/public/logout" class="dropdown-item" style="color: #C34200;">Logout</a>
                    </div>
                </div>
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
            </header>

            <main class="main-content">
                <div style="margin-bottom: 20px;">
                    <a href="/php-web/public/orders" style="text-decoration: none; color: #C34200; font-weight: bold; font-size: 14px;">&larr; Back to Orders</a>
                </div>

                <!-- Title and Kebab Menu -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 2px solid #C34200; padding-bottom: 14px;">
                    <h1 style="margin: 0; color: #C34200; font-size: 32px; font-weight: 800;">Order #<?= $order['order_id'] ?? 0 ?></h1>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div class="menu-wrap">
                            <button type="button" class="pill-btn solid" onclick="togglePop(event, 'exportPopViewOrder')" title="Options">&#8942;</button>
                            <div class="pop" id="exportPopViewOrder">
                                <a href="/php-web/public/orders/view/export_pdf?id=<?= $order_id ?>">Export PDF</a>
                                <a href="/php-web/public/orders/view/export_csv?id=<?= $order_id ?>">Export CSV</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Status -->
                <div style="margin-bottom: 24px;">
                    <span class="status-pill <?= strtolower($order['order_status'] ?? 'pending') ?>">
                        <?= strtoupper($order['order_status'] ?? 'PENDING') ?>
                    </span>
                </div>

                <!-- Info Grid -->
                <div class="info-grid">
                    <div>
                        <h3>Retailer Information</h3>
                        <p><strong>Name:</strong> <?= htmlspecialchars(is_array($order['retailer_name'] ?? '') ? 'Unknown' : ($order['retailer_name'] ?? 'Unknown')) ?></p>
                        <p><strong>Code:</strong> <?= htmlspecialchars(is_array($order['customer_code'] ?? '') ? 'N/A' : ($order['customer_code'] ?? 'N/A')) ?></p>
                        <p><strong>Phone:</strong> <?= htmlspecialchars(is_array($order['phone'] ?? '') ? 'N/A' : ($order['phone'] ?? 'N/A')) ?></p>
                        <p><strong>Address:</strong> <?= htmlspecialchars(is_array($order['address'] ?? '') ? 'N/A' : ($order['address'] ?? 'N/A')) ?></p>
                    </div>
                    <div>
                        <h3>Order Information</h3>
                        <p><strong>Date:</strong> <?= date('M d, Y h:i A', strtotime($order['order_date'] ?? 'now')) ?></p>
                        <p><strong>Status:</strong> <?= ucfirst(htmlspecialchars($order['order_status'] ?? 'Pending')) ?></p>
                        <p><strong>Driver:</strong> <?= htmlspecialchars(is_array($driver_name ?? '') ? 'Unassigned' : ($driver_name ?? 'Unassigned')) ?></p>
                        <p><strong>Total Amount:</strong> <span style="color: #C34200; font-weight: bold;">&#8369;<?= number_format($order['total_amount'] ?? 0, 2) ?></span></p>
                    </div>
                    <div>
                        <h3>Items Ordered</h3>
                        <table class="info-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Qty</th>
                                    <th style="text-align: right;">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($items) && $items->num_rows > 0): while ($item = $items->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['product_name'] ?? $item['name'] ?? 'Unknown Product') ?></td>
                                    <td><?= (int)($item['quantity'] ?? 1) ?></td>
                                    <td style="text-align: right;">
                                        <?php 
                                            $price = $item['unit_price'] ?? $item['price'] ?? 0;
                                            $qty = $item['quantity'] ?? 1;
                                            $subtotal = $item['subtotal'] ?? ($price * $qty);
                                        ?>
                                        &#8369;<?= number_format($subtotal, 2) ?>
                                    </td>
                                </tr>
                                <?php endwhile; else: ?>
                                <tr><td colspan="3" style="text-align: center; padding: 20px; color: #888;">No items found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        function toggleProfileMenu(event) {
            event.stopPropagation();
            const notif = document.getElementById('notifDropdown');
            if (notif) notif.classList.remove('show');
            document.querySelectorAll('.pop').forEach(p => p.classList.remove('show'));
            const prof = document.getElementById('profileDropdown');
            if (prof) prof.classList.toggle('show');
        }
        document.addEventListener('click', function(event) { const menu = document.getElementById('profileMenu'); if (menu && !menu.contains(event.target)) document.getElementById('profileDropdown').classList.remove('show'); });
        
        function toggleNotifications(event) {
            event.stopPropagation();
            const prof = document.getElementById('profileDropdown');
            if (prof) prof.classList.remove('show');
            document.querySelectorAll('.pop').forEach(p => p.classList.remove('show'));
            const notif = document.getElementById('notifDropdown');
            if (notif) notif.classList.toggle('show');
        }
        document.addEventListener('click', function(event) { const container = document.getElementById('notifContainer'); const dropdown = document.getElementById('notifDropdown'); if (container && !container.contains(event.target)) { if(dropdown) dropdown.classList.remove('show'); } });

        function togglePop(event, id) {
            event.stopPropagation();
            const prof = document.getElementById('profileDropdown');
            if (prof) prof.classList.remove('show');
            const notif = document.getElementById('notifDropdown');
            if (notif) notif.classList.remove('show');
            
            const pop = document.getElementById(id);
            if (!pop) return;
            const wasOpen = pop.classList.contains('show');
            document.querySelectorAll('.pop').forEach(p => p.classList.remove('show'));
            if (!wasOpen) pop.classList.add('show');
        }
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.menu-wrap')) {
                document.querySelectorAll('.pop').forEach(p => p.classList.remove('show'));
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
</body>
</html>
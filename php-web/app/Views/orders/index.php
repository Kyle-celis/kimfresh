<?php /* Orders view - vars from OrderController::index() */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KimFresh - Orders</title>
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

        /* ---------- Filters (Tabs) ---------- */
        .toolbar { margin-bottom: 20px; }
        .tabs { display: flex; gap: 8px; flex-wrap: wrap; }
        .tab { padding: 8px 16px; background: #fff; border: 1px solid #ddd; border-radius: 999px; font-size: 13px; font-weight: 700; color: #555; text-decoration: none; transition: 0.2s; display: flex; align-items: center; gap: 6px; }
        .tab:hover { background: #fdf0e6; border-color: #C34200; color: #C34200; }
        .tab.active { background: #C34200; border-color: #C34200; color: #fff; }
        .tab span { background: #f1f2f6; color: #333; padding: 2px 8px; border-radius: 999px; font-size: 11px; }
        .tab.active span { background: rgba(255,255,255,0.3); color: #fff; }

        /* ---------- Table ---------- */
        .orders-box { background: #fff; border-radius: 16px; padding: 16px 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        thead tr { background: #f1f2f6; }
        th { text-align: center; padding: 16px 12px; font-size: 16px; color: #222; font-weight: 600; white-space: nowrap; }
        td { padding: 16px 12px; font-size: 15px; text-align: center; border-bottom: 1px solid #eee; color: #222; }
        tbody tr:hover { background: #fffaf6; }
        .col-checkbox { width: 40px; text-align: center; }
        .col-checkbox input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; accent-color: #C34200; }
        .num { text-align: right !important; font-variant-numeric: tabular-nums; }

        .status-pill { padding: 6px 14px; border-radius: 999px; font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block; }
        .status-pill.pending { color: #b45309; background: #fef3c7; }
        .status-pill.approved { color: #15803d; background: #dcfce7; }
        .status-pill.delivered { color: #1d4ed8; background: #dbeafe; }
        .status-pill.rejected, .status-pill.cancelled { color: #991b1b; background: #fee2e2; }
        .status-pill.waiting_for_pickup, .status-pill.in_transit { color: #c2410c; background: #ffedd5; }

        .action-group { display: flex; gap: 6px; justify-content: center; align-items: center; }
        .btn-approve { background: #15803d; color: white; padding: 6px 14px; font-size: 12px; font-weight: 700; border: none; border-radius: 6px; cursor: pointer; transition: 0.2s; }
        .btn-approve:hover { background: #166534; }
        .btn-reject { background: #dc2626; color: white; padding: 6px 14px; font-size: 12px; font-weight: 700; border: none; border-radius: 6px; cursor: pointer; transition: 0.2s; }
        .btn-reject:hover { background: #b91c1c; }
        .btn-view { background: #fff; color: #333; padding: 6px 14px; font-size: 12px; font-weight: 700; border: 1px solid #ddd; border-radius: 6px; cursor: pointer; transition: 0.2s; text-decoration: none; }
        .btn-view:hover { background: #f8f9fa; border-color: #C34200; color: #C34200; }

        /* ---------- Pagination & Bulk Bar ---------- */
        .pagination { display: flex; justify-content: center; gap: 6px; margin-top: 24px; }
        .pagination a, .pagination span { display: inline-flex; align-items: center; justify-content: center; min-width: 36px; height: 36px; padding: 0 12px; border-radius: 8px; font-size: 13px; font-weight: 700; text-decoration: none; border: 1px solid #ddd; color: #555; background: white; transition: 0.2s; }
        .pagination a:hover { background: #fdf0e6; border-color: #C34200; color: #C34200; }
        .pagination .active { background: #C34200; border-color: #C34200; color: white; }
        .pagination .disabled { opacity: 0.5; pointer-events: none; }

        .bulk-bar { position: fixed; bottom: -100px; left: 50%; transform: translateX(-50%); background: #333; color: white; padding: 15px 30px; border-radius: 12px; display: flex; align-items: center; gap: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); transition: bottom 0.3s ease; z-index: 1000; }
        .bulk-bar.show { bottom: 30px; }
        .bulk-bar span { font-weight: bold; font-size: 14px; }
        .bulk-btn { padding: 8px 16px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 13px; transition: 0.2s; }
        .bulk-btn.approve { background: #15803d; color: white; }
        .bulk-btn.reject { background: #dc2626; color: white; }
        .bulk-btn.cancel { background: transparent; color: #aaa; text-decoration: underline; padding: 8px 0; }
        .bulk-btn.cancel:hover { color: white; }
        
        .new-order-highlight { background-color: #fef3c7 !important; transition: background-color 3s ease-out; }
        .alert-success { background: #dcfce7; color: #166534; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; border: 1px solid #bbf7d0; }
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
                <?php if (!empty($_SESSION['is_superadmin'])): ?>
                    <a href="/php-web/public/superadmin"><svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg>Manage Admins</a>
                <?php endif; ?>
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
                <?php if (isset($_GET['error']) && $_GET['error'] === 'stock'): ?>
                  <div style="background:#fee2e2;color:#991b1b;padding:12px;border-radius:8px;margin-bottom:12px;font-weight:700; border: 1px solid #f87171;">
                    Approval failed: one or more products don't have enough stock.
                  </div>
                <?php endif; ?>
                <?php if (isset($_GET['bulk_success'])): ?>
                  <div class="alert-success">
                    Successfully processed <?php echo (int)$_GET['bulk_success']; ?> order(s).
                  </div>
                <?php endif; ?>

                <div style="display:flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                    <div>
                        <p class="overview-label">Manage</p>
                        <h1 style="margin:0; font-size: 32px; font-weight: 800; color: #111;">Orders</h1>
                        <p style="margin-top:5px; color: #666;">Review retailer orders. Approve or reject pending requests.</p>
                    </div>

                </div>

                <div class="toolbar" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                    <div class="tabs">
                        <a href="?status=all&sort=<?php echo $sort; ?>" class="tab <?php echo $current_filter == 'all' ? 'active' : ''; ?>" style="text-decoration:none;">All <span><?php echo ($counts['all'] ?? 0); ?></span></a>
                        <a href="?status=pending&sort=<?php echo $sort; ?>" class="tab <?php echo $current_filter == 'pending' ? 'active' : ''; ?>" style="text-decoration:none;">Pending <span><?php echo ($counts['pending'] ?? 0); ?></span></a>
                        <a href="?status=approved&sort=<?php echo $sort; ?>" class="tab <?php echo $current_filter == 'approved' ? 'active' : ''; ?>" style="text-decoration:none;">Approved <span><?php echo ($counts['approved'] ?? 0); ?></span></a>
                        <a href="?status=waiting_for_pickup&sort=<?php echo $sort; ?>" class="tab <?php echo $current_filter == 'waiting_for_pickup' ? 'active' : ''; ?>" style="text-decoration:none;">In Pickup <span><?php echo ($counts['waiting_for_pickup'] ?? 0); ?></span></a>
                        <a href="?status=delivered&sort=<?php echo $sort; ?>" class="tab <?php echo $current_filter == 'delivered' ? 'active' : ''; ?>" style="text-decoration:none;">Delivered <span><?php echo ($counts['delivered'] ?? 0); ?></span></a>
                        <a href="?status=rejected&sort=<?php echo $sort; ?>" class="tab <?php echo $current_filter == 'rejected' ? 'active' : ''; ?>" style="text-decoration:none;">Rejected <span><?php echo ($counts['rejected'] ?? 0); ?></span></a>
                        <a href="?status=cancelled&sort=<?php echo $sort; ?>" class="tab <?php echo $current_filter == 'cancelled' ? 'active' : ''; ?>" style="text-decoration:none;">Cancelled <span><?php echo ($counts['cancelled'] ?? 0); ?></span></a>
                                        </div>
                    <div class="menu-wrap">
                        <button type="button" class="pill-btn solid" onclick="togglePop(event, 'exportPopOrder')" title="Options">&#8942;</button>
                        <div class="pop" id="exportPopOrder">
                            <a href="?export_pdf=1" download="KimFresh_Orders.pdf">Export PDF</a>
                            <a href="/php-web/public/orders/export_csv">Export CSV</a>
                        </div>
                    </div>
                </div>

                <div class="orders-box">
                    <form method="POST" id="bulkForm">
                        <?= csrf_field() ?>
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th class="col-checkbox"><input type="checkbox" id="selectAll"></th>
                                    <th><a href="?status=<?php echo $current_filter; ?>&sort=<?php echo ($sort == 'id_asc' ? 'id_desc' : 'id_asc'); ?>" style="text-decoration:none; color:inherit;">Order &#8597;</a></th>
                                    <th><a href="?status=<?php echo $current_filter; ?>&sort=<?php echo ($sort == 'retailer_asc' ? 'retailer_desc' : 'retailer_asc'); ?>" style="text-decoration:none; color:inherit;">Retailer &#8597;</a></th>
                                    <th>Date</th>
                                    <th>Items</th>
                                    <th><a href="?status=<?php echo $current_filter; ?>&sort=<?php echo ($sort == 'total_asc' ? 'total_desc' : 'total_asc'); ?>" style="text-decoration:none; color:inherit;">Total &#8597;</a></th>
                                    <th><a href="?status=<?php echo $current_filter; ?>&sort=<?php echo ($sort == 'status_asc' ? 'status_desc' : 'status_asc'); ?>" style="text-decoration:none; color:inherit;">Status &#8597;</a></th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($orders->num_rows > 0) {
                                    while($row = $orders->fetch_assoc()) {
                                        $status = strtolower($row["order_status"]);
                                        $displayStatus = strtoupper(str_replace('_', ' ', $row["order_status"]));
                                        $item_count = (int)($row['total_items'] ?? 0);
                                        $orderTime = strtotime($row['order_date']);
                                        $currentTime = strtotime(date('Y-m-d H:i:s'));
                                        $isRecent = ($currentTime - $orderTime) < 120;
                                        $rowClass = $isRecent ? "class='new-order-highlight'" : "";
                                        
                                        echo "<tr $rowClass data-order-id='" . $row['order_id'] . "'>";
                                        
                                        if ($row["order_status"] == 'pending') {
                                            echo "<td class='col-checkbox'><input type='checkbox' name='selected_orders[]' value='" . $row["order_id"] . "' class='order-checkbox'></td>";
                                        } else {
                                            echo "<td class='col-checkbox'></td>";
                                        }
                                        
                                        echo "<td><span class='order-no'>#" . $row["order_id"] . "</span></td>";
                                        echo "<td><strong>" . htmlspecialchars($row["retailer_name"]) . "</strong></td>";
                                        echo "<td>" . date('M d, Y, g:i A', strtotime($row['order_date'])) . "</td>";
                                        echo "<td>" . $item_count . " items</td>";
                                        echo "<td class='num'>&#8369;" . number_format($row["total_amount"], 2) . "</td>";
                                        echo "<td><span class='status-pill " . $status . "'>" . $displayStatus . "</span></td>";
                                        
                                        echo "<td>";
                                        echo "<div class='action-group'>";
                                        
                                        if ($row["order_status"] == 'pending') {
                                            echo "<button type='button' name='approve_single' class='btn-approve' onclick='submitSingle(\"approve\", " . $row["order_id"] . ")'>Approve</button>";
                                            echo "<button type='button' name='reject_single' class='btn-reject' onclick='submitSingle(\"reject\", " . $row["order_id"] . ")'>Reject</button>";
                                        }
                                        
                                        echo "<a href='/php-web/public/orders/view?id=" . $row["order_id"] . "' class='btn-view' onclick='markOrderAsViewed(" . $row["order_id"] . ")'>View</a>";
                                        
                                        echo "</div>";
                                        echo "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='8' style='text-align:center; padding: 20px;'>No orders found for this filter.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                        <input type="hidden" name="bulk_action" id="bulkActionInput" value="">
                    </form>
                </div>

                <div class="bulk-bar" id="bulkBar">
                    <span id="bulkCount">0</span> orders selected
                    <button type="button" class="bulk-btn approve" onclick="submitBulk('approve')">Approve Selected</button>
                    <button type="button" class="bulk-btn reject" onclick="submitBulk('reject')">Reject Selected</button>
                    <button type="button" class="bulk-btn cancel" onclick="clearSelection()">Cancel</button>
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

    <dialog id="actionModal" style="width: 90%; max-width: 400px; border: none; border-radius: 12px; padding: 24px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); margin: auto;">
        <style>#actionModal::backdrop { background: rgba(0, 0, 0, 0.5); }</style>
        <h2 style="margin-top:0; color: #333;" id="modalTitle">Confirm Action</h2>
        <p style="font-size: 14px; color: #555; margin-bottom: 20px;" id="modalMessage">Are you sure you want to proceed?</p>
        <div style="display: flex; justify-content: flex-end; gap: 10px;">
            <button type="button" onclick="document.getElementById('actionModal').close()" style="padding: 10px 20px; background: #eee; border: none; border-radius: 8px; font-weight: bold; cursor: pointer;">Cancel</button>
            <button type="button" id="modalConfirmBtn" style="padding: 10px 20px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; color: white;">Confirm</button>
        </div>
    </dialog>

    <script>
        // Smart Toggle: Closes other menus when one opens
        function toggleProfileMenu(event) {
            event.stopPropagation();
            const notif = document.getElementById('notifDropdown');
            if (notif) notif.classList.remove('show');
            document.querySelectorAll('.pop').forEach(p => p.classList.remove('show'));
            const prof = document.getElementById('profileDropdown');
            if (prof) prof.classList.toggle('show');
        }
        
        function toggleNotifications(event) {
            event.stopPropagation();
            const prof = document.getElementById('profileDropdown');
            if (prof) prof.classList.remove('show');
            document.querySelectorAll('.pop').forEach(p => p.classList.remove('show'));
            const notif = document.getElementById('notifDropdown');
            if (notif) notif.classList.toggle('show');
        }

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

        // Close all when clicking outside
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('profileMenu');
            if (menu && !menu.contains(event.target)) {
                document.getElementById('profileDropdown').classList.remove('show');
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

        // PAGE ANIMATION
        if (sessionStorage.getItem('scrollPos')) { document.documentElement.style.visibility = 'hidden'; }
        window.addEventListener('load', function() {
            const savedPosition = sessionStorage.getItem('scrollPos');
            if (savedPosition) {
                window.scrollTo(0, parseInt(savedPosition));
                document.documentElement.style.visibility = 'visible';
                sessionStorage.removeItem('scrollPos');
            }
        });
        document.addEventListener('mousedown', function(event) {
            if (event.target.closest('.tabs a') || event.target.closest('th a') || event.target.closest('.pagination a')) {
                sessionStorage.setItem('scrollPos', window.scrollY);
            }
        });

        // BULK SELECT LOGIC
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.order-checkbox');
        const bulkBar = document.getElementById('bulkBar');
        const bulkCount = document.getElementById('bulkCount');

        function updateBulkBar() {
            const checkedCount = document.querySelectorAll('.order-checkbox:checked').length;
            bulkCount.textContent = checkedCount;
            if (checkedCount > 0) bulkBar.classList.add('show');
            else bulkBar.classList.remove('show');
        }

        if (selectAll) {
            selectAll.addEventListener('change', function() {
                checkboxes.forEach(cb => cb.checked = this.checked);
                updateBulkBar();
            });
        }
        checkboxes.forEach(cb => cb.addEventListener('change', updateBulkBar));

        function clearSelection() {
            if (selectAll) selectAll.checked = false;
            checkboxes.forEach(cb => cb.checked = false);
            updateBulkBar();
        }

        // CUSTOM MODAL LOGIC
        const actionModal = document.getElementById('actionModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalMessage = document.getElementById('modalMessage');
        const modalConfirmBtn = document.getElementById('modalConfirmBtn');
        let pendingAction = null;

        function openConfirmModal(title, message, btnText, btnColor, actionCallback) {
            modalTitle.textContent = title;
            modalMessage.textContent = message;
            modalConfirmBtn.textContent = btnText;
            modalConfirmBtn.style.background = btnColor;
            pendingAction = actionCallback;
            actionModal.showModal();
        }

        modalConfirmBtn.addEventListener('click', function() {
            if (pendingAction) { pendingAction(); actionModal.close(); }
        });

        function submitBulk(action) {
            const count = document.querySelectorAll('.order-checkbox:checked').length;
            const isApprove = action === 'approve';
            openConfirmModal(
                isApprove ? "Approve Orders" : "Reject Orders",
                "Are you sure you want to " + action + " these " + count + " orders?",
                isApprove ? "Approve Selected" : "Reject Selected",
                isApprove ? "#15803d" : "#dc2626",
                function() {
                    document.getElementById('bulkActionInput').value = action;
                    document.getElementById('bulkForm').submit();
                }
            );
        }

        function submitSingle(action, orderId) {
            const isApprove = action === 'approve';
            openConfirmModal(
                isApprove ? "Approve Order" : "Reject Order",
                "Are you sure you want to " + action + " Order #" + orderId + "?",
                isApprove ? "Approve" : "Reject",
                isApprove ? "#15803d" : "#dc2626",
                function() {
                    const form = document.getElementById('bulkForm');
                    const actionInput = document.createElement('input');
                    actionInput.type = 'hidden'; actionInput.name = action + '_order'; actionInput.value = '1';
                    form.appendChild(actionInput);
                    const idInput = document.createElement('input');
                    idInput.type = 'hidden'; idInput.name = 'order_id'; idInput.value = orderId;
                    form.appendChild(idInput);
                    form.submit();
                }
            );
        }

        let lastNotifCount = 0;
        function playBeep() {
            try {
                let context = new (window.AudioContext || window.webkitAudioContext)();
                let osc = context.createOscillator();
                osc.type = 'sine'; osc.frequency.value = 800;
                osc.connect(context.destination);
                osc.start(); osc.stop(context.currentTime + 0.15);
            } catch (e) {}
        }

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
                            badge.style.display = 'flex'; badge.textContent = unreadCount;
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
                                list.innerHTML += `<a href="/php-web/public/orders?status=pending&highlight=${item.order_id}" class="notif-item" style="${readStyle}" data-notif-id="${itemId}" onclick="markAsRead('${itemId}')"><div class="notif-icon">#${item.order_id}</div><div class="notif-content"><p><strong>${item.retailer_name}</strong> placed a new order.</p><span class="notif-time">${timeAgo} &middot; &#8369;${Number(item.total_amount).toFixed(2)}</span></div></a>`;
                            } else {
                                list.innerHTML += `<a href="/php-web/public/drivers?search=${encodeURIComponent(item.driver_name)}" class="notif-item" style="${readStyle}" data-notif-id="${itemId}" onclick="markAsRead('${itemId}')"><div class="notif-icon" style="background: #15803d; border-radius: 50%;"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg></div><div class="notif-content"><p>${item.action}</p><span class="notif-time">${timeAgo} &middot; Click to view driver</span></div></a>`;
                            }
                        });
                    }
                }).catch(err => console.error('Error fetching notifications:', err));
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

        window.addEventListener('load', function() {
            let viewedOrders = JSON.parse(localStorage.getItem('viewedOrders') || '[]');
            const highlightedRows = document.querySelectorAll('tr.new-order-highlight');
            highlightedRows.forEach(row => {
                const orderId = row.getAttribute('data-order-id');
                if (viewedOrders.includes(orderId)) {
                    row.classList.remove('new-order-highlight');
                    row.style.backgroundColor = 'transparent';
                }
            });
        });
    </script>
</body>
</html>
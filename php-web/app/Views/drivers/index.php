<?php /* Drivers view */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KimFresh - Drivers</title>
    <link rel="stylesheet" href="/php-web/public/css/theme.css">
    <link rel="stylesheet" href="/php-web/public/css/dashboard.css">
    <link rel="stylesheet" href="/php-web/public/css/drivers.css">
    <link rel="stylesheet" href="/php-web/public/css/responsive.css">
    <style>
        /* --- LAYOUT & HEADER --- */
        body { margin: 0 !important; padding: 0 !important; background: #f4f7f6; }
        .dashboard-layout { display: flex !important; min-height: 100vh !important; width: 100% !important; overflow-x: hidden; }
        .sidebar { width: 260px !important; flex-shrink: 0 !important; background: #C34200 !important; min-height: 100vh !important; position: sticky !important; top: 0 !important; }
        .main-area { flex: 1 !important; display: flex !important; flex-direction: column !important; min-width: 0 !important; margin: 0 !important; padding: 0 !important; }
        .top-header { width: 100% !important; background: #ffffff !important; padding: 15px 40px !important; border-bottom: 1px solid #eaeaea !important; display: flex !important; justify-content: flex-end !important; align-items: center !important; box-sizing: border-box !important; position: sticky; top: 0; z-index: 999; margin: 0 !important; border-radius: 0 !important; box-shadow: none !important;}
        .main-content { padding: 30px 40px !important; flex-grow: 1 !important; margin: 0 !important; max-width: none !important; }

        /* --- STATS & TOOLBAR --- */
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 24px; }
        .stat-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border-left: 5px solid #C34200; }
        .stat-card.green { border-left-color: #15803d; }
        .stat-card.orange { border-left-color: #f59e0b; }
        .stat-card p { margin: 0; color: #666; font-size: 13px; font-weight: 700; }
        .stat-card h2 { margin: 5px 0 0; font-size: 28px; color: #333; }
        .toolbar { display: flex; gap: 12px; margin-top: 10px; margin-bottom: 24px; justify-content: space-between; flex-wrap: wrap; }
        .filter-box select { width: 220px; height: 44px; padding: 0 16px; border: 1px solid #ddd; border-radius: 10px; font-size: 13px; outline: none; background: #fff; font-weight: bold; color: #333; }

        /* --- PROFILE & NOTIFICATIONS --- */
        .profile-menu { position: relative; }
        .profile-trigger { display: flex; align-items: center; gap: 10px; background: transparent; border: none; cursor: pointer; padding: 8px; border-radius: 10px; }
        .profile-trigger:hover { background: #fdf0e6; }
        .profile-dropdown { position: absolute; top: 100%; right: 0; background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); width: 200px; padding: 8px; display: none; z-index: 100; border: 1px solid #eee; }
        .profile-dropdown.show { display: block; }
        .dropdown-account { padding: 12px; border-bottom: 1px solid #eee; margin-bottom: 8px; }
        .dropdown-item { display: flex; padding: 10px; text-decoration: none; color: #333; font-size: 13px; font-weight: 600; border-radius: 8px; }
        .dropdown-item:hover { background: #fdf0e6; color: #C34200; }
        
        .notification-container { position: relative; margin-right: 15px; }
        .notification-bell { position: relative; display: flex; align-items: center; justify-content: center; width: 44px; height: 44px; border-radius: 50%; cursor: pointer; }
        .notification-bell:hover { background: #f0f0f0; }
        .notif-badge { position: absolute; top: 2px; right: 2px; background: #dc2626; color: white; font-size: 11px; font-weight: 800; width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2px solid white; }
        .notif-dropdown { position: absolute; top: 55px; right: 0; width: 320px; background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); display: none; z-index: 1000; overflow: hidden; }
        .notif-dropdown.show { display: block; }
        .notif-header { display: flex; justify-content: space-between; padding: 15px; border-bottom: 1px solid #eee; }
        .notif-list { max-height: 350px; overflow-y: auto; }
        .notif-item { display: flex; gap: 12px; padding: 12px 15px; border-bottom: 1px solid #f5f5f5; text-decoration: none; color: #333; }
        .notif-icon { width: 45px; height: 45px; border-radius: 50%; background: #C34200; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; }
        .notif-content p { margin: 0 0 4px 0; font-size: 13px; color: #444; }
        .notif-time { color: #888; font-size: 11px; }

        /* --- DRIVER CARDS --- */
        .driver-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .driver-card { background: white; border-radius: 16px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); display: flex; flex-direction: column; gap: 15px; }
        .driver-top { display: flex; align-items: center; gap: 12px; }
        .driver-avatar { width: 45px; height: 45px; border-radius: 50%; background: #C34200; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 18px; }
        .driver-title { flex: 1; }
        .driver-name { font-weight: 800; font-size: 16px; color: #333; }
        .driver-license { font-size: 12px; color: #777; }
        .status { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
        .status.available { color: #15803d; background: #dcfce7; }
        .status.assigned { color: #1d4ed8; background: #dbeafe; }
        .status.on-delivery { color: #b45309; background: #fef3c7; }
        .status.inactive { color: #666; background: #eee; }
        .driver-contact { font-size: 13px; color: #555; }
        .driver-contact p { margin: 2px 0; }
        .driver-section { border-top: 1px solid #eee; padding-top: 10px; }
        .driver-section h4 { margin: 0; font-size: 14px; color: #333; }
        .done-count { font-size: 12px; color: #777; margin: 4px 0 0 0; }
        .history-btn { padding: 8px 16px; font-size: 12px; font-weight: 700; color: #fff; background: #C34200; border: none; border-radius: 8px; cursor: pointer; width: 100%; }
        .history-btn:disabled { background: #ccc; cursor: not-allowed; }
        .btn-deactivate { padding: 8px 16px; font-size: 12px; font-weight: 700; color: #fff; background: #dc2626; border: none; border-radius: 8px; cursor: pointer; width: 100%; }
        .btn-activate { padding: 8px 16px; font-size: 12px; font-weight: 700; color: #fff; background: #15803d; border: none; border-radius: 8px; cursor: pointer; width: 100%; }

        /* --- MODAL --- */
        .modal-backdrop { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 1000; }
        .modal-backdrop.open { display: flex; }
        .modal { background: white; border-radius: 12px; padding: 24px; width: 90%; max-width: 400px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .modal-wide { max-width: 800px; }
        .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 16px; }
        .modal-actions button { padding: 10px 20px; border-radius: 8px; font-weight: bold; cursor: pointer; border: none; }
        .modal-actions .cancel { background: #eee; color: #333; }
        .history-table { width: 100%; border-collapse: collapse; }
        .history-table th { text-align: left; padding: 12px; font-size: 12px; color: #666; border-bottom: 1px solid #eee; background: #f8f9fa; }
        .history-table td { padding: 12px; font-size: 13px; border-bottom: 1px solid #eee; }
        .status-pill { padding: 4px 10px; font-size: 11px; font-weight: 700; border-radius: 20px; }
        .status-pill.pending { background: #fef3c7; color: #b45309; }
        .status-pill.approved, .status-pill.delivered { background: #dcfce7; color: #15803d; }
        .status-pill.rejected, .status-pill.cancelled { background: #fee2e2; color: #b91c1c; }
        .status-pill.in_transit, .status-pill.waiting_for_pickup { background: #ffedd5; color: #c2410c; }
    </style>
</head>
<body>
    <script>
        document.addEventListener('click', function() {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            if (ctx.state === 'suspended') ctx.resume();
        }, { once: true });
    </script>

    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-brand">
                <img src="/php-web/public/logo.png" class="logo" alt="KimFresh">
                <p>Administration</p>
            </div>
            <nav class="menu">
                <a href="/php-web/public/dashboard">Dashboard</a>
                <a href="/php-web/public/orders">Orders</a>
                <a href="/php-web/public/products">Products</a>
                <a href="/php-web/public/drivers" class="active">Drivers</a>
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
                            <p class="user-name" style="margin: 0; font-weight:700; font-size:14px;"><?= htmlspecialchars($_SESSION['admin_name']) ?></p>
                            <p class="user-role" style="margin: 0; font-size:12px; color:#777;">Administrator</p>
                        </div>
                    </button>
                    <div class="profile-dropdown" id="profileDropdown">
                        <div class="dropdown-account">
                            <strong><?= htmlspecialchars($_SESSION['admin_name']) ?></strong>
                            <span style="font-size:12px; color:#777;"><?= htmlspecialchars($_SESSION['admin_email'] ?? 'admin@kimfresh.com') ?></span>
                        </div>
                        <a href="/php-web/public/settings" class="dropdown-item">Settings</a>
                        <a href="/php-web/public/logs" class="dropdown-item">Activity Logs</a>
                        <a href="/php-web/public/logout" class="dropdown-item" style="color: #C34200;">Logout</a>
                    </div>
                </div>
            </header>

            <main class="main-content">
                <div class="page-head" style="display:flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                    <div>
                        <p class="overview-label" style="color:#C34200; font-weight:bold; font-size:12px; margin:0 0 5px 0;">Manage</p>
                        <h1 style="margin:0;">Drivers</h1>
                    </div>
                    <a href="?export_pdf=1" download="KimFresh_Drivers.pdf" class="btn-export" style="background:#C34200; color:white; padding:10px 20px; border-radius:8px; font-weight:bold; text-decoration:none;">Export PDF</a>
                </div>

                <div class="stats-grid">
                    <div class="stat-card"><p>Total Drivers</p><h2><?= $total_drivers ?></h2></div>
                    <div class="stat-card green"><p>Available</p><h2><?= $available_drivers ?></h2></div>
                    <div class="stat-card orange"><p>On Delivery</p><h2><?= $on_delivery ?></h2></div>
                </div>

                <div class="toolbar no-print" style="justify-content: flex-start;">
                    <div class="filter-box">
                        <form method="GET" action="/php-web/public/drivers" style="margin:0;">
                            <select name="filter" onchange="this.form.submit()">
                                <option value="all"         <?= $filter === 'all'         ? 'selected' : '' ?>>All Drivers</option>
                                <option value="available"   <?= $filter === 'available'   ? 'selected' : '' ?>>Available</option>
                                <option value="assigned"    <?= $filter === 'assigned'    ? 'selected' : '' ?>>Assigned</option>
                                <option value="on_delivery" <?= $filter === 'on_delivery' ? 'selected' : '' ?>>On Delivery</option>
                                <option value="inactive"    <?= $filter === 'inactive'    ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </form>
                    </div>
                </div>

                <div class="driver-grid">
                    <?php if ($drivers->num_rows > 0): while ($row = $drivers->fetch_assoc()): ?>
                        <?php
                            $activeCount    = (int)$row['active_orders'];
                            $deliveryStatus = $row['active_delivery_status'];
                            $isInactive     = $row['status'] === 'hidden';

                            if ($isInactive) {
                                $statusClass = 'inactive'; $statusLabel = 'Inactive';
                            } elseif ($activeCount === 0) {
                                $statusClass = 'available'; $statusLabel = 'Available';
                            } elseif ($deliveryStatus === 'in_transit') {
                                $statusClass = 'on-delivery'; $statusLabel = 'On Delivery';
                            } else {
                                $statusClass = 'assigned'; $statusLabel = 'Assigned';
                            }

                            $initial    = strtoupper(substr($row['name'], 0, 1));
                            $driverId   = (int)$row['driver_id'];
                            $driverPast = $histories[$driverId] ?? [];
                        ?>
                        <div class="driver-card">
                            <div class="driver-top">
                                <div class="driver-avatar"><?= $initial ?></div>
                                <div class="driver-title">
                                    <div class="driver-name"><?= htmlspecialchars($row['name']) ?></div>
                                    <div class="driver-license"><?= htmlspecialchars($row['license_number'] ?? 'No license') ?></div>
                                </div>
                                <span class="status <?= $statusClass ?>"><?= $statusLabel ?></span>
                            </div>
                            <div class="driver-contact">
                                <p><?= htmlspecialchars($row['email'] ?? 'No email') ?></p>
                                <p><?= htmlspecialchars($row['phone'] ?? 'No phone') ?></p>
                            </div>
                            <div class="driver-section">
                                <h4>Active Orders: <?= $activeCount ?></h4>
                                <p class="done-count"><?= count($driverPast) ?> past deliveries</p>
                            </div>

                            <div class="driver-actions no-print" style="display:flex; flex-direction:column; gap:8px;">
                                <?php if ($activeCount === 0 && !$isInactive && $unassigned->num_rows > 0): ?>
                                    <form method="POST" class="assign-form">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="driver_id" value="<?= $driverId ?>">
                                        <select name="order_id" required>
                                            <option value="">Assign an order...</option>
                                            <?php $unassigned->data_seek(0); while ($uo = $unassigned->fetch_assoc()): ?>
                                                <option value="<?= $uo['order_id'] ?>">#<?= $uo['order_id'] ?> - <?= htmlspecialchars($uo['retailer_name']) ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                        <button type="submit" name="assign_order" style="background:#C34200; color:white; border:none; border-radius:8px; font-weight:bold; cursor:pointer;">Assign</button>
                                    </form>
                                <?php endif; ?>

                                <button class="history-btn" type="button" data-driver-id="<?= $driverId ?>" data-driver-name="<?= htmlspecialchars($row['name'], ENT_QUOTES) ?>" onclick="openHistoryModal(this)">View History</button>

                                <?php if ($isInactive): ?>
                                    <form method="POST" style="margin:0;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="driver_id" value="<?= $driverId ?>">
                                        <input type="hidden" name="new_status" value="available">
                                        <button type="submit" name="toggle_status" class="btn-activate">Activate</button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" style="margin:0;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="driver_id" value="<?= $driverId ?>">
                                        <input type="hidden" name="new_status" value="hidden">
                                        <button type="submit" name="toggle_status" class="btn-deactivate">Deactivate</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>

                        <template id="history-tpl-<?= $driverId ?>">
                            <?php if (!empty($driverPast)): ?>
                                <table class="history-table">
                                    <thead><tr><th>Order</th><th>Retailer</th><th>Items</th><th>Total</th><th>Status</th><th>Delivery</th><th>Date</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($driverPast as $h): ?>
                                        <tr>
                                            <td><span class="order-no">#<?= (int)$h['order_id'] ?></span></td>
                                            <td><?= htmlspecialchars($h['retailer_name'] ?? '-') ?></td>
                                            <td><?= (int)($h['total_items'] ?? 0) ?></td>
                                            <td>&#8369;<?= number_format((float)$h['total_amount'], 2) ?></td>
                                            <td><span class="status-pill <?= strtolower($h['order_status']) ?>"><?= strtoupper($h['order_status']) ?></span></td>
                                            <td><?= strtoupper($h['delivery_status']) ?></td>
                                            <td><?= date('M d, Y', strtotime($h['assigned_at'] ?? $h['order_date'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p class="empty-note">This driver has no past deliveries yet.</p>
                            <?php endif; ?>
                        </template>
                    <?php endwhile; else: ?>
                        <p style="text-align:center; padding: 20px; grid-column: 1/-1; color:#777;">No drivers found matching your filter.</p>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <div class="modal-backdrop no-print" id="historyModal">
        <div class="modal modal-wide">
            <h3 style="margin-top:0; color: #C34200;">Order History — <span id="historyDriverName">—</span></h3>
            <div id="historyBody" style="max-height: 60vh; overflow: auto; margin-top: 14px;"></div>
            <div class="modal-actions">
                <button type="button" class="cancel" onclick="document.getElementById('historyModal').classList.remove('open')">Close</button>
            </div>
        </div>
    </div>

    <script>
        function toggleProfileMenu(event) {
            event.stopPropagation();
            document.getElementById('profileDropdown').classList.toggle('show');
        }
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('profileMenu');
            if (menu && !menu.contains(event.target)) document.getElementById('profileDropdown').classList.remove('show');
        });

        function openHistoryModal(btn) {
            const id = btn.dataset.driverId;
            const name = btn.dataset.driverName;
            const tpl = document.getElementById('history-tpl-' + id);
            if (!tpl) { alert('No history template found.'); return; }
            document.getElementById('historyDriverName').textContent = name;
            const body = document.getElementById('historyBody');
            body.innerHTML = '';
            body.appendChild(tpl.content.cloneNode(true));
            document.getElementById('historyModal').classList.add('open');
        }
        document.addEventListener('click', function(event) {
            const modal = document.getElementById('historyModal');
            if (modal && event.target === modal) modal.classList.remove('open');
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modal = document.getElementById('historyModal');
                if (modal) modal.classList.remove('open');
            }
        });

        let lastNotifCount = 0;
        function playBeep() {
            try {
                let ctx = new (window.AudioContext || window.webkitAudioContext)();
                let osc = ctx.createOscillator();
                osc.frequency.value = 800;
                osc.connect(ctx.destination);
                osc.start(); osc.stop(ctx.currentTime + 0.15);
            } catch(e) {}
        }
        function toggleNotifications(event) {
            event.stopPropagation();
            document.getElementById('notifDropdown').classList.toggle('show');
        }
        document.addEventListener('click', function(event) {
            const c = document.getElementById('notifContainer');
            if (c && !c.contains(event.target)) {
                const d = document.getElementById('notifDropdown');
                if (d) d.classList.remove('show');
            }
        });
        function checkNotifications() {
            fetch('/php-web/public/notifications', { credentials: 'same-origin' })
                .then(r => r.json())
                .then(data => {
                    const badge = document.getElementById('notif-badge');
                    const list = document.getElementById('notifList');
                    if (!badge || !list) return;
                    let readIds = JSON.parse(localStorage.getItem('readNotifIds') || '[]');
                    let unread = 0;
                    data.orders.forEach(i => {
                        const id = i.type === 'order' ? 'order-' + i.order_id : 'log-' + i.log_id;
                        if (!readIds.includes(id)) unread++;
                    });
                    if (unread > 0) {
                        badge.style.display = 'flex'; badge.textContent = unread;
                        if (unread > lastNotifCount) playBeep();
                    } else {
                        badge.style.display = 'none';
                    }
                    lastNotifCount = unread;
                    list.innerHTML = '';
                    data.orders.forEach(i => {
                        const date = new Date(i.type === 'order' ? i.order_date : i.timestamp);
                        const t = date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
                        const id = i.type === 'order' ? 'order-' + i.order_id : 'log-' + i.log_id;
                        if (i.type === 'order') {
                            list.innerHTML += `<a href="/php-web/public/orders/view?id=${i.order_id}" class="notif-item" onclick="markAsRead('${id}')"><div class="notif-icon">#${i.order_id}</div><div class="notif-content"><p><strong>${i.retailer_name}</strong> placed a new order.</p><span class="notif-time">${t} &middot; &#8369;${Number(i.total_amount).toFixed(2)}</span></div></a>`;
                        } else {
                            list.innerHTML += `<a href="/php-web/public/drivers" class="notif-item" onclick="markAsRead('${id}')"><div class="notif-icon" style="background:#15803d;"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg></div><div class="notif-content"><p>${i.action}</p><span class="notif-time">${t}</span></div></a>`;
                        }
                    });
                }).catch(()=>{});
        }
        function markAsRead(id) {
            let r = JSON.parse(localStorage.getItem('readNotifIds') || '[]');
            if (!r.includes(id)) { r.push(id); localStorage.setItem('readNotifIds', JSON.stringify(r)); }
        }
        checkNotifications();
        setInterval(checkNotifications, 15000);
    </script>
</body>
</html>
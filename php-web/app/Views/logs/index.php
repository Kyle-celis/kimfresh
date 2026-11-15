<?php /* Logs view - vars from LogsController::index() */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs</title>
    <link rel="stylesheet" href="/php-web/public/css/theme.css">
    <link rel="stylesheet" href="/php-web/public/css/dashboard.css">
    <link rel="stylesheet" href="/php-web/public/css/responsive.css">
    
    <style>
        :root { --primary: #C34200; }
        .table-section { background: white; border-radius: 16px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; }
        
        th { 
            text-align: left; 
            padding: 15px; 
            font-size: 13px; 
            color: #666; 
            border-bottom: 1px solid #eee; 
            background: transparent !important; 
            font-weight: 700;
        }
        
        td { padding: 15px; border-bottom: 1px solid #f5f5f5; font-size: 13px; }
        
        .page-head { display: flex; justify-content: space-between; align-items: center; }
        .btn-export { background: #C34200; color: white; padding: 12px 20px; border: none; border-radius: 8px; font-weight: 700; font-size: 13px; cursor: pointer; transition: 0.2s; text-decoration: none; display: inline-block; }
        .btn-export:hover { background: #9a3400; }

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

        /* PAGINATION STYLES */
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

        /* Mobile Table and Header Fixes */
        @media (max-width: 768px) {
            .page-head {
                flex-direction: column !important;
                align-items: stretch !important;
                gap: 15px !important;
            }
            
            .page-head form {
                width: 100% !important;
            }
            
            .page-head .btn-export {
                width: 100% !important;
                padding: 12px !important;
                text-align: center !important;
            }

            .table-section {
                padding: 10px !important;
            }

            table {
                width: 100% !important;
                table-layout: fixed;
            }

            th, td {
                padding: 10px 8px !important;
                font-size: 11px !important;
                word-wrap: break-word;
                white-space: normal !important;
            }

            th:nth-child(3), td:nth-child(3) {
                display: none !important; 
            }
        }
            /* Stable table layout */
        table {
            table-layout: fixed;
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 16px !important;
            vertical-align: middle;
            word-wrap: break-word;
        }
        /* Fixed column widths */
        th:nth-child(1), td:nth-child(1) { width: 20%; } /* Admin */
        th:nth-child(2), td:nth-child(2) { width: 60%; } /* Action */
        th:nth-child(3), td:nth-child(3) { width: 20%; text-align: right; } /* Timestamp */

        /* ---------- Sidebar typography (same on all pages) ---------- */
        .sidebar, .sidebar * { font-family: 'Nunito Sans', sans-serif !important; }
        .sidebar .sidebar-brand .logo { width: 120px !important; max-width: 100% !important; height: auto !important; }
        .sidebar .sidebar-brand p { font-size: 13px !important; font-weight: 600 !important; color: #fff !important; opacity: .95 !important; }
        .sidebar .menu a { font-size: 17px !important; font-weight: 800 !important; }
    
        /* ---------- Sidebar brand (same as Drivers) ---------- */
        .sidebar .sidebar-brand { display: flex !important; flex-direction: column !important; align-items: center !important; justify-content: center !important; text-align: center !important; padding: 28px 16px 22px !important; margin: 0 !important; border-bottom: 1px solid rgba(255,255,255,.18) !important; }
        .sidebar .sidebar-brand .logo { display: block !important; margin: 0 auto !important; }
        .sidebar .sidebar-brand p { margin: 12px 0 0 !important; text-align: center !important; }
    </style>
<link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/php-web/public/css/app-shell.css?v=1">
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
                <a href="/php-web/public/orders">
                    <svg viewBox="0 0 24 24"><path d="M12 2 2 7l10 5 10-5-10-5zm-8.5 8.2L2 11l10 5 10-5-1.5-.8L12 14 3.5 10.2zm0 4L2 15l10 5 10-5-1.5-.8L12 18l-8.5-3.8z"/></svg>
                    Orders
                </a>
                <a href="/php-web/public/drivers">
                    <svg viewBox="0 0 24 24"><path d="M12 2a4 4 0 0 1 4 4v1h1a2 2 0 0 1 2 2v3a7 7 0 0 1-14 0V9a2 2 0 0 1 2-2h1V6a4 4 0 0 1 4-4zM5 20h14v2H5v-2z"/></svg>
                    Drivers
                </a>
                
                <!-- ADDED ANALYTICS LINK -->
                <a href="/php-web/public/analytics">
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
                <div class="page-head" style="display:flex; justify-content: space-between; align-items: center;">
                    <div>
                        <p class="overview-label">System</p>
                        <h1>Activity Logs</h1>
                    </div>
                    <a href="?export_pdf=1" download="KimFresh_Activity_Logs.pdf" class="btn-export">Export to PDF</a>
                </div>

                <section class="table-section">
                    <table>
                        <thead>
                            <tr>
                                <th>Admin</th>
                                <th>Action</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($logs->num_rows > 0) {
                                while($row = $logs->fetch_assoc()) {
                                    $admin = $row['admin_name'] ? htmlspecialchars($row['admin_name']) : 'System';
                                    echo "<tr>";
                                    echo "<td>" . $admin . "</td>";
                                    echo "<td>" . htmlspecialchars($row['action']) . "</td>";
                                    echo "<td>" . date('M d, Y h:i A', strtotime($row['timestamp'])) . "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='3' style='text-align:center;'>No activity logs found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>

                    <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>">Prev</a>
                        <?php else: ?>
                            <span class="disabled">Prev</span>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="active"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>">Next</a>
                        <?php else: ?>
                            <span class="disabled">Next</span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </section>
            </main>
        </div>
    </div>

    <script>
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
            if (event.target.closest('.pagination a')) {
                sessionStorage.setItem('scrollPos', window.scrollY);
            }
        });

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
        // REAL-TIME NOTIFICATIONS & DROPDOWN (MARK AS READ)
        // =========================================
     let lastNotifCount = 0;
    // ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â°ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚Â¸ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚ÂÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¥ NEW: Built-in browser beep synthesizer
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
            const prof = document.getElementById('profileDropdown');
            if (prof) prof.classList.remove('show');
            document.querySelectorAll('.pop').forEach(p => p.classList.remove('show'));
            const notif = document.getElementById('notifDropdown');
            if (notif) notif.classList.toggle('show');
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
    playBeep(); // ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â°ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚Â¸ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¹Ã…â€œÃƒÆ’Ã¢â‚¬Â¹ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â  Call our new beep function
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
                                        <span class="notif-time">${timeAgo} &middot; ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â±${Number(item.total_amount).toFixed(2)}</span>
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

    // ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â°ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚Â¸ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚ÂÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¥ NEW FUNCTION: Marks only the clicked item as read
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

    <!-- KimFresh mobile burger menu script -->
    <script>
    (function () {
        var sidebar = document.querySelector('.sidebar');
        var header = document.querySelector('.top-header');
        if (!sidebar || !header) return;

        sidebar.id = sidebar.id || 'sidebar';

        var burger = document.createElement('button');
        burger.type = 'button';
        burger.className = 'menu-toggle';
        burger.setAttribute('aria-label', 'Open menu');
        burger.setAttribute('aria-controls', sidebar.id);
        burger.setAttribute('aria-expanded', 'false');
        burger.innerHTML = '<span></span><span></span><span></span>';
        header.insertBefore(burger, header.firstChild);

        var logo = sidebar.querySelector('.logo');
        if (logo) {
            var mobileLogo = logo.cloneNode(true);
            mobileLogo.className = 'mobile-logo';
            header.insertBefore(mobileLogo, burger.nextSibling);
        }

        var closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'sidebar-close';
        closeBtn.setAttribute('aria-label', 'Close menu');
        closeBtn.innerHTML = '&times;';
        sidebar.appendChild(closeBtn);

        var overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);

        function setOpen(open) {
            sidebar.classList.toggle('open', open);
            document.body.classList.toggle('sidebar-open', open);
            burger.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        burger.addEventListener('click', function () { setOpen(!sidebar.classList.contains('open')); });
        closeBtn.addEventListener('click', function () { setOpen(false); });
        overlay.addEventListener('click', function () { setOpen(false); });
        sidebar.addEventListener('click', function (e) { if (e.target.closest('a')) setOpen(false); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setOpen(false); });
        window.matchMedia('(min-width: 769px)').addEventListener('change', function (e) { if (e.matches) setOpen(false); });
    })();
            
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
</body>
</html>
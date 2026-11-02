<?php
session_start();
require 'db.php';
require 'log_helper.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$current_admin_id = $_SESSION['admin_id'];
$is_superadmin = ($current_admin_id == 1);

// --- HANDLE PDF EXPORT (FPDF) ---
if (isset($_GET['export_pdf'])) {
    logActivity($conn, $current_admin_id, "Exported Activity Logs to PDF");
    
    require('fpdf/fpdf.php');

    class PDF extends FPDF {
        function Header() {
            $this->SetFont('Arial', 'B', 15);
            $this->SetTextColor(195, 66, 0); 
            $this->Cell(0, 10, 'KimFresh Activity Logs', 0, 1, 'C');
            $this->SetFont('Arial', 'I', 10);
            $this->SetTextColor(100, 100, 100);
            $this->Cell(0, 5, 'Generated on: ' . date('F j, Y, g:i A'), 0, 1, 'C');
            $this->Ln(10);
            
            $this->SetFont('Arial', 'B', 10);
            $this->SetFillColor(195, 66, 0);
            $this->SetTextColor(255, 255, 255);
            $this->Cell(50, 8, 'Admin Name', 1, 0, 'C', true);
            $this->Cell(80, 8, 'Action', 1, 0, 'C', true);
            $this->Cell(40, 8, 'Timestamp', 1, 1, 'C', true);
        }

        function Footer() {
            $this->SetY(-15);
            $this->SetFont('Arial', 'I', 8);
            $this->SetTextColor(128);
            $this->Cell(0, 10, 'Page ' . $this->PageNo() . ' - KimFresh IoT System', 0, 0, 'C');
        }
    }

    $pdf = new PDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(0, 0, 0);

    if ($is_superadmin) {
        $logs_export = $conn->query("
            SELECT l.log_id, a.name as admin_name, l.action, l.timestamp 
            FROM activity_logs l
            LEFT JOIN admin a ON l.admin_id = a.admin_id
            ORDER BY l.timestamp DESC
        ");
    } else {
        $logs_export = $conn->query("
            SELECT l.log_id, a.name as admin_name, l.action, l.timestamp 
            FROM activity_logs l
            LEFT JOIN admin a ON l.admin_id = a.admin_id
            WHERE l.admin_id = $current_admin_id
            ORDER BY l.timestamp DESC
        ");
    }
    
    while ($row = $logs_export->fetch_assoc()) {
        $admin = $row['admin_name'] ? htmlspecialchars($row['admin_name']) : 'System';
        $pdf->Cell(50, 8, $admin, 1, 0, 'L');
        $pdf->Cell(80, 8, htmlspecialchars($row['action']), 1, 0, 'L');
        $pdf->Cell(40, 8, date('M d, Y h:i A', strtotime($row['timestamp'])), 1, 1, 'C');
    }

    $pdf->Output('D', 'KimFresh_Activity_Logs.pdf');
    exit();
}

// --- PAGINATION LOGIC ---
$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Count Total Logs based on role
if ($is_superadmin) {
    $countQuery = $conn->query("SELECT COUNT(*) AS total FROM activity_logs");
} else {
    $countQuery = $conn->query("SELECT COUNT(*) AS total FROM activity_logs WHERE admin_id = $current_admin_id");
}
$total_rows = $countQuery->fetch_assoc()['total'];
$totalPages = ceil($total_rows / $limit);

// Fetch Logs for Display
if ($is_superadmin) {
    $logs = $conn->query("
        SELECT l.*, a.name as admin_name 
        FROM activity_logs l
        LEFT JOIN admin a ON l.admin_id = a.admin_id
        ORDER BY l.timestamp DESC
        LIMIT $limit OFFSET $offset
    ");
} else {
    $logs = $conn->query("
        SELECT l.*, a.name as admin_name 
        FROM activity_logs l
        LEFT JOIN admin a ON l.admin_id = a.admin_id
        WHERE l.admin_id = $current_admin_id
        ORDER BY l.timestamp DESC
        LIMIT $limit OFFSET $offset
    ");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs</title>
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/responsive.css">
    
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
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-brand">
                <img src="logo.png" alt="KimFresh" class="logo">
                <p>Administration</p>
            </div>
            <nav class="menu">
                <a href="dashboard.php">Dashboard</a>
                <a href="orders.php">Orders</a>
                <a href="products.php">Products</a>
                <a href="drivers.php">Drivers</a>
                <a href="analytics.php">Analytics</a>
                <?php if (!empty($_SESSION['is_superadmin'])): ?>
                    <a href="superadmin.php">Manage Admins</a>
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
                        <a href="settings.php" class="dropdown-item">Settings</a>
                        <a href="logs.php" class="dropdown-item">Activity Logs</a>
                        <a href="logout.php" class="dropdown-item" style="color: #C34200;">Logout</a>
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

                    <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>">Prev</a>
                        <?php else: ?>
                            <span class="disabled">Prev</span>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="active"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
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
            document.getElementById('profileDropdown').classList.toggle('show');
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
        fetch('get_notifications.php')
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
                                <a href="view_order.php?id=${item.order_id}" class="notif-item" style="${readStyle}" data-notif-id="${itemId}" onclick="markAsRead('${itemId}')">
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
    </script>
</body>
</html>
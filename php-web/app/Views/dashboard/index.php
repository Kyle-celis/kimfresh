<?php
// Load result sets into arrays so the same data can feed several panels
$recentRows = [];
if ($recentOrders && $recentOrders->num_rows > 0) { while ($r = $recentOrders->fetch_assoc()) $recentRows[] = $r; }
$stockRows = [];
if ($lowStockList && $lowStockList->num_rows > 0) { while ($r = $lowStockList->fetch_assoc()) $stockRows[] = $r; }
$deliveryRows = [];
if ($activeDeliveries && $activeDeliveries->num_rows > 0) { while ($r = $activeDeliveries->fetch_assoc()) $deliveryRows[] = $r; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KimFresh - Dashboard</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/php-web/public/css/theme.css">
    <link rel="stylesheet" href="/php-web/public/css/dashboard.css">
    <link rel="stylesheet" href="/php-web/public/css/flow.css">
    <link rel="stylesheet" href="/php-web/public/css/responsive.css">

    <style>
    :root { --primary: #D44A00; --primary-dark: #B83F00; --orange-a: #DC4E00; --orange-b: #C8632D; }

    /* --- Core Layout --- */
    body { margin: 0; padding: 0; background: #f4f6fa; font-family: 'Nunito Sans', sans-serif; }
    .dashboard-layout { min-height: 100vh; width: 100%; position: relative; }

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
        /* ---------- Sidebar brand (same as Drivers) ---------- */
        .sidebar .sidebar-brand { display: flex !important; flex-direction: column !important; align-items: center !important; justify-content: center !important; text-align: center !important; padding: 28px 16px 22px !important; margin: 0 !important; border-bottom: 1px solid rgba(255,255,255,.18) !important; }
        .sidebar .sidebar-brand .logo { display: block !important; margin: 0 auto !important; }
        .sidebar .sidebar-brand p { margin: 12px 0 0 !important; text-align: center !important; }

    /* --- Main Area --- */
    .main-area {
        margin-left: 270px !important; width: calc(100% - 270px) !important;
        display: flex; flex-direction: column; min-height: 100vh;
    }
    .top-header { width: 100% !important; background: #fff !important; padding: 30px 50px !important; border-bottom: 1px solid #f0f0f0 !important; border-radius: 0 !important; margin: 0 !important; box-shadow: 0 2px 10px rgba(0,0,0,0.02) !important; display: flex !important; justify-content: flex-end !important; align-items: center !important; gap: 30px; box-sizing: border-box !important; position: sticky; top: 0; z-index: 999; }
    .main-content { padding: 24px 40px 40px !important; flex-grow: 1 !important; }
    .overview-label { margin: 0 0 2px; color: var(--primary); font-weight: 800; font-size: 18px; }
    .main-content h1 { margin: 0 0 18px; font-size: 32px; font-weight: 800; color: #111; }

    /* --- Profile + Bell --- */
    .profile-menu { position: relative; }
    .profile-trigger { display: flex; align-items: center; gap: 12px; background: transparent; border: none; cursor: pointer; padding: 4px 8px; border-radius: 10px; transition: 0.2s; }
    .profile-trigger:hover { background: #fdf0e6; }
    .profile-trigger .avatar { width: 54px; height: 54px; border-radius: 50%; background: #F2B58F; color: var(--primary); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 20px; }
    .profile-trigger .user-name { font-size: 16px; font-weight: 700; color: #222; }
    .profile-trigger .user-role { font-size: 13px; color: #666; }
    .profile-chevron {
        width: 16px; height: 16px; border-radius: 50%; border: 1px solid #ccc; display: flex;
        align-items: center; justify-content: center; margin-left: 10px;
    }
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

    /* --- Order Flow panel --- */
    .main-content .panel {
        background: #fff; border-radius: 24px; border-top: 8px solid var(--primary);
        padding: 20px 22px 22px; box-shadow: 0 4px 14px rgba(0,0,0,0.05);
    }
    .main-content .panel-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; }
    .main-content .panel-head h3 { margin: 0; font-size: 19px; font-weight: 800; color: #111; }
    .panel-link { color: var(--primary); font-weight: 700; text-decoration: none; font-size: 13px; }

    .pipeline { display: flex; border-radius: 20px; overflow: hidden; background: transparent; }
    .pipeline .pipe-step {
        flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center;
        gap: 6px; height: 86px; color: #fff; text-decoration: none; margin-left: -18px;
        clip-path: polygon(0 0, calc(100% - 26px) 0, 100% 50%, calc(100% - 26px) 100%, 0 100%, 26px 50%);
        padding: 0 30px; box-sizing: border-box; border: none; border-radius: 0;
    }
    .pipeline .pipe-step:first-child { margin-left: 0; clip-path: polygon(0 0, calc(100% - 26px) 0, 100% 50%, calc(100% - 26px) 100%, 0 100%); }
    .pipeline .pipe-step:last-child { clip-path: polygon(0 0, 100% 0, 100% 100%, 0 100%, 26px 50%); }
    .pipeline .pipe-step:nth-child(1) { background: #D44A00; }
    .pipeline .pipe-step:nth-child(2) { background: #D2531A; }
    .pipeline .pipe-step:nth-child(3) { background: #CE5B22; }
    .pipeline .pipe-step:nth-child(4) { background: #CA6028; }
    .pipeline .pipe-step:nth-child(5) { background: #C8662F; }
    .pipeline .pipe-step strong {
        width: 32px; height: 32px; border-radius: 50%; background: #fff; color: var(--primary);
        display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: 800;
    }
    .pipeline .pipe-step span { font-size: 11px; font-weight: 700; color: #fff; text-align: center; }

    /* --- Stat cards --- */
    .main-content .cards-section { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-top: 24px; }
    .main-content .cards-section .card {
        background: linear-gradient(180deg, #DA4C00 0%, #C23F00 100%); color: #fff;
        border-radius: 8px; padding: 22px 20px; box-shadow: 0 4px 10px rgba(195,66,0,0.18); border: none;
    }
    .main-content .cards-section .card h3 { margin: 0 0 8px; font-size: 12px; font-weight: 700; color: #fff; }
    .main-content .cards-section .card .value { font-size: 30px; font-weight: 800; color: #fff; line-height: 1.1; }
    .main-content .cards-section .card .hint { margin: 10px 0 0; font-size: 11px; font-weight: 700; color: #fff; opacity: 0.95; }

    /* --- Chart section --- */
    .week-controls { display: flex; gap: 10px; align-items: center; }
    .week-controls h3 { margin: 0 6px; font-size: 18px; font-weight: 800; color: #111; }
    .week-controls .week-range { color: #8a8f98; font-size: 14px; margin-left: 10px; }
    .week-controls a.btn, .week-controls span.btn {
        background: var(--primary) !important; color: #fff !important; border-radius: 8px;
        width: 36px; height: 38px; padding: 0 !important; display: inline-flex; align-items: center;
        justify-content: center; font-size: 20px; text-decoration: none; box-sizing: border-box;
    }
    .week-controls a.btn:hover { background: var(--primary-dark) !important; }
    .filter-select {
        appearance: none; -webkit-appearance: none; padding: 9px 38px 9px 18px; border-radius: 8px;
        border: 1px solid var(--primary); font-weight: 800; font-size: 14px; color: var(--primary);
        background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' fill='none' stroke='%23D44A00' stroke-width='2' stroke-linecap='round'/%3E%3C/svg%3E") no-repeat right 14px center;
        cursor: pointer; outline: none; font-family: inherit;
    }
    .chart-box { background: #fff; border-radius: 12px; padding: 16px; margin-top: 16px; box-shadow: 0 2px 10px rgba(0,0,0,0.04); }

    /* --- Status pills --- */
    .status-pill { display: inline-block; padding: 4px 10px; font-size: 11px; font-weight: 700; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px; }
    .status-pill.approved, .status-pill.delivered { background-color: #dcfce7 !important; color: #15803d !important; }
    .status-pill.pending { background-color: #fef3c7 !important; color: #b45309 !important; }
    .status-pill.rejected, .status-pill.cancelled { background-color: #fee2e2 !important; color: #b91c1c !important; }
    .status-pill.in_transit, .status-pill.waiting_for_pickup { background-color: #ffedd5 !important; color: #c2410c !important; }

    /* ===== Reference "Dashboard 2": three overview panels ===== */
    .overview-grid {
        display: grid; grid-template-columns: 1.35fr 1fr 1fr; gap: 16px; margin-top: 24px;
        background: #fff; border-radius: 24px; padding: 18px; box-shadow: 0 4px 14px rgba(0,0,0,0.05);
    }
    .mini-panel { border: 1px solid #f1e4db; border-radius: 14px; padding: 14px 16px; min-width: 0; background: #fff; }
    .mini-panel h4 { margin: 0 0 12px; font-size: 13px; font-weight: 800; color: #111; display: flex; justify-content: space-between; align-items: center; }
    .mini-panel h4 a { font-size: 10px; font-weight: 700; color: var(--primary); text-decoration: none; }

    .inv-table { width: 100%; border-collapse: collapse; font-size: 10px; }
    .inv-table th { text-align: left; color: #9a9fa8; font-weight: 700; padding: 0 0 8px; background: transparent !important; }
    .inv-table td { padding: 6px 0; border-top: 1px solid #f6efe9; color: #333; font-weight: 600; }
    .prod-cell { display: flex; align-items: center; gap: 8px; }
    .prod-avatar {
        width: 22px; height: 22px; border-radius: 50%; background: #F2B58F; color: var(--primary);
        display: inline-flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 800; flex-shrink: 0;
    }
    .tag { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 9px; font-weight: 700; }
    .tag.low { background: #fde68a; color: #92400e; }
    .tag.out { background: #fecaca; color: #b91c1c; }
    .tag.reorder { background: #fff; border: 1px solid #ef4444; color: #ef4444; }
    .tag.ok { background: #dcfce7; color: #15803d; }

    .alert-row, .mini-order { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 7px 0; border-top: 1px solid #f6efe9; }
    
    /* ADDED THIS LINE TO CENTER THE REORDER TAG */
    .alert-row .tag.reorder { margin: 0 auto; }

    .alert-row:first-of-type, .mini-order:first-of-type { border-top: none; }
    .alert-info { display: flex; align-items: center; gap: 8px; min-width: 0; }
    .alert-info strong, .mini-order strong { display: block; font-size: 11px; font-weight: 700; color: #111; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .alert-info small, .mini-order small { display: block; font-size: 9px; color: #888; }
    .alert-tags { display: flex; flex-direction: column; align-items: flex-end; gap: 3px; flex-shrink: 0; }
    .mini-note { margin: 4px 0; font-size: 11px; color: #888; }

    /* ===== Reference "Dashboard 2": orange Recent Orders table panel ===== */
    .orders-panel {
        margin-top: 24px; background: linear-gradient(180deg, #DA4C00 0%, #C23F00 100%);
        border-radius: 24px; padding: 18px 22px 20px; box-shadow: 0 4px 14px rgba(195,66,0,0.18);
    }
    .orders-panel h3 { margin: 0 0 14px; font-size: 17px; font-weight: 800; color: #fff; }
    .orders-panel h3 small { font-size: 11px; font-weight: 600; opacity: 0.85; margin-left: 6px; }
    .orders-panel .table-responsive { background: #fff; border-radius: 10px; overflow: hidden; }
    .orders-panel table { width: 100%; border-collapse: collapse; font-size: 11px; background: #fff; }
    .orders-panel thead th {
        background: #B83F00 !important; color: #fff !important; text-align: center; font-weight: 700;
        padding: 10px 8px; font-size: 11px;
    }
    .orders-panel thead th a, .orders-panel thead th a:visited { color: #fff !important; text-decoration: none; }
    .orders-panel thead th a:hover { text-decoration: underline; }
    .orders-panel tbody td { text-align: center; padding: 10px 8px; color: #333; border-top: 1px solid #f3ece6; font-weight: 600; }
    .orders-panel .pagination { margin-top: 14px; gap: 5px; }
    .orders-panel .pagination a, .orders-panel .pagination span {
        min-width: 28px; height: 24px; padding: 0 8px; font-size: 10px; border-radius: 6px;
        border: 1px solid #F2B58F; background: #fff; color: #888;
    }
    .orders-panel .pagination a:hover { background: #fdf0e6; border-color: #fff; color: var(--primary); }
    .orders-panel .pagination .active { background: #8f3000; border-color: #8f3000; color: #fff; }
    .pagination { display: flex; justify-content: center; gap: 6px; margin-top: 24px; }
    .pagination .disabled { opacity: 0.5; pointer-events: none; }

    /* --- Delivery Monitoring (paginated) --- */
    .delivery-panel .panel-head { align-items: flex-start; }
    .delivery-panel .panel-link { color: var(--primary); font-weight: 700; font-size: 11px; }
    .delivery-panel .delivery { padding: 12px 0; border: none; background: transparent; box-shadow: none; }
    .delivery-panel .delivery-top { display: flex; justify-content: space-between; align-items: center; gap: 12px; }
    .delivery-panel .delivery-top strong { font-size: 14px; font-weight: 600; color: #111; }
    .delivery-panel .delivery-driver { margin: 6px 0 0; font-size: 13px; color: #6b7280; }
    .delivery-panel .delivery-driver strong { font-weight: 600; color: #6b7280; }
    .d-pill {
        background: #B83F00; color: #fff; font-size: 11px; font-weight: 600;
        padding: 5px 0; min-width: 130px; text-align: center; border-radius: 6px; white-space: nowrap;
    }
    .mini-pagination { display: flex; justify-content: center; gap: 8px; margin-top: 22px; }
    .mini-pagination button {
        min-width: 36px; height: 24px; padding: 0 10px; border-radius: 6px;
        border: 1px solid #F2B58F; background: #fff; color: #888;
        font-size: 11px; font-weight: 600; font-family: inherit; cursor: pointer;
    }
    .mini-pagination button:hover:not(:disabled) { border-color: var(--primary); color: var(--primary); }
    .mini-pagination button.active { background: var(--primary); border-color: var(--primary); color: #fff; }
    .mini-pagination button:disabled { opacity: 0.5; cursor: default; }

    @media (max-width: 1100px) {
        .overview-grid { grid-template-columns: 1fr; }
        .main-content .cards-section { grid-template-columns: 1fr 1fr; }
    }
    
        /* ---------- Sidebar typography (same on all pages) ---------- */
        .sidebar, .sidebar * { font-family: 'Nunito Sans', sans-serif !important; }
        .sidebar .sidebar-brand .logo { width: 120px !important; max-width: 100% !important; height: auto !important; }
        .sidebar .sidebar-brand p { font-size: 13px !important; font-weight: 600 !important; color: #fff !important; opacity: .95 !important; }
        .sidebar .menu a { font-size: 17px !important; font-weight: 800 !important; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js">        
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
                <a href="/php-web/public/dashboard" class="active">
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
                <a href="/php-web/public/analytics">
                    <svg viewBox="0 0 24 24"><path d="M3 3v18h18V3H3zm16 16H5V5h14v14zM7 16h2V9H7v7zm4 0h2V7h-2v9zm4 0h2v-4h-2v4z"/></svg>
                    Analytics
                </a>
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
                            <strong><?php echo htmlspecialchars($_SESSION['admin_name']); ?></strong>
                            <span><?php echo htmlspecialchars($_SESSION['admin_email'] ?? 'admin@kimfresh.com'); ?></span>
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
                <p class="overview-label">Overview</p>
                <h1>Distribution Dashboard</h1>

                <!-- ===== DASHBOARD 1: Order flow, stat cards, chart ===== -->
                <section class="panel" style="margin-bottom: 24px;">
                    <div class="panel-head"><h3>Order Flow</h3></div>
                    <div class="pipeline">
                        <a class="pipe-step <?= $pending > 0 ? 'attention' : '' ?>" href="/php-web/public/orders"><strong><?= $pending ?></strong><span>Pending Approval</span></a>
                        <a class="pipe-step <?= $waiting > 0 ? 'attention' : '' ?>" href="/php-web/public/orders"><strong><?= $waiting ?></strong><span>Waiting for driver</span></a>
                        <a class="pipe-step" href="/php-web/public/orders"><strong><?= $waitingForPickup ?></strong><span>In pickup</span></a>
                        <a class="pipe-step" href="/php-web/public/orders"><strong><?= $active ?></strong><span>Out for Delivery</span></a>
                        <a class="pipe-step" href="/php-web/public/orders"><strong><?= $delivered ?></strong><span>Delivered</span></a>
                    </div>
                </section>

                <section class="cards-section">
                    <div class="card"><h3>Pending Approval</h3><div class="value"><?= $pending ?></div><p class="hint">Waiting for approval</p></div>
                    <div class="card"><h3>Awaiting Driver</h3><div class="value"><?= $waiting ?></div><p class="hint">Approved, no driver yet</p></div>
                    <div class="card"><h3>Active Deliveries</h3><div class="value"><?= $active ?></div><p class="hint">Pickup + on the way</p></div>
                    <div class="card"><h3>Delivered</h3><div class="value"><?= $delivered ?></div><p class="hint">&#8369; <?= number_format($revenue, 2) ?> fulfilled</p></div>
                    <div class="card"><h3>Low / Out of Stock</h3><div class="value"><?= $lowStock ?></div><p class="hint"><?= $totalProducts ?> active products</p></div>
                    <div class="card"><h3>Orders This <?= $filterLabel ?></h3><div class="value"><?= $ordersThisPeriod ?></div><p class="hint"><?= $dateLabel ?></p></div>
                </section>

                <section class="analytics-section" style="margin-top: 24px;">
                    <div class="week-controls">
                        <select class="filter-select" onchange="window.location.href='?filter=' + this.value;">
                            <option value="weekly"  <?= $filter_type === 'weekly'  ? 'selected' : '' ?>>Weekly</option>
                            <option value="monthly" <?= $filter_type === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                        </select>

                        <?php if ($filter_type === 'weekly'): ?>
                            <a href="?filter=weekly&week=<?= $weekOffset - 1 ?>" class="btn">ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¹</a>
                            <h3>Orders Per Week</h3>
                            <?php if ($weekOffset >= 0): ?>
                                <span class="btn" style="opacity: 0.3; cursor: not-allowed;">ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Âº</span>
                            <?php else: ?>
                                <a href="?filter=weekly&week=<?= $weekOffset + 1 ?>" class="btn">ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Âº</a>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if ($filter_type === 'monthly'): ?>
                            <a href="?filter=monthly&month=<?= $monthOffset - 1 ?>" class="btn">ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¹</a>
                            <h3>Orders Per Month</h3>
                            <?php if ($monthOffset >= 0): ?>
                                <span class="btn" style="opacity: 0.3; cursor: not-allowed;">ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Âº</span>
                            <?php else: ?>
                                <a href="?filter=monthly&month=<?= $monthOffset + 1 ?>" class="btn">ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Âº</a>
                            <?php endif; ?>
                        <?php endif; ?>

                        <span class="week-range"><?= $dateLabel ?></span>
                    </div>
                    <div class="chart-box" style="height: 300px; position: relative;">
                        <canvas id="ordersChart"></canvas>
                    </div>
                </section>

                <!-- ===== DASHBOARD 2: Inventory / Low stock / Recent orders row ===== -->
                <section class="overview-grid">
                    <!-- Product Inventory -->
                    <div class="mini-panel">
                        <h4>Product Inventory <a href="/php-web/public/products">View all</a></h4>
                        <?php if (count($stockRows) > 0): ?>
                        <table class="inv-table">
                            <thead>
                                <tr><th>Product</th><th>Current Stock</th><th>Status</th><th>Reorder Level</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($stockRows, 0, 4) as $p): $out = $p['stock_quantity'] <= 0; ?>
                                <tr>
                                    <td><span class="prod-cell"><span class="prod-avatar"><?= strtoupper(substr($p['name'], 0, 1)) ?></span><?= htmlspecialchars($p['name']) ?></span></td>
                                    <td><?= (int)$p['stock_quantity'] ?></td>
                                    <td><span class="tag <?= $out ? 'out' : 'low' ?>"><?= $out ? 'Out' : 'Low' ?></span></td>
                                    <td><?= (int)$p['reorder_level'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                            <p class="mini-note">All products are well stocked.</p>
                        <?php endif; ?>
                    </div>

                    <!-- Low Stock Alerts -->
                    <div class="mini-panel">
                        <h4>Low Stock Alerts <a href="/php-web/public/products">Products</a></h4>
                        <?php if (count($stockRows) > 0): foreach (array_slice($stockRows, 0, 4) as $p): $out = $p['stock_quantity'] <= 0; ?>
                            <div class="alert-row">
                                <div class="alert-info">
                                    <span class="prod-avatar"><?= strtoupper(substr($p['name'], 0, 1)) ?></span>
                                    <div>
                                        <strong><?= htmlspecialchars($p['name']) ?></strong>
                                        <small><?= (int)$p['stock_quantity'] ?> left</small>
                                    </div>
                                </div>
                                <!-- REORDER TAG IS NOW HERE IN THE MIDDLE -->
                                <span class="tag reorder">Reorder at <?= (int)$p['reorder_level'] ?></span>
                                <div class="alert-tags">
                                    <span class="tag <?= $out ? 'out' : 'low' ?>"><?= $out ? 'Out of Stock' : 'Low Stock' ?></span>
                                </div>
                            </div>
                        <?php endforeach; else: ?>
                            <p class="mini-note">All products are well stocked.</p>
                        <?php endif; ?>
                    </div>

                    <!-- Recent Orders (mini) -->
                    <div class="mini-panel">
                        <h4>Recent Orders <a href="/php-web/public/orders">View all</a></h4>
                        <?php if (count($recentRows) > 0): foreach (array_slice($recentRows, 0, 4) as $row): ?>
                            <div class="mini-order">
                                <div style="min-width:0;">
                                    <strong><?= htmlspecialchars($row['retailer_name']) ?></strong>
                                    <small>#<?= $row['order_id'] ?> &middot; &#8369;<?= number_format($row['total_amount'], 2) ?></small>
                                </div>
                                <span class="status-pill <?= strtolower($row['order_status']) ?>"><?= strtoupper(str_replace('_', ' ', $row['order_status'])) ?></span>
                            </div>
                        <?php endforeach; else: ?>
                            <p class="mini-note">No orders for this period.</p>
                        <?php endif; ?>
                    </div>
                </section>

<!-- ===== UPDATED RECENT ORDERS PANEL ===== -->
<section class="orders-panel" style="background: transparent; box-shadow: none; padding: 0; margin-top: 24px;">
    <div class="orders-card">
        <h3 class="orders-title">Recent Orders <small>(<?= $dateLabel ?>)</small></h3>
        
        <div class="table-responsive" style="border: none; background: transparent;">
            <table class="new-orders-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th><a href="?filter=<?= $filter_type ?>&sort=retailer_asc">Name &#8597;</a></th>
                        <th>Items</th>
                        <th>Total</th>
                        <th><a href="?filter=<?= $filter_type ?>&sort=driver_asc">Drivers &#8597;</a></th>
                        <th><a href="?filter=<?= $filter_type ?>&sort=status_asc">Status &#8597;</a></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($recentRows) > 0): foreach ($recentRows as $row): ?>
                        <tr>
                            <td class="order-id">#<?= $row['order_id'] ?></td>
                            <td class="order-name"><?= htmlspecialchars($row['retailer_name']) ?></td>
                            <td class="order-items"><?= $row['total_items'] ?? 0 ?>x <?= htmlspecialchars($row['first_item_name'] ?? 'Items') ?></td>
                            <td class="order-total">P <?= number_format($row['total_amount'], 2) ?></td>
                            <td class="order-driver"><?= $row['driver_name'] ? htmlspecialchars($row['driver_name']) : '-' ?></td>
                            <td>
                                <span class="status-pill new-pill <?= strtolower($row['order_status']) ?>">
                                    <?= strtoupper($row['order_status']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="6" style="text-align:center; padding: 30px;">No orders found for this period.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="pagination new-pagination">
            <?php if ($page > 1): ?>
                <a href="?filter=<?= $filter_type ?>&sort=<?= $sort ?>&page=<?= $page - 1 ?>" class="page-btn">Prev</a>
            <?php else: ?>
                <span class="page-btn disabled">Prev</span>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php if ($i === $page): ?>
                    <span class="page-btn active"><?= $i ?></span>
                <?php else: ?>
                    <a href="?filter=<?= $filter_type ?>&sort=<?= $sort ?>&page=<?= $i ?>" class="page-btn"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
                <a href="?filter=<?= $filter_type ?>&sort=<?= $sort ?>&page=<?= $page + 1 ?>" class="page-btn">Next</a>
            <?php else: ?>
                <span class="page-btn disabled">Next</span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</section>  

                <!-- ===== DASHBOARD 3: Delivery Monitoring ===== -->
                <section class="panel delivery-panel" style="margin-top: 24px;">
                    <div class="panel-head">
                        <h3>Delivery Monitoring</h3>
                        <a href="/php-web/public/drivers" class="panel-link">Drivers</a>
                    </div>
                    <div id="deliveryList">
                        <?php if (count($deliveryRows) > 0): foreach ($deliveryRows as $row): ?>
                            <?php
                                $statusLabel = ucwords(str_replace('_', ' ', $row['order_status']));
                                if ($row['order_status'] === 'approved') $statusLabel = 'Waiting for Driver';
                                $driver = $row['driver_name'] ? htmlspecialchars($row['driver_name']) : 'No driver assigned';
                            ?>
                            <div class="delivery">
                                <div class="delivery-top">
                                    <strong>Order #<?= $row['order_id'] ?> ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚Â¡ <?= htmlspecialchars($row['customer_code']) ?></strong>
                                    <span class="d-pill"><?= $statusLabel ?></span>
                                </div>
                                <p class="delivery-driver">Driver: <strong><?= $driver ?></strong> ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚Â¡ <?= htmlspecialchars($row['address']) ?></p>
                            </div>
                        <?php endforeach; else: ?>
                            <p class="empty-note">No deliveries in progress.</p>
                        <?php endif; ?>
                    </div>
                    <div class="mini-pagination" id="deliveryPager"></div>
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
                datasets: [{ label: 'Orders', data: chartData, borderColor: '#C34200', backgroundColor: 'rgba(195, 66, 0, 0.1)', borderWidth: 2, fill: true, tension: 0.4, pointRadius: 2, pointBackgroundColor: '#C34200' }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { ticks: {} } } }
        });

        function toggleProfileMenu(e) { e.stopPropagation(); document.getElementById('profileDropdown').classList.toggle('show'); }
        document.addEventListener('click', function(e) {
            const m = document.getElementById('profileMenu');
            if (m && !m.contains(e.target)) document.getElementById('profileDropdown').classList.remove('show');
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

<!-- DELIVERY MONITORING PAGINATION -->
<script>
(function () {
    const PER_PAGE = 6;
    const items = Array.from(document.querySelectorAll('#deliveryList .delivery'));
    const pager = document.getElementById('deliveryPager');
    if (!pager || items.length <= PER_PAGE) return;

    const totalPages = Math.ceil(items.length / PER_PAGE);
    let current = 1;

    function render() {
        items.forEach((el, i) => {
            el.style.display = (i >= (current - 1) * PER_PAGE && i < current * PER_PAGE) ? '' : 'none';
        });
        pager.innerHTML = '';
        const add = (label, page, opts = {}) => {
            const b = document.createElement('button');
            b.type = 'button';
            b.textContent = label;
            if (opts.active) b.classList.add('active');
            if (opts.disabled) b.disabled = true;
            b.onclick = () => { current = page; render(); };
            pager.appendChild(b);
        };
        add('Prev', current - 1, { disabled: current === 1 });
        for (let p = 1; p <= totalPages; p++) add(p, p, { active: p === current });
        add('Next', current + 1, { disabled: current === totalPages });
    }
    render();
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
                                <a href="/php-web/public/orders/view?id=${item.order_id}" class="notif-item" style="${readStyle}" data-notif-id="${itemId}" onclick="markAsRead('${itemId}')">
                                    <div class="notif-icon">#${item.order_id}</div>
                                    <div class="notif-content">
                                        <p><strong>${item.retailer_name}</strong> placed a new order.</p>
                                        <span class="notif-time">${timeAgo} &middot; ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â±${Number(item.total_amount).toFixed(2)}</span>
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
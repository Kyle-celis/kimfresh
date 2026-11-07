

<?php /* Products view — variables provided by ProductController::index() */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KimFresh - Products</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/php-web/public/css/theme.css">
    <link rel="stylesheet" href="/php-web/public/css/products.css">
    <link rel="stylesheet" href="/php-web/public/css/dashboard.css">
    <link rel="stylesheet" href="/php-web/public/css/responsive.css">
    <style>
        :root { --primary: #C34200; --primary-dark: #9a3400; --primary-soft: #fdf0e6; }
        .table-section { background: white; border-radius: 16px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; font-size: 13px; color: #666; border-bottom: 1px solid #eee; background: transparent; font-weight: 700; }
        td { padding: 15px; border-bottom: 1px solid #f5f5f5; font-size: 13px; }
        th a { color: #666; text-decoration: none; }
        th a:hover { color: #C34200 !important; text-decoration: underline; }
        .product-name { font-weight: 800; color: #333; font-size: 14px; margin-bottom: 3px; }
        .product-sku { color: #888; font-size: 12px; }
        .price { font-weight: 700; color: #333; }
        .status { padding: 4px 12px; font-size: 11px; font-weight: 700; border-radius: 20px; }
        .status.in-stock, .status.available { color: #15803d; background: #dcfce7; }
        .status.low-stock { color: #b45309; background: #fef3c7; }
        .status.expired { color: #991b1b; background: #fee2e2; border: 1px solid #f87171; }
        .actions { display: flex; gap: 8px; }
        .btn-edit { padding: 7px 16px; font-size: 12px; font-weight: 700; color: #333; background: #fff; border: 1px solid #ddd; border-radius: 8px; cursor: pointer; }
        .btn-edit:hover { background: #f8f9fa; }
        .btn-delete { padding: 7px 16px; font-size: 12px; font-weight: 700; color: white; background: #dc2626; border: 1px solid #dc2626; border-radius: 8px; cursor: pointer; }
        .btn-delete:hover { background: #b91c1c; }
        dialog { width: 90%; max-width: 460px; margin: auto; padding: 28px; border: none; border-radius: 18px; box-shadow: 0 20px 60px rgba(0,0,0,0.35); }
        dialog::backdrop { background: rgba(0,0,0,0.55); }
        dialog h2 { font-size: 20px; font-weight: 800; margin-bottom: 15px; }
        dialog label { display: block; margin-bottom: 5px; font-size: 12px; font-weight: 700; color: #555; }
        dialog input { width: 100%; height: 40px; margin-bottom: 15px; padding: 0 14px; font-size: 13px; border: 1px solid #ddd; border-radius: 8px; outline: none; }
        dialog .row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        dialog .dialog-buttons { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
        dialog .cancel-btn { padding: 10px 20px; background: #eee; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; }
        dialog .primary-btn { padding: 10px 20px; background: #C34200; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; }
        .error-banner { background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; border: 1px solid #f87171; }
        .profile-menu { position: relative; }
        .profile-trigger { display: flex; align-items: center; gap: 10px; background: transparent; border: none; cursor: pointer; padding: 8px; border-radius: 10px; }
        .profile-trigger:hover { background: var(--primary-soft); }
        .profile-dropdown { position: absolute; top: 100%; right: 0; background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); width: 200px; padding: 8px; display: none; z-index: 100; border: 1px solid #eee; }
        .profile-dropdown.show { display: block; }
        .dropdown-account { padding: 12px; border-bottom: 1px solid #eee; margin-bottom: 8px; }
        .dropdown-account strong { display: block; font-size: 14px; color: #333; }
        .dropdown-account span { font-size: 12px; color: #777; }
        .dropdown-item { display: flex; align-items: center; gap: 10px; padding: 10px 12px; text-decoration: none; color: #333; font-size: 13px; font-weight: 600; border-radius: 8px; }
        .dropdown-item:hover { background: #fdf0e6; color: #C34200; }
        .btn-export { background: #C34200; color: white; padding: 10px 20px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-export:hover { background: #9a3400; }
        .pagination { display: flex; justify-content: center; gap: 6px; margin-top: 24px; }
        .pagination a, .pagination span { display: inline-flex; align-items: center; justify-content: center; min-width: 36px; height: 36px; padding: 0 12px; border-radius: 8px; font-size: 13px; font-weight: 700; text-decoration: none; border: 1px solid #ddd; color: #555; background: white; }
        .pagination a:hover { background: #fdf0e6; border-color: #C34200; color: #C34200; }
        .pagination .active { background: #C34200; border-color: #C34200; color: white; }
        .pagination .disabled { opacity: 0.5; pointer-events: none; }
        .store-status-box { background: white; padding: 15px 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; }
        .store-status-box.open { border-left: 5px solid #15803d; }
        .store-status-box.closed { border-left: 5px solid #dc2626; }
        .btn-store { color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; cursor: pointer; }
        .btn-store.open { background: #dc2626; }
        .btn-store.closed { background: #15803d; }
        @media print {
            .sidebar, .top-header, .page-head, .actions, .pagination, .btn-export, .primary-btn, .store-status-box { display: none !important; }
            body { background: white !important; margin: 0 !important; }
            .dashboard-layout { display: block !important; }
            .main-area { margin-left: 0 !important; width: 100% !important; }
            .main-content { padding: 0 !important; }
            .table-section { box-shadow: none !important; border: none !important; padding: 0 !important; }
            table { border-collapse: collapse !important; }
            th, td { border: 1px solid #000 !important; padding: 8px !important; font-size: 12px !important; }
            th { background: #eee !important; }
            h1 { text-align: center; color: #C34200 !important; }
            th:last-child, td:last-child { display: none !important; }
        }
        .action-menu-container { position: relative; display: inline-block; }
        .action-menu-btn { background: transparent; border: 1px solid #ddd; border-radius: 8px; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #333; }
        .action-menu-btn:hover { background: #f8f9fa; border-color: #C34200; color: #C34200; }
        .action-menu-dropdown { position: absolute; top: 45px; right: 0; background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); width: 180px; padding: 8px; display: none; z-index: 100; border: 1px solid #eee; }
        .action-menu-dropdown.show { display: block; }
        .action-menu-item { display: flex; align-items: center; gap: 10px; padding: 10px 12px; text-decoration: none; color: #333; font-size: 13px; font-weight: 600; border-radius: 8px; border: none; background: transparent; width: 100%; cursor: pointer; text-align: left; }
        .action-menu-item:hover { background: #fdf0e6; color: #C34200; }
    </style>
<!-- NOTIFICATION CSS -->
<style>
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
                <a href="/php-web/public/products" class="active">Products</a>
                <a href="/php-web/public/drivers">Drivers</a>
                <a href="/php-web/public/analytics">Analytics</a>
                <?php if (!empty($_SESSION['is_superadmin'])): ?>
                    <a href="/php-web/public/superadmin">Manage Admins</a>
                <?php endif; ?>
            </nav>
        </aside>

        <div class="main-area">
            <header class="top-header">
<!-- NOTIFICATION BELL -->
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
<!-- END NOTIFICATION BELL -->
                <div class="profile-menu" id="profileMenu">
                    <button type="button" class="profile-trigger" onclick="toggleProfileMenu(event)">
                        <div class="avatar">A</div>
                        <div class="user-info" style="text-align: left;">
                            <p class="user-name" style="margin: 0;"><?= htmlspecialchars($_SESSION['admin_name']) ?></p>
                            <p class="user-role" style="margin: 0;">Administrator</p>
                        </div>
                    </button>
                    <div class="profile-dropdown" id="profileDropdown">
                        <div class="dropdown-account">
                            <strong><?= htmlspecialchars($_SESSION['admin_name']) ?></strong>
                            <span><?= htmlspecialchars($_SESSION['admin_email'] ?? 'admin@kimfresh.com') ?></span>
                        </div>
                        <a href="/php-web/public/settings" class="dropdown-item">Settings</a>
                        <a href="/php-web/public/logs" class="dropdown-item">Activity Logs</a>
                        <a href="/php-web/public/logout" class="dropdown-item" style="color: #C34200;">Logout</a>
                    </div>
                </div>
            </header>

            <main class="main-content">
                <?php require __DIR__ . '/../partials/flash.php'; ?>
                <?php if (!empty($_GET['import_success'])): ?>
                    <div style="background:#dcfce7;color:#166534;padding:12px;border-radius:8px;margin-bottom:20px;font-weight:bold;border:1px solid #bbf7d0;">
                        Successfully imported/updated <?= (int)$_GET['import_success'] ?> products!
                    </div>
                <?php endif; ?>
                <?php if (!empty($_GET['error'])): ?>
                    <div style="background:#fee2e2;color:#991b1b;padding:12px;border-radius:8px;margin-bottom:20px;font-weight:bold;border:1px solid #f87171;">
                        Error: <?= isset($_GET['msg']) ? htmlspecialchars($_GET['msg']) : 'Please check your CSV format.' ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($error_msg)): ?>
                    <div class="error-banner"><?= $error_msg ?></div>
                <?php endif; ?>

                <p class="overview-label">Manage</p>

                <div class="page-head" style="display:flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                    <div>
                        <h1 style="margin:0;">Products</h1>
                        <p style="margin-top:5px;">Add, delete and keep track of your kimchi stock.</p>
                    </div>
                    <div style="display:flex; gap:10px; flex-wrap: wrap; align-items: center;">
                        <div class="action-menu-container">
                            <button class="action-menu-btn" onclick="toggleActionMenu(event)">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="1"></circle>
                                    <circle cx="12" cy="5" r="1"></circle>
                                    <circle cx="12" cy="19" r="1"></circle>
                                </svg>
                            </button>
                            <div class="action-menu-dropdown" id="actionMenuDropdown">
                                <a href="/php-web/public/products/export-csv" class="action-menu-item">Export CSV</a>
                                <a href="?export_pdf=1" download="KimFresh_Products.pdf" class="action-menu-item">Export PDF</a>
                                <button type="button" class="action-menu-item" onclick="document.getElementById('importModal').showModal(); document.getElementById('actionMenuDropdown').classList.remove('show');">Import CSV</button>
                            </div>
                        </div>
                        <?php if ($is_store_open): ?>
    <button class="primary-btn" style="opacity: 0.5; cursor: not-allowed;" disabled title="Close the store first to add products.">+ Add Product</button>
<?php else: ?>
    <button class="primary-btn" onclick="openProductModal()">+ Add Product</button>
<?php endif; ?>
                    </div>
                </div>

                <div class="store-status-box <?= $is_store_open ? 'open' : 'closed' ?>">
                    <div>
                        <h3 style="margin: 0; font-size: 16px;">Store Status:
                            <span style="color: <?= $is_store_open ? '#15803d' : '#dc2626' ?>;">
                                <?= $is_store_open ? 'OPEN' : 'CLOSED' ?>
                            </span>
                        </h3>
                        <p style="margin: 5px 0 0 0; font-size: 13px; color: #666;">
                            <?php if ($store['manual_override'] !== null): ?>
                                Manual Override Active.
                            <?php else: ?>
                                Auto Schedule: <?= date('h:i A', strtotime($store['open_time'])) ?> - <?= date('h:i A', strtotime($store['close_time'])) ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <?php if ($store['manual_override'] !== null): ?>
                            <form method="POST" style="margin:0;">
                                <?= csrf_field() ?>
                                <button type="submit" name="reset_schedule" class="btn-store" style="background: #555;">Reset to Schedule</button>
                            </form>
                        <?php endif; ?>
                        <form method="POST" style="margin:0;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="override_value" value="<?= $is_store_open ? 0 : 1 ?>">
                            <button type="submit" name="toggle_manual" class="btn-store <?= $is_store_open ? 'open' : 'closed' ?>">
                                <?= $is_store_open ? 'Deactivate' : 'Activate' ?>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="stats">
                    <div class="stat"><p>Total Products</p><h2><?= $total_products ?></h2></div>
                    <div class="stat" style="border-left-color: #15803d;"><p>Available Products</p><h2><?= $available_products ?></h2></div>
                    <div class="stat warn"><p>Low Stock</p><h2><?= $low_stock ?></h2></div>
                </div>

                <section class="table-section">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th><a href="?sort=<?= $sort === 'name_asc' ? 'name_desc' : 'name_asc' ?>">Product &#8597;</a></th>
                                    <th>SKU</th>
                                    <th><a href="?sort=<?= $sort === 'price_asc' ? 'price_desc' : 'price_asc' ?>">Price &#8597;</a></th>
                                    <th><a href="?sort=<?= $sort === 'stock_asc' ? 'stock_desc' : 'stock_asc' ?>">Stock &#8597;</a></th>
                                    <th>Reorder Level</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($products->num_rows > 0): while ($row = $products->fetch_assoc()): ?>
                                    <?php
                                        $isLow = $row['stock_quantity'] <= $row['reorder_level'];
                                        $stockLabel = $isLow ? 'Low Stock' : 'In Stock';
                                        $stockClass = $isLow ? 'low-stock' : 'in-stock';
                                        $isHighlighted = isset($_SESSION['highlight_products']) && in_array($row['product_id'], $_SESSION['highlight_products']);
                                        $rowStyle = $isHighlighted ? "style='background-color:#fef3c7;'" : "";
                                    ?>
                                    <tr <?= $rowStyle ?>>
                                        <td>
                                            <div class="product-name"><?= htmlspecialchars($row['name']) ?></div>
                                            
                                            <?php 
                                                if (!empty($row['expiry_date'])) {
                                                    $expiry = new DateTime($row['expiry_date']);
                                                    $today = new DateTime(date('Y-m-d'));
                                                    $daysLeft = $today->diff($expiry)->format('%r%a');
                                                    
                                                    if ($daysLeft < 0) {
                                                        $expiryColor = '#dc2626'; // Red
                                                        $expiryText = "EXPIRED (" . abs($daysLeft) . " days ago)";
                                                    } elseif ($daysLeft <= 3) {
                                                        $expiryColor = '#b45309'; // Orange
                                                        $expiryText = "Expires " . $expiry->format('M d, Y') . " (" . $daysLeft . " days left)";
                                                    } else {
                                                        $expiryColor = '#15803d'; // Green
                                                        $expiryText = "Expires " . $expiry->format('M d, Y') . " (" . $daysLeft . " days left)";
                                                    }
                                                    echo "<div style='font-size: 11px; font-weight: bold; color: $expiryColor; margin-top: 4px;'>$expiryText</div>";
                                                }
                                            ?>

                                            <?php if (!empty($row['description'])): ?>
                                                <div style="font-size: 11px; color: #777; margin-top: 4px; max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($row['description']) ?>">
                                                    <?= htmlspecialchars($row['description']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <!-- THE MISSING COLUMNS -->
                                        <td style="color: #666; font-size: 12px; font-weight: 600;"><?= htmlspecialchars($row['sku']) ?></td>
                                        <td class="price">&#8369;<?= number_format($row['unit_price'], 2) ?></td>
                                        <td><?= $row['stock_quantity'] ?> <span class="status <?= $stockClass ?>"><?= $stockLabel ?></span></td>
                                        <td><?= $row['reorder_level'] ?></td>
                                        <td>
    <?php if ($row['status'] === 'expired' || (isset($daysLeft) && $daysLeft < 0)): ?>
        <span class="status expired">EXPIRED</span>
    <?php else: ?>
        <span class="status available">Available</span>
    <?php endif; ?>
</td>
                                        <td class="actions-cell">
                                            <div class="actions">
<?php if ($is_store_open): ?>
    <button class="btn-edit" style="opacity: 0.5; cursor: not-allowed;" disabled title="Close the store first to edit products.">Edit</button>
<?php else: ?>
    <button class="btn-edit" onclick='openProductModal(<?= json_encode($row) ?>)'>Edit</button>
<?php endif; ?>
                                                <?php if ($is_store_open): ?>
                                                    <button class="btn-delete" style="opacity: 0.5; cursor: not-allowed;" disabled title="Close the store first to delete products.">Delete</button>
                                                <?php else: ?>
                                                    <button class="btn-delete" onclick='openDeleteModal(<?= $row['product_id'] ?>, <?= json_encode($row['name']) ?>)'>Delete</button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; else: ?>
                                    <tr><td colspan="7" style="text-align:center;">No products found</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?sort=<?= $sort ?>&page=<?= $page - 1 ?>">Prev</a>
                        <?php else: ?>
                            <span class="disabled">Prev</span>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i === $page): ?>
                                <span class="active"><?= $i ?></span>
                            <?php else: ?>
                                <a href="?sort=<?= $sort ?>&page=<?= $i ?>"><?= $i ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        <?php if ($page < $total_pages): ?>
                            <a href="?sort=<?= $sort ?>&page=<?= $page + 1 ?>">Next</a>
                        <?php else: ?>
                            <span class="disabled">Next</span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </section>
            </main>
        </div>
    </div>

    <!-- ADD/EDIT MODAL -->
    <dialog id="productModal">
        <h2 id="modalTitle">Add Product</h2>
        <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="product_id" id="form_id" value="">
            <label>Product Name</label>
            <input type="text" name="name" id="form_name" maxlength="50" required>
            <label style="margin-top: 15px;">Shelf Life (Days)</label>
            <input type="number" min="1" name="shelf_life_days" id="form_shelf_life" value="30" required>
            
            <label>Description</label>
<textarea name="description" id="form_description" maxlength="255" rows="2" placeholder="Brief description..." style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 15px; resize: vertical; font-family: inherit;"></textarea>
            
            <div class="row">
                <div>
                    <label>Price (&#8369;)</label>
                    <input type="number" step="0.01" min="0" name="unit_price" id="form_price" required>
                </div>
                <div>
                    <label>Reorder Level</label>
                    <input type="number" min="1" name="reorder_level" id="form_reorder" required>
                </div>
            </div>
            <label>Stock Quantity</label>
            <input type="number" min="0" name="stock_quantity" id="form_stock" required>
            <div class="dialog-buttons">
                <button type="button" class="cancel-btn" onclick="closeProductModal()">Cancel</button>
                <button type="submit" name="save_product" class="primary-btn">Save Product</button>
            </div>
        </form>
    </dialog>

    <!-- DELETE MODAL -->
    <dialog id="deleteModal">
        <h2 style="color: #dc2626; margin-bottom: 10px;">Confirm Deletion</h2>
        <p style="margin-bottom: 20px; font-size: 14px; color: #555;">
            Are you sure you want to permanently delete <strong id="deleteProductName"></strong>? This action cannot be undone.
        </p>
        <form method="POST" id="deleteForm">
            <?= csrf_field() ?>
            <input type="hidden" name="product_id" id="delete_product_id" value="">
            <div class="dialog-buttons">
                <button type="button" class="cancel-btn" onclick="closeDeleteModal()">Cancel</button>
                <button type="submit" name="delete_product" style="padding: 10px 20px; background: #dc2626; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold;">Yes, Delete</button>
            </div>
        </form>
    </dialog>

    <!-- IMPORT CSV MODAL -->
    <dialog id="importModal">
        <h2 style="margin-top:0;">Import Products (CSV)</h2>
        <p style="font-size: 13px; color: #555; margin-bottom: 15px;">
            Upload a CSV to bulk-add or update. Required columns: <code>product_id, name, description, sku, unit_price, stock_quantity, reorder_level, status</code>.
        </p>
        <form action="/php-web/public/products/import-csv" method="POST" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="file" name="csv_file" accept=".csv" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px; margin-bottom:20px;">
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="document.getElementById('importModal').close()" style="padding: 10px 20px; background: #eee; border: none; border-radius: 8px; font-weight: bold; cursor: pointer;">Cancel</button>
                <button type="submit" name="import_csv" style="padding: 10px 20px; background: #333; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer;">Upload CSV</button>
            </div>
        </form>
    </dialog>

    <?php unset($_SESSION['highlight_products']); ?>

    <script>
        function toggleActionMenu(event) {
            event.stopPropagation();
            document.getElementById('actionMenuDropdown').classList.toggle('show');
        }
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.action-menu-container')) {
                document.getElementById('actionMenuDropdown').classList.remove('show');
            }
        });

        function openProductModal(product = null) {
            const modal = document.getElementById('productModal');
            if (product) {
                document.getElementById('modalTitle').textContent = 'Edit Product';
                document.getElementById('form_id').value = product.product_id;
                document.getElementById('form_name').value = product.name;
                document.getElementById('form_description').value = product.description || '';
                document.getElementById('form_price').value = product.unit_price;
                document.getElementById('form_reorder').value = product.reorder_level;
                document.getElementById('form_stock').value = product.stock_quantity;
                document.getElementById('form_shelf_life').value = product.shelf_life_days || 30;
            } else {
                document.getElementById('modalTitle').textContent = 'Add Product';
                document.getElementById('form_id').value = '';
                document.getElementById('form_name').value = '';
                document.getElementById('form_description').value = '';
                document.getElementById('form_price').value = '';
                document.getElementById('form_reorder').value = '';
                document.getElementById('form_stock').value = '';
                document.getElementById('form_shelf_life').value = 30;
            }
            modal.showModal();
        }
        function closeProductModal() { document.getElementById('productModal').close(); }

        function openDeleteModal(id, name) {
            document.getElementById('delete_product_id').value = id;
            document.getElementById('deleteProductName').textContent = name;
            document.getElementById('deleteModal').showModal();
        }
        function closeDeleteModal() { document.getElementById('deleteModal').close(); }

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


<script>
    // AUTO REFRESH 15s
    // Pause auto-refresh if the user is currently typing in a modal, 
    // or if the store is open (to avoid clearing the "success" banners while they work).
    let autoRefreshTimer = setInterval(function() {
        // Don't refresh if a modal is open
        const productModal = document.getElementById('productModal');
        const deleteModal = document.getElementById('deleteModal');
        const importModal = document.getElementById('importModal');
        
        if (productModal.open || deleteModal.open || importModal.open) {
            return; 
        }
        
        // Don't refresh if the store is open (prevents interrupting the admin's view)
        const isStoreOpen = <?= json_encode($is_store_open) ?>;
        if (isStoreOpen) {
            return;
        }

        // Otherwise, silently reload the page
        location.reload();
    }, 15000);
</script>
</body>
</html>
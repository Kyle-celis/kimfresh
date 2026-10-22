<?php
session_start();
require 'db.php';
require 'log_helper.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// --- FETCH STORE STATUS ---
$store_res = $conn->query("SELECT is_open FROM store_settings WHERE id = 1");
$store_data = $store_res->fetch_assoc();
$is_store_open = $store_data['is_open'];

// --- HANDLE PDF EXPORT (Using Print CSS) ---
$trigger_print = false;
if (isset($_POST['export_pdf'])) {
    logActivity($conn, $_SESSION['admin_id'], "Exported Products to PDF");
    $trigger_print = true;
    $products = $conn->query("SELECT * FROM product ORDER BY product_id ASC");
}

// --- HANDLE HARD DELETE ---
if (isset($_POST['delete_product'])) {
    $product_id = $_POST['product_id'];

    $stmt = $conn->prepare("SELECT name FROM product WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    $product_name = $product ? $product['name'] : 'Unknown';

    $stmt = $conn->prepare("DELETE FROM order_item WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();

    $stmt = $conn->prepare("DELETE FROM product WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    
    if ($stmt->execute()) {
        logActivity($conn, $_SESSION['admin_id'], "Permanently Deleted Product: " . $product_name);
    }

    header("Location: products.php");
    exit();
}

$error_msg = "";

// Handle Add/Edit
if (isset($_POST['save_product'])) {
    $product_id = $_POST['product_id'];
    $name = $_POST['name'];
    $sku = $_POST['sku'];
    $price = $_POST['unit_price'];
    $stock = $_POST['stock_quantity'];
    $reorder = $_POST['reorder_level'];

    if ($price < 0 || $stock < 0 || $reorder < 0) {
        $error_msg = "Error: Price, Stock, and Reorder level cannot be negative!";
    } else {
        if (empty($product_id)) {
            $stmt = $conn->prepare("INSERT INTO product (name, sku, unit_price, stock_quantity, reorder_level, status) VALUES (?, ?, ?, ?, ?, 'available')");
            $stmt->bind_param("ssdii", $name, $sku, $price, $stock, $reorder);
        } else {
            $stmt = $conn->prepare("UPDATE product SET name=?, sku=?, unit_price=?, stock_quantity=?, reorder_level=? WHERE product_id=?");
            $stmt->bind_param("ssdiii", $name, $sku, $price, $stock, $reorder, $product_id);
        }
        $stmt->execute();
        
        $action = empty($product_id) ? "Added new product: " : "Updated product: ";
        logActivity($conn, $_SESSION['admin_id'], $action . $name);
        
        header("Location: products.php");
        exit();
    }
}

// Fetch Data for Stats
$total_products = $conn->query("SELECT COUNT(*) AS c FROM product")->fetch_assoc()['c'];
$low_stock = $conn->query("SELECT COUNT(*) AS c FROM product WHERE stock_quantity <= reorder_level")->fetch_assoc()['c'];
$available_products = $conn->query("SELECT COUNT(*) AS c FROM product WHERE status = 'available' AND stock_quantity > 0")->fetch_assoc()['c'];

if (!$trigger_print) {
    // --- SORTING LOGIC ---
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'id_asc';
    $orderBy = "product_id ASC"; 

    if ($sort == 'name_asc') $orderBy = "name ASC";
    if ($sort == 'name_desc') $orderBy = "name DESC";
    if ($sort == 'price_asc') $orderBy = "unit_price ASC";
    if ($sort == 'price_desc') $orderBy = "unit_price DESC";
    if ($sort == 'stock_asc') $orderBy = "stock_quantity ASC";
    if ($sort == 'stock_desc') $orderBy = "stock_quantity DESC";

    // --- PAGINATION LOGIC ---
    $limit = 10;
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($page < 1) $page = 1;
    $offset = ($page - 1) * $limit;

    $countQuery = $conn->query("SELECT COUNT(*) AS total FROM product");
    $total_rows = $countQuery->fetch_assoc()['total'];
    $total_pages = ceil($total_rows / $limit);

    $sql = "SELECT * FROM product ORDER BY $orderBy LIMIT $limit OFFSET $offset";
    $products = $conn->query($sql);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KimFresh - Products</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/products.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/responsive.css">
    
    <style>
        :root {
            --primary: #C34200;
            --primary-dark: #9a3400;
            --primary-soft: #fdf0e6;
        }

        .table-section { background: white; border-radius: 16px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; font-size: 13px; color: #666; border-bottom: 1px solid #eee; background: transparent; font-weight: 700; }
        td { padding: 15px; border-bottom: 1px solid #f5f5f5; font-size: 13px; }
        
        th a { color: #666; transition: color 0.2s ease; text-decoration: none; }
        th a:hover { color: #C34200 !important; text-decoration: underline; }
        
        .product-name { font-weight: 800; color: #333; font-size: 14px; margin-bottom: 3px; }
        .product-sku { color: #888; font-size: 12px; }
        .price { font-weight: 700; color: #333; }
        
        .status { padding: 4px 12px; font-size: 11px; font-weight: 700; border-radius: 20px; }
        .status.in-stock, .status.available { color: #15803d; background: #dcfce7; }
        .status.low-stock { color: #b45309; background: #fef3c7; }

        .actions { display: flex; gap: 8px; }
        .btn-edit { padding: 7px 16px; font-size: 12px; font-weight: 700; color: #333; background: #fff; border: 1px solid #ddd; border-radius: 8px; cursor: pointer; text-decoration: none; }
        .btn-edit:hover { background: #f8f9fa; }
        .btn-delete { padding: 7px 16px; font-size: 12px; font-weight: 700; color: white; background: #dc2626; border: 1px solid #dc2626; border-radius: 8px; cursor: pointer; }
        .btn-delete:hover { background: #b91c1c; }

        dialog { width: 90%; max-width: 460px; margin: auto; padding: 28px; border: none; border-radius: 18px; box-shadow: 0 20px 60px rgba(0,0,0,0.35); }
        dialog::backdrop { background: rgba(0,0,0,0.55); }
        dialog h2 { font-size: 20px; font-weight: 800; margin-bottom: 15px; }
        dialog label { display: block; margin-bottom: 5px; font-size: 12px; font-weight: 700; color: #555;}
        dialog input { width: 100%; height: 40px; margin-bottom: 15px; padding: 0 14px; font-size: 13px; border: 1px solid #ddd; border-radius: 8px; outline: none; }
        dialog .row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        dialog .dialog-buttons { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
        dialog .cancel-btn { padding: 10px 20px; background: #eee; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; }
        dialog .primary-btn { padding: 10px 20px; background: #C34200; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; }
        
        .error-banner { background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; border: 1px solid #f87171; }

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

        .btn-export { background: #C34200; color: white; padding: 10px 20px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.2s; }
        .btn-export:hover { background: #9a3400; }

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

        /* --- PRINT STYLES FOR PDF --- */
        @media print {
            .sidebar, .top-header, .page-head, .actions, .pagination, .btn-export, .primary-btn { display: none !important; }
            body { background: white !important; margin: 0 !important; padding: 0 !important; }
            .dashboard-layout { display: block !important; }
            .main-area { margin-left: 0 !important; width: 100% !important; }
            .main-content { padding: 0 !important; }
            .table-section { box-shadow: none !important; border: none !important; padding: 0 !important; margin-top: 0 !important; }
            table { width: 100% !important; border-collapse: collapse !important; }
            th, td { border: 1px solid #000 !important; padding: 8px !important; font-size: 12px !important; color: #000 !important; }
            th { background: #eee !important; }
            h1 { margin-bottom: 20px !important; text-align: center; color: #C34200 !important; }
            .stats { display: flex !important; gap: 20px; margin-bottom: 20px; }
            .stat { border: 1px solid #000 !important; padding: 10px !important; flex: 1; text-align: center; }
            .stat h2 { margin: 0; font-size: 18px; }
            .stat p { margin: 0 0 5px 0; font-size: 12px; }
            th:last-child, td:last-child { display: none !important; }
        }

        /* =========================================
           MOBILE COMPACT TABLE LAYOUT & BUTTON FIX
           ========================================= */
        @media (max-width: 768px) {
            /* --- FIX HEADER BUTTONS --- */
            .page-head {
                flex-direction: column !important;
                align-items: stretch !important;
                gap: 15px !important;
            }
            
            .page-head > div:last-child {
                display: flex !important;
                flex-direction: row !important;
                gap: 10px !important;
                width: 100% !important;
            }

            .page-head .btn-export,
            .page-head .primary-btn {
                width: auto !important;
                flex: 1 !important; 
                text-align: center !important;
                padding: 10px 12px !important;
                font-size: 13px !important;
                white-space: nowrap !important;
            }

            /* --- FIX TABLE --- */
            .table-section {
                padding: 10px !important; 
            }

            .table-responsive {
                overflow-x: hidden !important; 
                width: 100% !important;
            }

            table {
                width: 100% !important;
                min-width: 0 !important; 
                table-layout: fixed; 
            }

            th, td {
                padding: 8px 4px !important;
                font-size: 11px !important;
                word-wrap: break-word;
                white-space: normal !important;
            }

            /* Hide the Reorder Level column */
            th:nth-child(4), td:nth-child(4) {
                display: none !important; 
            }

            /* Stack action buttons */
            .actions {
                flex-direction: column;
                gap: 4px;
            }
            
            .btn-edit,
            .btn-delete {
                padding: 4px 6px !important;
                font-size: 10px !important;
                width: 100%;
                text-align: center;
            }

            .status {
                font-size: 9px !important;
                padding: 2px 4px !important;
                display: inline-block;
            }
        }
    </style>
</head>
<body <?php if ($trigger_print) echo 'onload="window.print()"'; ?>>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-brand">
                <img src="logo.png" alt="KimFresh" class="logo">
                <p>Administration</p>
            </div>
            <nav class="menu">
                <a href="dashboard.php">Dashboard</a>
                <a href="orders.php">Orders</a>
                <a href="products.php" class="active">Products</a>
                <a href="drivers.php">Drivers</a>
                <?php if ($_SESSION['admin_id'] == 1): ?>
                    <a href="superadmin.php">Manage Admins</a>
                <?php endif; ?>
            </nav>
        </aside>

        <div class="main-area">
            <header class="top-header">
                <div class="profile-menu" id="profileMenu">
                    <button type="button" class="profile-trigger" onclick="toggleProfileMenu(event)">
                        <div class="avatar">A</div>
                        <div class="user-info" style="text-align: left;">
                            <p class="user-name" style="margin: 0;"><?php echo $_SESSION['admin_name']; ?></p>
                            <p class="user-role" style="margin: 0;">Administrator</p>
                        </div>
                        <span style="font-size: 18px; color: #999; margin-left: 5px;">⌄</span>
                    </button>
                    <div class="profile-dropdown" id="profileDropdown">
                        <div class="dropdown-account">
                            <strong><?php echo $_SESSION['admin_name']; ?></strong>
                    </div>
                        <a href="settings.php" class="dropdown-item">Settings</a>
                        <a href="logs.php" class="dropdown-item">Activity Logs</a>
                        <a href="logout.php" class="dropdown-item" style="color: #C34200;">Logout</a>
                    </div>
                </div>
            </header>

            <main class="main-content">
                <p class="overview-label">Manage</p>

                <div class="page-head" style="display:flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                    <div>
                        <h1 style="margin:0;">Products</h1>
                        <p style="margin-top:5px;">Add, delete and keep track of your kimchi stock.</p>
                    </div>
                    <div style="display:flex; gap:10px;">
                        <form method="POST" style="margin:0; width: 100%;">
                            <button type="submit" name="export_pdf" class="btn-export" style="width: 100%;">Export to PDF</button>
                        </form>
                        <button class="primary-btn" onclick="openProductModal()" style="width: 100%;">+ Add Product</button>
                    </div>
                </div>

                <?php if(!empty($error_msg)): ?>
                    <div class="error-banner"><?php echo $error_msg; ?></div>
                <?php endif; ?>

                <!-- UPDATED STATS WITH AVAILABLE -->
                <div class="stats">
                    <div class="stat"><p>Total Products</p><h2><?php echo $total_products; ?></h2></div>
                    <div class="stat" style="border-left-color: #15803d;"><p>Available Products</p><h2><?php echo $available_products; ?></h2></div>
                    <div class="stat warn"><p>Low Stock</p><h2><?php echo $low_stock; ?></h2></div>
                </div>

                <section class="table-section">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th><a href="?sort=<?php echo ($sort == 'name_asc' ? 'name_desc' : 'name_asc'); ?>">Product ↕</a></th>
                                    <th><a href="?sort=<?php echo ($sort == 'price_asc' ? 'price_desc' : 'price_asc'); ?>">Price ↕</a></th>
                                    <th><a href="?sort=<?php echo ($sort == 'stock_asc' ? 'stock_desc' : 'stock_asc'); ?>">Stock ↕</a></th>
                                    <th>Reorder Level</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($products->num_rows > 0) {
                                    while($row = $products->fetch_assoc()) {
                                        $isLow = $row['stock_quantity'] <= $row['reorder_level'];
                                        $stockLabel = $isLow ? 'Low Stock' : 'In Stock';
                                        $stockClass = $isLow ? 'low-stock' : 'in-stock';
                                        
                                        echo "<tr>";
                                        echo "<td data-label='Product'>
                                                <div class='product-name'>" . htmlspecialchars($row['name']) . "</div>
                                                <div class='product-sku'>" . htmlspecialchars($row['sku']) . "</div>
                                              </td>";
                                        echo "<td data-label='Price' class='price'>₱" . number_format($row['unit_price'], 2) . "</td>";
                                        echo "<td data-label='Stock'>" . $row['stock_quantity'] . " <span class='status " . $stockClass . "'>" . $stockLabel . "</span></td>";
                                        echo "<td data-label='Reorder'>" . $row['reorder_level'] . "</td>";
                                        echo "<td data-label='Status'><span class='status available'>Available</span></td>";
                                        
                                        echo "<td data-label='Action' class='actions-cell'>
                                                <div class='actions'>
                                                    <button class='btn-edit' onclick='openProductModal(" . json_encode($row) . ")'>Edit</button>";
                                        
                                        if ($is_store_open) {
                                            echo "<button class='btn-delete' style='opacity: 0.5; cursor: not-allowed;' title='Close the store first to delete products.' disabled>Delete</button>";
                                        } else {
                                            echo "<button class='btn-delete' onclick='openDeleteModal(" . $row['product_id'] . ", \"" . addslashes($row['name']) . "\")'>Delete</button>";
                                        }
                                        
                                        echo "</div></td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='6' class='empty' style='text-align:center;'>No products found</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if (!$trigger_print && $total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?sort=<?php echo $sort; ?>&page=<?php echo $page - 1; ?>">Prev</a>
                        <?php else: ?>
                            <span class="disabled">Prev</span>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="active"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?sort=<?php echo $sort; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?sort=<?php echo $sort; ?>&page=<?php echo $page + 1; ?>">Next</a>
                        <?php else: ?>
                            <span class="disabled">Next</span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </section>
            </main>
        </div>
    </div>

<!-- ADD / EDIT MODAL -->
<dialog id="productModal">
    <h2 id="modalTitle">Add Product</h2>
    <form method="POST">
        <input type="hidden" name="product_id" id="form_id" value="">
        
        <label>Product Name</label>
        <input type="text" name="name" id="form_name" maxlength="50" required>
        
        <label>SKU</label>
        <input type="text" name="sku" id="form_sku" maxlength="50">
        
        <div class="row">
            <div>
                <label>Price (₱)</label>
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

    <!-- CUSTOM DELETE CONFIRMATION MODAL -->
    <dialog id="deleteModal">
        <h2 style="color: #dc2626; margin-bottom: 10px;">Confirm Deletion</h2>
        <p style="margin-bottom: 20px; font-size: 14px; color: #555; line-height: 1.5;">
            Are you sure you want to permanently delete <strong id="deleteProductName" style="color: #333;"></strong>? This action cannot be undone.
        </p>
        <form method="POST" id="deleteForm">
            <input type="hidden" name="product_id" id="delete_product_id" value="">
            <div class="dialog-buttons">
                <button type="button" class="cancel-btn" onclick="closeDeleteModal()">Cancel</button>
                <button type="submit" name="delete_product" style="padding: 10px 20px; background: #dc2626; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold;">Yes, Delete</button>
            </div>
        </form>
    </dialog>

    <!-- CUSTOM CTRL+F FIND BAR -->
    <div id="customFindBar" style="display: none; position: fixed; top: 10px; right: 20px; background: white; padding: 10px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 9999; border: 1px solid #ddd;">
        <input type="text" id="findInput" placeholder="Find product..." style="padding: 8px; border: 1px solid #ccc; border-radius: 4px; outline: none; width: 200px;">
        <button id="closeFind" style="padding: 8px 12px; margin-left: 5px; background: #eee; border: none; border-radius: 4px; cursor: pointer;">X</button>
    </div>

    <script>
        function openProductModal(product = null) {
            const modal = document.getElementById('productModal');
            const title = document.getElementById('modalTitle');
            
            if (product) {
                title.textContent = 'Edit Product';
                document.getElementById('form_id').value = product.product_id;
                document.getElementById('form_name').value = product.name;
                document.getElementById('form_sku').value = product.sku;
                document.getElementById('form_price').value = product.unit_price;
                document.getElementById('form_reorder').value = product.reorder_level;
                document.getElementById('form_stock').value = product.stock_quantity;
            } else {
                title.textContent = 'Add Product';
                document.getElementById('form_id').value = '';
                document.getElementById('form_name').value = '';
                document.getElementById('form_sku').value = '';
                document.getElementById('form_price').value = '';
                document.getElementById('form_reorder').value = '';
                document.getElementById('form_stock').value = '';
            }
            modal.showModal();
        }

        function closeProductModal() {
            document.getElementById('productModal').close();
        }

        function openDeleteModal(id, name) {
            document.getElementById('delete_product_id').value = id;
            document.getElementById('deleteProductName').textContent = name;
            document.getElementById('deleteModal').showModal();
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').close();
        }

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

        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                e.preventDefault(); 
                const findBar = document.getElementById('customFindBar');
                findBar.style.display = 'flex';
                document.getElementById('findInput').focus();
            }
            if (e.key === 'Escape') {
                const findBar = document.getElementById('customFindBar');
                if (findBar.style.display === 'flex') {
                    findBar.style.display = 'none';
                    document.getElementById('findInput').value = '';
                    document.querySelectorAll('table tbody tr').forEach(row => row.style.display = '');
                    e.stopImmediatePropagation();
                }
            }
        });

        document.getElementById('closeFind').addEventListener('click', function() {
            document.getElementById('customFindBar').style.display = 'none';
            document.getElementById('findInput').value = '';
            document.querySelectorAll('table tbody tr').forEach(row => row.style.display = '');
        });

        document.getElementById('findInput').addEventListener('keyup', function() {
            const query = this.value.toLowerCase();
            const rows = document.querySelectorAll('table tbody tr');
            rows.forEach(row => {
                if (row.cells.length < 2) return; 
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    </script>

    <!-- KimFresh mobile burger menu. Include this just before </body> on every admin page. -->
    <script>
    /* KimFresh mobile burger menu.
       Builds the burger button, a mobile logo, a close button and the overlay,
       so every admin page only needs to include this one script. */
    (function () {
        var sidebar = document.querySelector('.sidebar');
        var header = document.querySelector('.top-header');
        if (!sidebar || !header) return;

        sidebar.id = sidebar.id || 'sidebar';

        // Burger button (first item in the top bar)
        var burger = document.createElement('button');
        burger.type = 'button';
        burger.className = 'menu-toggle';
        burger.setAttribute('aria-label', 'Open menu');
        burger.setAttribute('aria-controls', sidebar.id);
        burger.setAttribute('aria-expanded', 'false');
        burger.innerHTML = '<span></span><span></span><span></span>';
        header.insertBefore(burger, header.firstChild);

        // Logo in the top bar (copied from the sidebar, since the sidebar is hidden)
        var logo = sidebar.querySelector('.logo');
        if (logo) {
            var mobileLogo = logo.cloneNode(true);
            mobileLogo.className = 'mobile-logo';
            header.insertBefore(mobileLogo, burger.nextSibling);
        }

        // Close button inside the drawer
        var closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'sidebar-close';
        closeBtn.setAttribute('aria-label', 'Close menu');
        closeBtn.innerHTML = '&times;';
        sidebar.appendChild(closeBtn);

        // Dark overlay behind the drawer
        var overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);

        function setOpen(open) {
            sidebar.classList.toggle('open', open);
            document.body.classList.toggle('sidebar-open', open);
            burger.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        burger.addEventListener('click', function () {
            setOpen(!sidebar.classList.contains('open'));
        });
        closeBtn.addEventListener('click', function () { setOpen(false); });
        overlay.addEventListener('click', function () { setOpen(false); });
        sidebar.addEventListener('click', function (e) {
            if (e.target.closest('a')) setOpen(false);
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') setOpen(false);
        });
        // If the window grows past phone size, reset the drawer
        window.matchMedia('(min-width: 769px)').addEventListener('change', function (e) {
            if (e.matches) setOpen(false);
        });
    })();
    </script>
</body>
</html>
<?php
session_start();
require 'db.php';
require 'log_helper.php'; // Moved to top

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Handle Actions (Hide/Restore)
if (isset($_POST['toggle_status'])) {
    $product_id = $_POST['product_id'];
    $new_status = $_POST['new_status'];
    $stmt = $conn->prepare("UPDATE product SET status = ? WHERE product_id = ?");
    $stmt->bind_param("si", $new_status, $product_id);
    $stmt->execute();
    
    $action = ($new_status == 'inactive') ? "Hidden Product #" : "Restored Product #";
    logActivity($conn, $_SESSION['admin_id'], $action . $product_id);
    
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

// Fetch Data
$total_products = $conn->query("SELECT COUNT(*) AS c FROM product WHERE status = 'available'")->fetch_assoc()['c'];
$low_stock = $conn->query("SELECT COUNT(*) AS c FROM product WHERE status = 'available' AND stock_quantity <= reorder_level")->fetch_assoc()['c'];
$hidden = $conn->query("SELECT COUNT(*) AS c FROM product WHERE status = 'inactive'")->fetch_assoc()['c'];

$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'active'; 

$sql = "SELECT * FROM product WHERE 1=1";
if ($filter === 'active') { $sql .= " AND status = 'available'"; } 
elseif ($filter === 'low') { $sql .= " AND status = 'available' AND stock_quantity <= reorder_level"; } 

if (!empty($search)) {
    $sql .= " AND (name LIKE '%$search%' OR sku LIKE '%$search%' OR status LIKE '%$search%')";
}
$sql .= " ORDER BY product_id ASC";
$products = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KimFresh - Products</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <!-- FIXED CSS PATHS -->
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/products.css">
    <link rel="stylesheet" href="css/dashboard.css">
    
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
        
        .product-name { font-weight: 800; color: #333; font-size: 14px; margin-bottom: 3px; }
        .product-sku { color: #888; font-size: 12px; }
        .price { font-weight: 700; color: #333; }
        
        .status { padding: 4px 12px; font-size: 11px; font-weight: 700; border-radius: 20px; }
        .status.in-stock, .status.available { color: #15803d; background: #dcfce7; }
        .status.low-stock { color: #b45309; background: #fef3c7; }
        .status.hidden { color: #666; background: #eee; }

        .actions { display: flex; gap: 8px; }
        
        .btn-edit { padding: 7px 16px; font-size: 12px; font-weight: 700; color: #333; background: #fff; border: 1px solid #ddd; border-radius: 8px; cursor: pointer; text-decoration: none; }
        .btn-edit:hover { background: #f8f9fa; }
        
        .btn-hide { padding: 7px 16px; font-size: 12px; font-weight: 700; color: #C34200; background: #fff; border: 1px solid #C34200; border-radius: 8px; cursor: pointer; }
        .btn-hide:hover { background: #fdf0e6; }
        
        .btn-restore { padding: 7px 16px; font-size: 12px; font-weight: 700; color: #fff; background: #C34200; border: 1px solid #C34200; border-radius: 8px; cursor: pointer; }

        .filters { display: flex; gap: 12px; margin-top: 24px; margin-bottom: 24px; }
        .filters input { flex: 1; height: 44px; padding: 0 16px; border: 1px solid #ddd; border-radius: 10px; font-size: 13px; outline: none; }
        .filters select { width: 220px; height: 44px; padding: 0 16px; border: 1px solid #ddd; border-radius: 10px; font-size: 13px; outline: none; background: #fff; }
        .filters input:focus, .filters select:focus { border-color: #C34200; }

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

        .primary-btn { background: #C34200 !important; }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-brand">
                <img src="../logo.png" alt="KimFresh" class="logo">
                <p>Administration</p>
            </div>
            <nav class="menu">
                <a href="dashboard.php">Dashboard</a>
                <a href="orders.php">Orders</a>
                <a href="products.php" class="active">Products</a>
                <a href="drivers.php">Drivers</a>
                <a href="settings.php">Settings</a>
                <a href="logs.php">Activity Logs</a> 
            </nav>
        </aside>

        <div class="main-area">
            <header class="top-header">
                <div class="profile-menu">
                    <div class="user-profile">
                        <div class="avatar">A</div>
                        <div class="user-info">
                            <p class="user-name"><?php echo $_SESSION['admin_name']; ?></p>
                            <p class="user-role">Administrator</p>
                        </div>
                        <a href="logout.php" class="logout-btn" style="margin-left: 15px; text-decoration: none;">Logout</a>
                    </div>
                </div>
            </header>

            <main class="main-content">
                <p class="overview-label">Manage</p>

                <div class="page-head">
                    <div>
                        <h1>Products</h1>
                        <p>Add, hide and keep track of your kimchi stock.</p>
                    </div>
                    <button class="primary-btn" onclick="openProductModal()">+ Add Product</button>
                </div>

                <?php if(!empty($error_msg)): ?>
                    <div class="error-banner">
                        <?php echo $error_msg; ?>
                    </div>
                <?php endif; ?>

                <div class="stats">
                    <div class="stat"><p>Total products</p><h2><?php echo $total_products; ?></h2></div>
                    <div class="stat warn"><p>Low stock</p><h2><?php echo $low_stock; ?></h2></div>
                    <div class="stat dark"><p>Hidden</p><h2><?php echo $hidden; ?></h2></div>
                </div>

                <form method="GET" class="filters">
                    <input type="text" name="search" placeholder="Search by name, SKU, or status..." value="<?php echo htmlspecialchars($search); ?>">
                    <select name="filter" onchange="this.form.submit()">
                        <option value="active" <?php echo $filter == 'active' ? 'selected' : ''; ?>>Show Active</option>
                        <option value="low" <?php echo $filter == 'low' ? 'selected' : ''; ?>>Low Stock Only</option>
                        <option value="all" <?php echo $filter == 'all' ? 'selected' : ''; ?>>Show All (Including Hidden)</option>
                    </select>
                </form>

                <section class="table-section">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Stock</th>
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
                                        $isHidden = $row['status'] == 'inactive';
                                        
                                        echo "<tr>";
                                        echo "<td>
                                                <div class='product-name'>" . htmlspecialchars($row['name']) . "</div>
                                                <div class='product-sku'>" . htmlspecialchars($row['sku']) . "</div>
                                              </td>";
                                        echo "<td class='price'>₱" . number_format($row['unit_price'], 2) . "</td>";
                                        echo "<td>" . $row['stock_quantity'] . " <span class='status " . $stockClass . "'>" . $stockLabel . "</span></td>";
                                        echo "<td>" . $row['reorder_level'] . "</td>";
                                        echo "<td><span class='status " . ($isHidden ? 'hidden' : 'available') . "'>" . ($isHidden ? 'Hidden' : 'Available') . "</span></td>";
                                        
                                        echo "<td>
                                                <div class='actions'>
                                                    <button class='btn-edit' onclick='openProductModal(" . json_encode($row) . ")'>Edit</button>";
                                        
                                        if ($isHidden) {
                                            echo "<form method='POST' style='margin:0;'>
                                                    <input type='hidden' name='product_id' value='" . $row['product_id'] . "'>
                                                    <input type='hidden' name='new_status' value='available'>
                                                    <button type='submit' name='toggle_status' class='btn-restore'>Restore</button>
                                                  </form>";
                                        } else {
                                            echo "<form method='POST' style='margin:0;'>
                                                    <input type='hidden' name='product_id' value='" . $row['product_id'] . "'>
                                                    <input type='hidden' name='new_status' value='inactive'>
                                                    <button type='submit' name='toggle_status' class='btn-hide'>Hide</button>
                                                  </form>";
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
            <input type="text" name="name" id="form_name" required>
            
            <label>SKU</label>
            <input type="text" name="sku" id="form_sku">
            
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
    </script>
</body>
</html>
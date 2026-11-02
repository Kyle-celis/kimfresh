<?php
session_start();
require 'db.php';
require 'log_helper.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// --- HANDLE CSV EXPORT ---
if (isset($_GET['export_csv'])) {
    logActivity($conn, $_SESSION['admin_id'], "Exported Products to CSV");
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=KimFresh_Products_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['product_id', 'name', 'sku', 'unit_price', 'stock_quantity', 'reorder_level', 'status']);
    
    $query = $conn->query("SELECT product_id, name, sku, unit_price, stock_quantity, reorder_level, status FROM product ORDER BY product_id ASC");
    while ($row = $query->fetch_assoc()) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit();
}

// --- HANDLE CSV IMPORT ---
if (isset($_POST['import_csv']) && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    
    if ($_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        header("Location: products.php?error=upload");
        exit();
    }

    $handle = fopen($file, "r");
    if ($handle !== FALSE) {
        fgetcsv($handle, 1000, ","); // Skip header
        
        $success = 0;
        $updated = 0;
        
        $conn->begin_transaction();
        try {
            $changed_ids = []; // Store IDs for highlighting
            $max_res = $conn->query("SELECT MAX(product_id) as max_id FROM product")->fetch_assoc();
            $next_id = ($max_res['max_id'] ?? 0) + 1;

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (count($data) < 7) continue;
                
                $product_id = trim($data[0]);
                $name = trim($data[1]);
                $sku = trim($data[2]);
                $price = (float)$data[3];
                $stock = (int)$data[4];
                $reorder = (int)$data[5];
                $status = trim($data[6]);

                if (empty($sku) || strtoupper($sku) === 'KIM-NEW') {
                    $sku = 'KIM-' . str_pad($next_id, 3, '0', STR_PAD_LEFT);
                }

                if (empty($product_id)) {
                    $stmt = $conn->prepare("INSERT INTO product (name, sku, unit_price, stock_quantity, reorder_level, status) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssdiis", $name, $sku, $price, $stock, $reorder, $status);
                    $stmt->execute();
                    $changed_ids[] = $conn->insert_id;
                    $success++;
                    $next_id++; 
                } else {
                    $stmt = $conn->prepare("UPDATE product SET name=?, sku=?, unit_price=?, stock_quantity=?, reorder_level=?, status=? WHERE product_id=?");
                    $stmt->bind_param("ssdiisi", $name, $sku, $price, $stock, $reorder, $status, $product_id);
                    $stmt->execute();
                    $changed_ids[] = $product_id;
                    $updated++;
                }
            }
            $conn->commit();
            logActivity($conn, $_SESSION['admin_id'], "Imported CSV: $success added, $updated updated");
            
            // SAVE IDs TO SESSION FOR HIGHLIGHTING
            $_SESSION['highlight_products'] = $changed_ids; 
            
            header("Location: products.php?import_success=" . ($success + $updated));
            exit();
        } catch (Throwable $e) {
            $conn->rollback();
            logActivity($conn, $_SESSION['admin_id'], "CSV Import Failed: " . $e->getMessage());
            header("Location: products.php?error=import");
            exit();
        }
        fclose($handle);
    }
}
header("Location: products.php");
exit();
?>
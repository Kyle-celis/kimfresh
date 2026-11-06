<?php
// app/Controllers/CsvController.php — strict MVC Controller for CSV import/export
class CsvController extends Controller
{
    public function exportProducts()
    {
        $this->requireAuth();

        require_once __DIR__ . '/../Models/Product.php';
        $model = new Product($this->conn);

        logActivity($this->conn, $_SESSION['admin_id'], "Exported Products to CSV");

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=KimFresh_Products_' . date('Y-m-d') . '.csv');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['product_id', 'name', 'sku', 'unit_price', 'stock_quantity', 'reorder_level', 'status']);

        $rows = $model->getAllForExport();
        while ($row = $rows->fetch_assoc()) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit();
    }

    public function importProducts()
    {
        $this->requireAuth();

        if (!isset($_POST['import_csv']) || !isset($_FILES['csv_file'])) {
            $this->redirect('/php-web/public/products');
        }

        $file = $_FILES['csv_file']['tmp_name'];
        if ($_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $this->redirect('/php-web/public/products?error=upload');
        }

        $handle = fopen($file, 'r');
        if ($handle === false) {
            $this->redirect('/php-web/public/products?error=upload');
        }

        $headers = fgetcsv($handle, 1000, ",");
        $headers = array_map(fn($h) => strtolower(trim($h)), $headers);

        $success = 0;
        $this->conn->begin_transaction();
        try {
            $changed_ids = [];
            $max_res = $this->conn->query("SELECT MAX(product_id) AS max_id FROM product")->fetch_assoc();
            $next_id = ($max_res['max_id'] ?? 0) + 1;

            $stmt = $this->conn->prepare("
                INSERT INTO product (product_id, name, sku, unit_price, stock_quantity, reorder_level, status)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                name=VALUES(name), sku=VALUES(sku), unit_price=VALUES(unit_price),
                stock_quantity=VALUES(stock_quantity), reorder_level=VALUES(reorder_level), status=VALUES(status)
            ");

            while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                if (empty($data) || !isset($data[1])) continue;

                $row = array_combine($headers, array_slice($data, 0, count($headers)));
                if (!$row) continue;

                $product_id = !empty($row['product_id']) ? trim($row['product_id']) : '';
                $name       = $row['name'] ?? '';
                if (trim($name) === '') continue;

                $price   = isset($row['unit_price'])   ? (float)$row['unit_price']   : 0.00;
                $stock   = isset($row['stock_quantity'])? (int)$row['stock_quantity']: 0;
                $reorder = isset($row['reorder_level']) ? (int)$row['reorder_level'] : 5;
                $status  = !empty($row['status'])       ? trim($row['status'])       : 'available';

                $sku = $row['sku'] ?? '';
                if (trim($sku) === '' || strtoupper(trim($sku)) === 'KIM-NEW') {
                    $sku = 'KKD-' . str_pad($next_id, 3, '0', STR_PAD_LEFT);
                }

                $safe_id = $product_id === '' ? 0 : (int)$product_id;

                $stmt->bind_param("issdiss", $safe_id, $name, $sku, $price, $stock, $reorder, $status);
                $stmt->execute();

                $actual_id = $safe_id > 0 ? $safe_id : $this->conn->insert_id;
                $changed_ids[] = $actual_id;
                $success++;
                $next_id++;
            }

            $this->conn->commit();
            logActivity($this->conn, $_SESSION['admin_id'], "Imported CSV: $success processed");
            $_SESSION['highlight_products'] = $changed_ids;
            fclose($handle);

            $this->redirect('/php-web/public/products?import_success=' . $success);
        } catch (Throwable $e) {
            $this->conn->rollback();
            if (is_resource($handle)) fclose($handle);
            $this->redirect('/php-web/public/products?error=import&msg=' . urlencode($e->getMessage()));
        }
    }
}

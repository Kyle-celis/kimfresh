<?php
// app/Controllers/ProductController.php Ã¢â‚¬â€ strict MVC Controller
class ProductController extends Controller
{
    private $productModel;
    private $storeModel;

    private function boot()
    {
        require_once __DIR__ . '/../Models/Product.php';
        require_once __DIR__ . '/../Models/Store.php';
        $this->productModel = new Product($this->conn);
        $this->storeModel   = new Store($this->conn);
        
        // AUTO-EXPIRE LOCK: Mark any product past its expiry date as expired
        $this->conn->query("UPDATE product SET status = 'expired' WHERE expiry_date < CURDATE() AND status != 'expired'");
    }

    public function index()
    {
        $this->requireAuth();
        $this->boot();

        // --- Handle POST actions ---
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['toggle_manual']))  { $this->handleToggleManual(); return; }
            if (isset($_POST['reset_schedule'])) { $this->handleResetSchedule(); return; }
            if (isset($_POST['save_product']))   { $this->handleSave(); return; }
            if (isset($_POST['delete_product'])) { $this->handleDelete(); return; }
        }

        // --- Handle PDF export ---
        if (isset($_GET['export_pdf'])) { $this->handlePdfExport(); return; }

        // --- Store status ---
        $store = $this->storeModel->get();
        $is_store_open = $this->computeStoreOpen($store);

        // --- Stats ---
        $stats = $this->productModel->getStats();
        $total_products     = $stats['total'];
        $low_stock          = $stats['low_stock'];
        $available_products = $stats['available'];

        // --- Sorting & pagination ---
        $sort = $_GET['sort'] ?? 'id_asc';
        $sortMap = [
            'name_asc'   => "name ASC",         'name_desc'  => "name DESC",
            'price_asc'  => "unit_price ASC",   'price_desc' => "unit_price DESC",
            'stock_asc'  => "stock_quantity ASC", 'stock_desc'=> "stock_quantity DESC",
        ];
        $orderBy = $sortMap[$sort] ?? "product_id ASC";

        $limit  = 7;
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $offset = ($page - 1) * $limit;
        $total_rows = $this->productModel->countAll();
        $total_pages = (int)ceil($total_rows / $limit);
        $products = $this->productModel->getAll($orderBy, $limit, $offset);

        

        $this->view('products/index', [
            'store'             => $store,
            'is_store_open'     => $is_store_open,
            'total_products'    => $total_products,
            'low_stock'         => $low_stock,
            'available_products'=> $available_products,
            'products'          => $products,
            'sort'              => $sort,
            'page'              => $page,
            'total_pages'       => $total_pages,
            
        ]);
    }

    private function computeStoreOpen($store)
    {
        date_default_timezone_set('Asia/Manila');
        $current_time = date('H:i:s');
        if ($store['manual_override'] !== null) {
            return ($store['manual_override'] == 1);
        }
        if ($store['open_time'] <= $store['close_time']) {
            return ($current_time >= $store['open_time'] && $current_time <= $store['close_time']);
        }
        return ($current_time >= $store['open_time'] || $current_time <= $store['close_time']);
    }

    private function handleToggleManual()
    {
        $override = (int)$_POST['override_value'];
        $this->storeModel->setManualOverride($override);
        logActivity($this->conn, $_SESSION['admin_id'], $override ? "Manually Opened Store" : "Manually Closed Store");
        $this->redirect('/php-web/public/products');
    }

    private function handleResetSchedule()
    {
        $this->storeModel->resetOverride();
        logActivity($this->conn, $_SESSION['admin_id'], "Reset Store to Auto Schedule");
        $this->redirect('/php-web/public/products');
    }

    private function handleSave()
    {
        $product_id = $_POST['product_id'] ?? '';
        $name        = $_POST['name'];
        $description = trim($_POST['description'] ?? '');
        $sku         = $_POST['sku'] ?? '';
        $price       = $_POST['unit_price'];
        $stock       = $_POST['stock_quantity'];
        $reorder     = $_POST['reorder_level']; // <-- ADDED BACK
        $shelf_life  = (int)($_POST['shelf_life_days'] ?? 30); // <-- ADDED BACK

        // Prevent adding or editing while the store is open
        $store = $this->storeModel->get();
        if ($this->computeStoreOpen($store)) {
            $this->flash('error', 'You cannot modify products while the store is open. Please deactivate the store first.');
            $this->redirect('/php-web/public/products');
        }

        if ($price < 0 || $stock < 0 || $reorder < 0) {
            $this->flash('error', "Error: Price, Stock, and Reorder level cannot be negative!");
            $this->redirect('/php-web/public/products');
        }

        if (empty($product_id)) {
            $sku = $this->productModel->nextSku();
            $newId = $this->productModel->create($name, $description, $sku, $price, $stock, $reorder, $shelf_life);
            logActivity($this->conn, $_SESSION['admin_id'], "Added new product: " . $name);
            $this->flash('success', "Product successfully added.");
            $_SESSION['highlight_products'] = [$newId];
        } else {
            $this->productModel->update($product_id, $name, $description, $sku, $price, $stock, $reorder, $shelf_life);
            logActivity($this->conn, $_SESSION['admin_id'], "Updated product: " . $name);
            $this->flash('success', "Product successfully updated.");
            $_SESSION['highlight_products'] = [$product_id];
        }
        $this->redirect('/php-web/public/products');
    }

    private function handleDelete()
    {
        $product_id = (int)$_POST['product_id'];
        $product = $this->productModel->findById($product_id);
        $this->productModel->delete($product_id);
        logActivity($this->conn, $_SESSION['admin_id'], "Permanently Deleted Product: " . ($product['name'] ?? 'Unknown'));
        $this->flash('success', "Product successfully deleted.");
        $this->redirect('/php-web/public/products');
    }

            private function handlePdfExport()
    {
        logActivity($this->conn, $_SESSION['admin_id'], "Exported Products to PDF");

        // Clear buffers
        if (ob_get_length()) ob_end_clean();
        ob_start();

        require_once __DIR__ . '/../../lib/fpdf/fpdf.php';
        require_once __DIR__ . '/../helpers/KimFreshPdf.php';

        $pdf = new KimFreshPdf();
        $pdf->title = 'KimFresh Product Inventory';
        $pdf->AddPage();
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(0, 0, 0);

        // Header
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor(195, 66, 0);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(60, 8, 'Product Name', 1, 0, 'C', true);
        $pdf->Cell(30, 8, 'SKU',          1, 0, 'C', true);
        $pdf->Cell(30, 8, 'Price',        1, 0, 'C', true);
        $pdf->Cell(20, 8, 'Stock',        1, 0, 'C', true);
        $pdf->Cell(25, 8, 'Reorder',      1, 0, 'C', true);
        $pdf->Cell(25, 8, 'Status',       1, 1, 'C', true);

        // Data
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(0, 0, 0);
        $rows = $this->productModel->getAllForPdf();
        
        while ($row = $rows->fetch_assoc()) {
            $pdf->Cell(60, 8, substr(htmlspecialchars($row['name'] ?? ''), 0, 35), 1, 0, 'L');
            $pdf->Cell(30, 8, htmlspecialchars($row['sku'] ?? ''),  1, 0, 'C');
            $pdf->Cell(30, 8, 'PHP ' . number_format($row['unit_price'] ?? 0, 2), 1, 0, 'R');
            $pdf->Cell(20, 8, $row['stock_quantity'] ?? 0, 1, 0, 'C');
            $pdf->Cell(25, 8, $row['reorder_level'] ?? 0, 1, 0, 'C');
            $pdf->Cell(25, 8, strtoupper($row['status'] ?? 'available'), 1, 1, 'C');
        }

        // Capture output as a string to prevent corruption
        $pdfData = $pdf->Output('S', 'KimFresh_Products.pdf');
        ob_end_clean();

        // Send headers and raw data
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="KimFresh_Products.pdf"');
        header('Content-Length: ' . strlen($pdfData));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        
        echo $pdfData;
        exit;
    }
}
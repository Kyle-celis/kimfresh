<?php
// app/Models/Product.php — strict MVC Model
class Product extends Model
{
    public function getAll($orderBy, $limit, $offset)
    {
        return $this->conn->query("SELECT * FROM product ORDER BY $orderBy LIMIT $limit OFFSET $offset");
    }

    public function countAll()
    {
        return $this->conn->query("SELECT COUNT(*) AS c FROM product")->fetch_assoc()['c'];
    }

    public function getStats()
    {
        return [
            'total'     => $this->conn->query("SELECT COUNT(*) AS c FROM product")->fetch_assoc()['c'],
            'low_stock' => $this->conn->query("SELECT COUNT(*) AS c FROM product WHERE stock_quantity <= reorder_level")->fetch_assoc()['c'],
            'available' => $this->conn->query("SELECT COUNT(*) AS c FROM product WHERE status = 'available' AND stock_quantity > 0")->fetch_assoc()['c'],
        ];
    }

    public function getAllForPdf()
    {
        return $this->conn->query("SELECT product_id, name, sku, unit_price, stock_quantity, reorder_level, status FROM product ORDER BY product_id ASC");
    }

    public function findById($id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM product WHERE product_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function create($name, $description, $sku, $price, $stock, $reorder, $shelf_life = 30)
    {
        $stmt = $this->conn->prepare("INSERT INTO product (name, description, sku, unit_price, stock_quantity, reorder_level, shelf_life_days, 
        expiry_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, DATE_ADD(CURDATE(), INTERVAL ? DAY), 'available')");
        $stmt->bind_param("sssdiisii", $name, $description, $sku, $price, $stock, $reorder, $shelf_life, $shelf_life);
        $stmt->execute();
        return $this->conn->insert_id;
    }

    public function update($id, $name, $description, $sku, $price, $stock, $reorder, $shelf_life = 30)
    {
        $stmt = $this->conn->prepare("UPDATE product SET name=?, description=?, sku=?, unit_price=?, stock_quantity=?, reorder_level=?, 
        shelf_life_days=?, expiry_date=DATE_ADD(CURDATE(), INTERVAL ? DAY) WHERE product_id=?");
        $stmt->bind_param("sssdiisii", $name, $description, $sku, $price, $stock, $reorder, $shelf_life, $shelf_life, $id);
        $stmt->execute();
        return $stmt->affected_rows;
    }

    public function delete($id)
    {
        $stmt = $this->conn->prepare("DELETE FROM order_item WHERE product_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        $stmt = $this->conn->prepare("DELETE FROM product WHERE product_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->affected_rows;
    }

    public function nextSku()
    {
        $max = $this->conn->query("SELECT MAX(product_id) AS max_id FROM product")->fetch_assoc();
        $next = ($max['max_id'] ?? 0) + 1;
        return 'KKD-' . str_pad($next, 3, '0', STR_PAD_LEFT);
    }

        public function getAllForExport()
    {
        return $this->conn->query("SELECT product_id, name, description, sku, unit_price, stock_quantity, reorder_level, status FROM product ORDER BY product_id ASC");
    }
}

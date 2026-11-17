<?php
// app/Controllers/DriverController.php
class DriverController extends Controller
{
    public function index()
    {
        $this->requireAuth();

        $filter = $_GET['filter'] ?? 'all';

        // Base query to get drivers
        $query = "SELECT d.*, 
                         (SELECT COUNT(*) FROM delivery 
                          WHERE driver_id = d.driver_id 
                          AND delivery_status IN ('assigned', 'picked_up', 'in_transit')) as active_orders,
                         (SELECT delivery_status FROM delivery 
                          WHERE driver_id = d.driver_id 
                          AND delivery_status IN ('assigned', 'picked_up', 'in_transit') 
                          ORDER BY delivery_id DESC LIMIT 1) as active_delivery_status
                  FROM driver d";

        // Apply filters
        $params = [];
        $types = "";
        if ($filter === 'available') {
            $query .= " WHERE d.status = 'available' AND d.account_status != 'hidden'";
        } elseif ($filter === 'offline') {
            $query .= " WHERE d.status = 'offline' AND d.account_status != 'hidden'";
        } elseif ($filter === 'inactive') {
            $query .= " WHERE d.account_status = 'hidden'";
        } elseif ($filter === 'assigned' || $filter === 'on_delivery') {
            $query .= " WHERE d.status = 'on_delivery' AND d.account_status != 'hidden'";
        } else {
            $query .= " WHERE d.account_status != 'hidden'";
        }

        $query .= " ORDER BY d.name ASC";
        $drivers = $this->conn->query($query);

        // Fetch histories (unchanged)
        $histories = [];
        $histQuery = "SELECT d.driver_id, o.order_id, r.name as retailer_name, o.total_amount, o.order_status, d.delivery_status, d.delivery_date as assigned_at
                      FROM delivery d
                      JOIN `order` o ON d.order_id = o.order_id
                      JOIN retailer r ON o.retailer_id = r.retailer_id
                      WHERE d.delivery_status IN ('delivered', 'rejected')
                      ORDER BY d.delivery_id DESC";
        $histResult = $this->conn->query($histQuery);
        if ($histResult) {
            while ($row = $histResult->fetch_assoc()) {
                $histories[$row['driver_id']][] = $row;
            }
        }

        // Stats
        $total_drivers = (int)$this->conn->query("SELECT COUNT(*) as c FROM driver WHERE account_status != 'hidden'")->fetch_assoc()['c'];
        $available_drivers = (int)$this->conn->query("SELECT COUNT(*) as c FROM driver WHERE status = 'available' AND account_status != 'hidden'")->fetch_assoc()['c'];
        $on_delivery = (int)$this->conn->query("SELECT COUNT(*) as c FROM driver WHERE status = 'on_delivery' AND account_status != 'hidden'")->fetch_assoc()['c'];
        
        // Unassigned orders for the dropdown
        $unassigned = $this->conn->query("SELECT o.order_id, r.name as retailer_name 
                                          FROM `order` o 
                                          JOIN retailer r ON o.retailer_id = r.retailer_id 
                                          WHERE o.order_status = 'approved' 
                                          AND o.order_id NOT IN (SELECT order_id FROM delivery)
                                          ORDER BY o.order_id ASC");

        $this->view('drivers/index', [
            'drivers' => $drivers,
            'histories' => $histories,
            'unassigned' => $unassigned,
            'filter' => $filter,
            'total_drivers' => $total_drivers,
            'available_drivers' => $available_drivers,
            'on_delivery' => $on_delivery
        ]);
    }

    // ... (keep your existing toggleStatus, assignOrder, export methods below)
}
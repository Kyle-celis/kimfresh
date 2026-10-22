<?php
session_start();
require 'db.php';
require 'log_helper.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// --- SECURITY CHECK: ONLY SUPERADMIN CAN ACCESS ---
if ($_SESSION['admin_id'] != 1) {
    die("<h2 style='text-align:center; margin-top:50px; color:red;'>Access Denied. You are not the Superadmin.</h2>");
}

$error_msg = "";

// --- HANDLE DEACTIVATE/ACTIVATE ---
if (isset($_POST['toggle_status'])) {
    $target_admin = $_POST['target_admin'];
    $new_status = $_POST['new_status']; 
    
    if ($target_admin == $_SESSION['admin_id']) {
        $error_msg = "You cannot deactivate your own account!";
    } else {
        $stmt = $conn->prepare("UPDATE admin SET status = ? WHERE admin_id = ?");
        $stmt->bind_param("si", $new_status, $target_admin);
        $stmt->execute();
        
        $action = ($new_status == 'inactive') ? "Deactivated Admin #" : "Activated Admin #";
        logActivity($conn, $_SESSION['admin_id'], $action . $target_admin);
        
        header("Location: superadmin.php");
        exit();
    }
}

// --- HANDLE EDIT ---
if (isset($_POST['save_admin'])) {
    $target_admin = $_POST['admin_id'];
    $new_name = $_POST['name'];
    $new_email = $_POST['email'];
    
    $stmt = $conn->prepare("UPDATE admin SET name = ?, email = ? WHERE admin_id = ?");
    $stmt->bind_param("ssi", $new_name, $new_email, $target_admin);
    $stmt->execute();
    
    logActivity($conn, $_SESSION['admin_id'], "Edited Admin #" . $target_admin);
    header("Location: superadmin.php");
    exit();
}

// Fetch all admins
$admins = $conn->query("SELECT * FROM admin ORDER BY admin_id ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Superadmin - Manage Admins</title>
    <link rel="stylesheet" href="../theme.css">
    <style>
        :root { --primary: #C34200; --primary-dark: #9a3400; }
        
        body { font-family: 'Nunito Sans', Arial, sans-serif; background: #F5F6FA; margin: 0; padding: 0; }
        
        /* Top Header */
        .top-bar { background: white; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .top-bar h2 { margin: 0; font-size: 18px; color: #333; }
        .top-bar .user-info { display: flex; align-items: center; gap: 15px; }
        .top-bar .user-name { font-weight: 700; font-size: 14px; color: #333; }
        .logout-btn { background: #C34200; color: white; text-decoration: none; padding: 8px 16px; border-radius: 8px; font-weight: bold; font-size: 13px; }

        /* Main Content */
        .container { max-width: 1000px; margin: 40px auto; padding: 0 20px; }
        .page-head { margin-bottom: 20px; }
        .page-head h1 { margin: 0; font-size: 28px; color: #333; }
        .page-head p { margin: 5px 0 0 0; color: #777; font-size: 14px; }
        
        .table-section { background: white; border-radius: 16px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; font-size: 13px; color: #666; border-bottom: 1px solid #eee; font-weight: 700; background: #f8f9fa; }
        td { padding: 15px; border-bottom: 1px solid #f5f5f5; font-size: 13px; vertical-align: middle; }
        
        .status { padding: 4px 12px; font-size: 11px; font-weight: 700; border-radius: 20px; }
        .status.active { color: #15803d; background: #dcfce7; }
        .status.inactive { color: #666; background: #eee; }
        .status.superadmin { color: #800020; background: #fdf2f4; }

        .actions { display: flex; gap: 8px; align-items: center; }
        .btn-edit { padding: 7px 16px; font-size: 12px; font-weight: 700; color: #333; background: #fff; border: 1px solid #ddd; border-radius: 8px; cursor: pointer; }
        .btn-hide { padding: 7px 16px; font-size: 12px; font-weight: 700; color: #C34200; background: #fff; border: 1px solid #C34200; border-radius: 8px; cursor: pointer; }
        .btn-restore { padding: 7px 16px; font-size: 12px; font-weight: 700; color: #fff; background: #C34200; border: 1px solid #C34200; border-radius: 8px; cursor: pointer; }
        
        /* Activity Log Dropdown */
        .log-btn { padding: 7px 16px; font-size: 12px; font-weight: 700; color: #333; background: #f8f9fa; border: 1px solid #ddd; border-radius: 8px; cursor: pointer; }
        .admin-log-row td { background: #fdfaf8; padding: 0; }
        .log-container { padding: 15px 20px; border-left: 3px solid #C34200; margin: 10px 0; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .log-container h4 { margin: 0 0 10px 0; font-size: 13px; color: #C34200; }
        .log-container ul { list-style: none; padding: 0; margin: 0; font-size: 12px; }
        .log-container li { padding: 6px 0; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; }
        .log-container li:last-child { border-bottom: none; }

        .error-banner { background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; border: 1px solid #f87171; }

        dialog { width: 90%; max-width: 400px; margin: auto; padding: 28px; border: none; border-radius: 18px; box-shadow: 0 20px 60px rgba(0,0,0,0.35); }
        dialog::backdrop { background: rgba(0,0,0,0.55); }
        dialog label { display: block; margin-bottom: 5px; font-size: 12px; font-weight: 700; color: #555;}
        dialog input { width: 100%; height: 40px; margin-bottom: 15px; padding: 0 14px; font-size: 13px; border: 1px solid #ddd; border-radius: 8px; outline: none; }
        dialog .dialog-buttons { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
        dialog .cancel-btn { padding: 10px 20px; background: #eee; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; }
        dialog .primary-btn { padding: 10px 20px; background: #C34200; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; }
    </style>
</head>
<body>

    <!-- TOP HEADER -->
    <div class="top-bar">
        <div style="display: flex; align-items: center; gap: 15px;">
            <img src="../logo.png" alt="KimFresh" style="height: 35px;">
            <h2>Superadmin Portal</h2>
        </div>
        <div class="user-info">
            <span class="user-name"><?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="container">
        <div class="page-head">
            <h1>Manage Admins</h1>
            <p>View, edit, deactivate, and audit other admin accounts.</p>
        </div>

        <?php if(!empty($error_msg)): ?>
            <div class="error-banner"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <section class="table-section">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $admins->fetch_assoc()): 
                        $isSuper = ($row['admin_id'] == 1);
                        $isActive = !isset($row['status']) || $row['status'] == 'active'; 
                    ?>
                        <tr>
                            <td><?php echo $row['admin_id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td>
                                <?php if($isSuper): ?>
                                    <span class="status superadmin">Superadmin</span>
                                <?php elseif($isActive): ?>
                                    <span class="status active">Active</span>
                                <?php else: ?>
                                    <span class="status inactive">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="actions">
                                    <button class="btn-edit" onclick='openAdminModal(<?php echo json_encode($row); ?>)'>Edit</button>
                                    
                                    <?php if(!$isSuper): ?>
                                        <?php if($isActive): ?>
                                            <form method='POST' style='margin:0;'>
                                                <input type='hidden' name='target_admin' value='<?php echo $row['admin_id']; ?>'>
                                                <input type='hidden' name='new_status' value='inactive'>
                                                <button type='submit' name='toggle_status' class='btn-hide'>Deactivate</button>
                                            </form>
                                        <?php else: ?>
                                            <form method='POST' style='margin:0;'>
                                                <input type='hidden' name='target_admin' value='<?php echo $row['admin_id']; ?>'>
                                                <input type='hidden' name='new_status' value='active'>
                                                <button type='submit' name='toggle_status' class='btn-restore'>Activate</button>
                                            </form>
                                        <?php endif; ?>

                                        <button class="log-btn" onclick="toggleLogs(<?php echo $row['admin_id']; ?>)">Activity Logs</button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>

                        <tr id="log-row-<?php echo $row['admin_id']; ?>" class="admin-log-row" style="display: none;">
                            <td colspan="5">
                                <div class="log-container">
                                    <h4>Recent Activity for <?php echo htmlspecialchars($row['name']); ?></h4>
                                    <?php 
                                        $target_id = $row['admin_id'];
                                        $logs = $conn->query("SELECT * FROM activity_logs WHERE admin_id = $target_id ORDER BY timestamp DESC LIMIT 5");
                                        if ($logs->num_rows > 0) {
                                            echo "<ul>";
                                            while($log = $logs->fetch_assoc()) {
                                                echo "<li><span>" . htmlspecialchars($log['action']) . "</span> <span style='color:#888;'>" . date('M d, g:i A', strtotime($log['timestamp'])) . "</span></li>";
                                            }
                                            echo "</ul>";
                                        } else {
                                            echo "<p style='font-size:12px; color:#777;'>No activity logs recorded for this admin yet.</p>";
                                        }
                                    ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </section>
    </div>

    <!-- EDIT ADMIN MODAL -->
    <dialog id="adminModal">
        <h2 style="margin-bottom: 15px;">Edit Admin</h2>
        <form method="POST">
            <input type="hidden" name="admin_id" id="form_admin_id">
            
            <label>Full Name</label>
            <input type="text" name="name" id="form_name" required>
            
            <label>Email</label>
            <input type="email" name="email" id="form_email" required>
            
            <div class="dialog-buttons">
                <button type="button" class="cancel-btn" onclick="closeAdminModal()">Cancel</button>
                <button type="submit" name="save_admin" class="primary-btn">Save Changes</button>
            </div>
        </form>
    </dialog>

    <script>
        function openAdminModal(admin) {
            document.getElementById('form_admin_id').value = admin.admin_id;
            document.getElementById('form_name').value = admin.name;
            document.getElementById('form_email').value = admin.email;
            document.getElementById('adminModal').showModal();
        }

        function closeAdminModal() {
            document.getElementById('adminModal').close();
        }

        function toggleLogs(adminId) {
            const row = document.getElementById('log-row-' + adminId);
            if (row.style.display === 'none') {
                row.style.display = 'table-row';
            } else {
                row.style.display = 'none';
            }
        }
    </script>
</body>
</html>
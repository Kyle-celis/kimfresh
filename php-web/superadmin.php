<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
require 'db.php';
require 'log_helper.php';
date_default_timezone_set('Asia/Manila');

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// STRICT SUPERADMIN CHECK
if ($_SESSION['admin_id'] != 1) {
    header("Location: dashboard.php");
    exit();
}

$error_msg = "";
$success_msg = "";

// --- HANDLE CREATE ADMIN ---
if (isset($_POST['create_admin'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $new_email = trim($_POST['new_email']);
    $new_password = $_POST['new_password'];
    
    $full_name = $first_name . ' ' . $last_name;

    if (empty($first_name) || empty($last_name) || empty($new_email) || empty($new_password)) {
        $error_msg = "All fields are required.";
    } else {
        $check = $conn->prepare("SELECT admin_id FROM admin WHERE email = ?");
        $check->bind_param("s", $new_email);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            $error_msg = "Email is already registered.";
        } else {
            $hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO admin (name, email, password_hash) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $full_name, $new_email, $hash);
            
            if ($stmt->execute()) {
                logActivity($conn, $_SESSION['admin_id'], "Created new Admin: " . $new_email);
                $success_msg = "Admin account successfully created!";
            } else {
                $error_msg = "Failed to create account.";
            }
        }
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

$admins = $conn->query("SELECT * FROM admin ORDER BY admin_id ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KimFresh - Manage Admins</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #C34200; --primary-dark: #9a3400; }
        
        body { font-family: 'Nunito Sans', Arial, sans-serif; background: #F5F6FA; margin: 0; padding: 0; }
        
        .container { max-width: 1000px; margin: 40px auto; padding: 0 20px; }
        
        .page-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .page-head h1 { margin: 0; font-size: 28px; color: #333; }
        .page-head p { margin: 5px 0 0 0; color: #777; font-size: 14px; }
        
        .nav-actions { display: flex; gap: 10px; }
        .btn-add { background: #C34200; color: white; padding: 10px 20px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.2s; }
        .btn-add:hover { background: #9a3400; }
        .btn-logout { background: #f0f0f0; color: #333; text-decoration: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; font-size: 14px; }
        .btn-logout:hover { background: #e0e0e0; }

        .table-section { background: white; border-radius: 16px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; font-size: 13px; color: #666; border-bottom: 1px solid #eee; font-weight: 700; background: #f8f9fa; }
        td { padding: 15px; border-bottom: 1px solid #f5f5f5; font-size: 13px; vertical-align: middle; }
        
        .status { padding: 4px 12px; font-size: 11px; font-weight: 700; border-radius: 20px; }
        .status.superadmin { color: #800020; background: #fdf2f4; }
        .status.active { color: #15803d; background: #dcfce7; }

        .actions { display: flex; gap: 8px; align-items: center; }
        .btn-edit { padding: 7px 16px; font-size: 12px; font-weight: 700; color: #333; background: #fff; border: 1px solid #ddd; border-radius: 8px; cursor: pointer; }
        
        .log-btn { padding: 7px 16px; font-size: 12px; font-weight: 700; color: #333; background: #f8f9fa; border: 1px solid #ddd; border-radius: 8px; cursor: pointer; }
        .admin-log-row td { background: #fdfaf8; padding: 0; }
        .log-container { padding: 15px 20px; border-left: 3px solid #C34200; margin: 10px 0; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .log-container h4 { margin: 0 0 10px 0; font-size: 13px; color: #C34200; }
        .log-container ul { list-style: none; padding: 0; margin: 0; font-size: 12px; }
        .log-container li { padding: 6px 0; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; }
        .log-container li:last-child { border-bottom: none; }

        .alert { padding: 12px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; }
        .alert.error { background: #fee2e2; color: #991b1b; border: 1px solid #f87171; }
        .alert.success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }

        dialog { width: 90%; max-width: 400px; margin: auto; padding: 28px; border: none; border-radius: 18px; box-shadow: 0 20px 60px rgba(0,0,0,0.35); }
        dialog::backdrop { background: rgba(0,0,0,0.55); }
        dialog label { display: block; margin-bottom: 5px; font-size: 12px; font-weight: 700; color: #555;}
        dialog input { width: 100%; height: 40px; margin-bottom: 15px; padding: 0 14px; font-size: 13px; border: 1px solid #ddd; border-radius: 8px; outline: none; box-sizing: border-box; }
        dialog .name-row { display: flex; gap: 10px; }
        dialog .name-row input { flex: 1; }
        dialog .dialog-buttons { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
        dialog .cancel-btn { padding: 10px 20px; background: #eee; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; }
        dialog .primary-btn { padding: 10px 20px; background: #C34200; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; }
    </style>
</head>
<body>

    <div class="container">
        <div class="page-head">
            <div>
                <h1>Manage Admins</h1>
                <p>View, edit, deactivate, and audit other admin accounts.</p>
            </div>
            <div class="nav-actions">
                <button class="btn-add" onclick="openCreateModal()">+ Add Admin</button>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
        </div>

        <?php if(!empty($error_msg)): ?>
            <div class="alert error"><?php echo $error_msg; ?></div>
        <?php endif; ?>
        <?php if(!empty($success_msg)): ?>
            <div class="alert success"><?php echo $success_msg; ?></div>
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
                    ?>
                        <tr>
                            <td><?php echo $row['admin_id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td>
                                <?php if($isSuper): ?>
                                    <span class="status superadmin">Superadmin</span>
                                <?php else: ?>
                                    <span class="status active">Active</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="actions">
                                    <button class="btn-edit" onclick='openAdminModal(<?php echo json_encode($row); ?>)'>Edit</button>
                                    
                                    <?php if(!$isSuper): ?>
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
                                        if ($logs && $logs->num_rows > 0) {
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

    <!-- CREATE ADMIN MODAL -->
    <dialog id="createModal">
        <h2 style="margin-bottom: 15px;">Add New Admin</h2>
        <form method="POST">
            <label>Full Name</label>
            <div class="name-row">
                <input type="text" name="first_name" placeholder="First Name" required>
                <input type="text" name="last_name" placeholder="Last Name" required>
            </div>
            
            <label>Email</label>
            <input type="email" name="new_email" placeholder="admin@kimfresh.com" required>
            
            <label>Password</label>
            <input type="password" name="new_password" placeholder="Create a strong password" required>
            
            <div class="dialog-buttons">
                <button type="button" class="cancel-btn" onclick="closeCreateModal()">Cancel</button>
                <button type="submit" name="create_admin" class="primary-btn">Create Admin</button>
            </div>
        </form>
    </dialog>

    <!-- EDIT ADMIN MODAL -->
    <dialog id="adminModal">
        <h2 style="margin-bottom: 15px;">Edit Admin</h2>
        <form method="POST">
            <input type="hidden" name="admin_id" id="form_admin_id">
            
            <label>Full Name</label>
            <input type="text" name="name" id="form_name" maxlength="50" required>
            
            <label>Email</label>
            <input type="email" name="email" id="form_email" maxlength="50" required>
            
            <div class="dialog-buttons">
                <button type="button" class="cancel-btn" onclick="closeAdminModal()">Cancel</button>
                <button type="submit" name="save_admin" class="primary-btn">Save Changes</button>
            </div>
        </form>
    </dialog>

    <script>
        function openCreateModal() {
            document.getElementById('createModal').showModal();
        }
        function closeCreateModal() {
            document.getElementById('createModal').close();
        }

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
    <script>
        window.addEventListener('pageshow', function (event) {
            if (event.persisted || (typeof window.performance != "undefined" && window.performance.navigation.type === 2)) {
                window.location.reload();
            }
        });
    </script>
</body>
</html>
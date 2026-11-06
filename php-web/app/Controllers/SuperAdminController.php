<?php
// app/Controllers/SuperAdminController.php — strict MVC Controller
class SuperAdminController extends Controller
{
    private $model;

    private function boot()
    {
        require_once __DIR__ . '/../Models/SuperAdmin.php';
        $this->model = new SuperAdmin($this->conn);
    }

    public function index()
    {
        $this->requireAuth();
        $this->boot();

        // Superadmin privilege check
        if (!$this->model->isSuperAdmin((int)$_SESSION['admin_id'])) {
            http_response_code(403);
            echo "<div style='text-align:center;margin-top:50px;font-family:sans-serif;'>
                    <h2 style='color:#dc2626;'>Access Denied</h2>
                    <p>You do not have Superadmin privileges.</p>
                    <a href='/php-web/public/dashboard' style='color:#C34200;font-weight:bold;'>Back to Dashboard</a>
                  </div>";
            exit();
        }

        // --- POST handlers ---
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['create_admin']))  { $this->handleCreate(); return; }
            if (isset($_POST['toggle_status'])) { $this->handleToggleStatus(); return; }
            if (isset($_POST['save_admin']))    { $this->handleSave(); return; }
        }

        $error_msg   = $_SESSION['superadmin_error'] ?? '';
        $success_msg = $_SESSION['superadmin_success'] ?? '';
        unset($_SESSION['superadmin_error'], $_SESSION['superadmin_success']);

        $admins = $this->model->getAll();

        // Pre-fetch activity logs for each admin (used by the activity modal)
        $logsByAdmin = [];
        $admins->data_seek(0);
        while ($row = $admins->fetch_assoc()) {
            $logsByAdmin[(int)$row['admin_id']] = $this->model->getActivityLogs((int)$row['admin_id'], 5);
        }
        $admins->data_seek(0);

        $this->view('superadmin/index', [
            'admins'      => $admins,
            'logsByAdmin' => $logsByAdmin,
            'error_msg'   => $error_msg,
            'success_msg' => $success_msg,
        ]);
    }

    private function handleCreate()
    {
        $first_name  = trim($_POST['first_name'] ?? '');
        $last_name   = trim($_POST['last_name'] ?? '');
        $new_email   = trim($_POST['new_email'] ?? '');
        $new_password = $_POST['new_password'] ?? '';
        $full_name   = trim($first_name . ' ' . $last_name);

        if ($first_name === '' || $last_name === '' || $new_email === '' || $new_password === '') {
            $_SESSION['superadmin_error'] = "All fields are required.";
            $this->redirect('/php-web/public/superadmin');
        }

        if ($this->model->emailExists($new_email)) {
            $_SESSION['superadmin_error'] = "Email is already registered.";
            $this->redirect('/php-web/public/superadmin');
        }

        $hash = password_hash($new_password, PASSWORD_DEFAULT);
        $this->model->create($full_name, $new_email, $hash);
        logActivity($this->conn, $_SESSION['admin_id'], "Created new Admin: " . $new_email);

        $_SESSION['superadmin_success'] = "Admin account successfully created!";
        $this->redirect('/php-web/public/superadmin');
    }

    private function handleToggleStatus()
    {
        $target_admin = (int)$_POST['target_admin'];
        $new_status   = $_POST['new_status'];

        if ($target_admin === (int)$_SESSION['admin_id']) {
            $_SESSION['superadmin_error'] = "You cannot deactivate your own account!";
            $this->redirect('/php-web/public/superadmin');
        }

        $this->model->setStatus($target_admin, $new_status);

        $action = ($new_status === 'inactive') ? "Deactivated Admin #" : "Activated Admin #";
        logActivity($this->conn, $_SESSION['admin_id'], $action . $target_admin);

        $this->redirect('/php-web/public/superadmin');
    }

    private function handleSave()
    {
        $target_admin = (int)$_POST['admin_id'];
        $new_name     = trim($_POST['name'] ?? '');
        $new_email    = trim($_POST['email'] ?? '');

        if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['superadmin_error'] = "Please enter a valid email address.";
            $this->redirect('/php-web/public/superadmin');
        }

        // Check email uniqueness (excluding this admin)
        $dupe = $this->conn->prepare("SELECT admin_id FROM admin WHERE email = ? AND admin_id != ?");
        $dupe->bind_param("si", $new_email, $target_admin);
        $dupe->execute();
        if ($dupe->get_result()->num_rows > 0) {
            $_SESSION['superadmin_error'] = "Email already in use by another admin.";
            $this->redirect('/php-web/public/superadmin');
        }

        $this->model->update($target_admin, $new_name, $new_email);
        logActivity($this->conn, $_SESSION['admin_id'], "Edited Admin #$target_admin");

        $_SESSION['superadmin_success'] = "Admin #$target_admin updated.";
        $this->redirect('/php-web/public/superadmin');
    }
}


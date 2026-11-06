<?php
// app/Controllers/SettingsController.php — strict MVC Controller
class SettingsController extends Controller
{
    public function index()
    {
        $this->requireAuth();

        require_once __DIR__ . '/../Models/Settings.php';
        $model = new Settings($this->conn);

        $admin_id = $_SESSION['admin_id'];
        $msg = '';
        $msg_type = '';

        // --- POST handlers ---
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['update_profile'])) {
                $name = $_POST['name'] ?? '';
                $new_password = $_POST['password'] ?? '';
                $model->updateProfile($admin_id, $name, $new_password);
                $_SESSION['admin_name'] = $name;
                logActivity($this->conn, $admin_id, "Updated admin settings");
                $msg = "Profile updated successfully!";
                $msg_type = "success";
            }
            if (isset($_POST['update_schedule'])) {
                $open_time  = $_POST['open_time'] ?? '';
                $close_time = $_POST['close_time'] ?? '';
                $model->updateSchedule($open_time, $close_time);
                logActivity($this->conn, $admin_id, "Updated Store Schedule to $open_time - $close_time");
                $msg = "Store schedule updated successfully!";
                $msg_type = "success";
            }
        }

        // --- Fetch data ---
        $admin = $model->getAdmin($admin_id);
        $store = $model->getStore();

        $this->view('settings/index', [
            'admin'    => $admin,
            'store'    => $store,
            'msg'      => $msg,
            'msg_type' => $msg_type,
        ]);
    }
}
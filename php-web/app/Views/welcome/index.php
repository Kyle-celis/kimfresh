<?php
// app/Views/welcome/index.php
$base = '/php-web/public';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Select Account Type — KimFresh</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;600;700;800&display=swap">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: "Nunito Sans", Arial, sans-serif; }
    body { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #C34200; padding: 20px; }
    
    .split-container {
      display: flex; width: 100%; max-width: 1000px; min-height: 600px; background: #fff;
      border-radius: 20px; overflow: hidden; box-shadow: 0 24px 60px rgba(0,0,0,0.25);
    }
    
    /* Left Panel */
    .left-panel {
      flex: 1; background: #C34200; padding: 60px 48px; position: relative; display: flex; flex-direction: column;
      color: #fff; background-image: radial-gradient(circle at 10% 90%, rgba(255,255,255,0.1) 0%, transparent 40%);
      justify-content: center;
    }
    /* Big Logo on Left Panel */
    .left-panel .logo { max-width: 220px; height: auto; margin-bottom: 40px; object-fit: contain; }
    
    .left-panel h1 { font-size: 34px; font-weight: 800; line-height: 1.2; margin-bottom: 20px; }
    .left-panel p { font-size: 16px; line-height: 1.6; color: rgba(255,255,255,0.9); max-width: 90%; }

    /* Right Panel */
    .right-panel { flex: 1; padding: 60px 50px; display: flex; flex-direction: column; justify-content: center; }
    .right-panel h2 { font-size: 26px; font-weight: 800; color: #C34200; margin-bottom: 8px; }
    .right-panel .subtitle { font-size: 14px; color: #737887; margin-bottom: 30px; line-height: 1.5; }
    
    /* Role Cards */
    .role-options { display: flex; gap: 16px; margin-bottom: 30px; }
    .role-card {
      flex: 1; border: 2px solid #dfe1e9; border-radius: 12px; padding: 20px; cursor: pointer;
      position: relative; transition: all 0.2s ease;
    }
    .role-card:hover { border-color: #f9d4b8; background: #fdfbf9; }
    .role-card.selected { border-color: #C34200; background: #fff; box-shadow: 0 4px 12px rgba(195,66,0,0.1); }
    .role-card input[type="radio"] { position: absolute; top: 20px; right: 20px; accent-color: #C34200; width: 18px; height: 18px; cursor: pointer; }
    .role-card h3 { font-size: 18px; font-weight: 800; color: #252733; margin-bottom: 8px; pointer-events: none; }
    .role-card p { font-size: 12px; color: #737887; line-height: 1.5; pointer-events: none; }

    .btn-continue {
      width: 100%; padding: 16px; background: #C34200; color: #fff; border: none; border-radius: 10px;
      font-size: 16px; font-weight: 800; cursor: pointer; transition: background 0.2s;
    }
    .btn-continue:hover { background: #9a3400; }
    
    .app-download-link { text-align: center; margin-top: 30px; font-size: 13px; color: #737887; }
    .app-download-link a { color: #C34200; font-weight: 800; text-decoration: none; }
    .app-download-link a:hover { text-decoration: underline; }

    .footer-text { margin-top: 40px; text-align: center; font-size: 12px; color: #a0a3af; }

    /* Responsive */
    @media (max-width: 800px) {
      .split-container { flex-direction: column; max-width: 500px; }
      .left-panel { padding: 40px 32px; }
      .left-panel h1 { font-size: 28px; }
      .right-panel { padding: 40px 32px; }
      .role-options { flex-direction: column; gap: 12px; }
    }
  </style>
</head>
<body>

  <div class="split-container">
    <!-- Left Side (Orange) -->
    <div class="left-panel">
      <!-- Use your original logo -->
      <img src="<?= $base ?>/logo.png" alt="KimFresh" class="logo">
      
      <h1>Control everything from one dashboard</h1>
      <p>Monitor users, manage product and monitor real-time delivery fulfillment</p>
    </div>

    <!-- Right Side (White) -->
    <div class="right-panel">
      <h2>Select Your Account Type</h2>
      <p class="subtitle">Please choose your user role below to access your personalized workspace.</p>

      <form action="<?= $base ?>/login" method="GET" id="roleForm">
        <div class="role-options">
          <!-- Admin Card -->
          <label class="role-card selected" onclick="selectCard(this)">
            <input type="radio" name="role" value="admin" checked>
            <h3>Admin</h3>
            <p>Monitors Inventory, Manage Orders and Products, Monitors Driver Fulfillment</p>
          </label>

          <!-- Super Admin Card -->
          <label class="role-card" onclick="selectCard(this)">
            <input type="radio" name="role" value="superadmin">
            <h3>Super Admin</h3>
            <p>Monitors admin account, View, edit, deactivate, and audit other admin accounts.</p>
          </label>
        </div>

        <button type="submit" class="btn-continue">Continue</button>
      </form>

      <!-- Mobile App Link -->
      <div class="app-download-link">
        Are you a Driver or Customer? <br>
        <a href="<?= $base ?>/download">📱 Download the Mobile App</a>
      </div>

      <div class="footer-text">
        &copy; <?= date('Y') ?> KimFresh. All rights reserved.
      </div>
    </div>
  </div>

  <script>
    function selectCard(selectedLabel) {
      document.querySelectorAll('.role-card').forEach(card => card.classList.remove('selected'));
      selectedLabel.classList.add('selected');
      selectedLabel.querySelector('input[type="radio"]').checked = true;
    }
  </script>
</body>
</html>

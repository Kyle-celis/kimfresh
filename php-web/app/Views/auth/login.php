<?php /* Login view — variable from AuthController::showLogin() */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KimFresh - Admin Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/php-web/public/css/theme.css">
    <link rel="stylesheet" href="/php-web/public/css/login.css">
</head>
<body>
    <div class="page">
        <div class="left">
            <img src="/php-web/public/logo.png" alt="KimFresh" class="logo">
            <div class="left-text">
                <h1>Control everything</h1>
                <h1 class="pink">from one dashboard.</h1>
                <p>Monitor users, manage products, and monitor real-time delivery fulfillment.</p>
            </div>
            <div class="boxes">
                <div class="box"><h3>Monitor</h3><p>Live deliveries</p></div>
                <div class="box"><h3>Manage</h3><p>Order requests</p></div>
                <div class="box"><h3>Monitor</h3><p>Inventory</p></div>
            </div>
        </div>

        <div class="right">
            <div class="login-box">
                <div class="tabs">
                    <a href="javascript:void(0);" class="tab active">Login</a>
                </div>

                <div class="panel">
                    <h1>Login to your account</h1>
                    <p class="subtitle">Sign in to your KimFresh admin account to continue.</p>

                    <?php if (!empty($loginError)): ?>
                        <p class="alert-box" style="color: red; background: #fee2e2; padding: 10px; border-radius: 5px;">
                            <?= htmlspecialchars($loginError) ?>
                        </p>
                    <?php endif; ?>

                    <form method="POST" action="/php-web/public/login">
                        <label for="email">Email</label>
                        <div class="input-box">
                            <input type="email" name="email" placeholder="admin@kimfresh.com" required>
                        </div>

                        <label for="password">Password</label>
                        <div class="input-box">
                            <input type="password" name="password" placeholder="Enter your password" required>
                        </div>

                        <button type="submit" name="login_submit" class="btn">Login</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<!-- SCROLL MEMORY -->
<script>
    if (sessionStorage.getItem('scrollPos')) { 
        document.documentElement.style.visibility = 'hidden'; 
    }
    window.addEventListener('load', function() {
        const savedPosition = sessionStorage.getItem('scrollPos');
        if (savedPosition) {
            window.scrollTo(0, parseInt(savedPosition));
            document.documentElement.style.visibility = 'visible';
            sessionStorage.removeItem('scrollPos');
        }
    });
    document.addEventListener('mousedown', function(event) {
        if (event.target.closest('th a') || 
            event.target.closest('.pagination a') || 
            event.target.closest('.filter-select') ||
            event.target.closest('.week-controls a') ||
            event.target.closest('button[type="submit"]')) {
            sessionStorage.setItem('scrollPos', window.scrollY);
        }
    });
</script>
</body>
</html>

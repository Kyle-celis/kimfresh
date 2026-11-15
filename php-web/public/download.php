<?php
// php-web/public/download.php — Android-only download page
$ua        = $_SERVER['HTTP_USER_AGENT'] ?? '';
$isAndroid = stripos($ua, 'Android') !== false;
$isIOS     = (stripos($ua, 'iPhone') !== false
           || stripos($ua, 'iPad')   !== false
           || stripos($ua, 'iPod')   !== false);

// ---- CONFIG: change this to your real APK URL ----
$androidUrl = 'https://kimfresh.com/downloads/kimfresh-app.apk';

// The URL encoded inside the QR code (must be a live public URL)
$qrTarget = 'https://kimfresh.com/download.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Download KimFresh App</title>
  <link rel="stylesheet" href="css/role-select.css">
  <link rel="icon" href="logo.png">
</head>
<body class="role-page">
  <div class="download-card">
    <img src="logo.png" alt="KimFresh" class="role-logo" style="height:56px;">

    <?php if ($isIOS): ?>
      <!-- ============ iPhone / iPad ============ -->
      <h1>iPhone App Coming Soon</h1>
      <p class="subtitle">
        The KimFresh app isn't available on the App Store yet.
      </p>
      <div class="ios-notice">
        📱 <strong>iPhone users:</strong> please check back soon, or
        contact support if you need access now.
      </div>
      <a href="index.php" class="back-link">← Back to home</a>

    <?php elseif ($isAndroid): ?>
      <!-- ============ Android ============ -->
      <h1>Download KimFresh</h1>
      <p class="subtitle">Tap below to download the app</p>
      <a href="<?= htmlspecialchars($androidUrl) ?>" class="download-btn">
        ⬇️ Download for Android
      </a>
      <p class="scan-hint" style="margin-top:16px;">
        If the download doesn't start, enable
        <em>"Install unknown apps"</em> in your browser settings.
      </p>
      <a href="index.php" class="back-link">← Back to home</a>

    <?php else: ?>
      <!-- ============ Desktop: show QR ============ -->
      <h1>Get the KimFresh App</h1>
      <p class="subtitle">Scan the QR code with your phone camera</p>
      <div id="qrcode"></div>
      <p class="scan-hint">📷 Android only — iPhone support coming soon</p>
      <a href="index.php" class="back-link">← Back to home</a>
    <?php endif; ?>
  </div>

  <?php if (! $isIOS && ! $isAndroid): ?>
  <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
  <script>
    new QRCode(document.getElementById('qrcode'), {
      text: <?= json_encode($qrTarget) ?>,
      width: 260,
      height: 260,
      colorDark: '#1f2937',
      colorLight: '#ffffff',
      correctLevel: QRCode.CorrectLevel.H
    });
  </script>
  <?php endif; ?>
</body>
</html>
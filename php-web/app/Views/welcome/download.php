<?php
// app/Views/welcome/download.php — Android-only download gate
$base = '/php-web/public';

$ua        = $_SERVER['HTTP_USER_AGENT'] ?? '';
$isAndroid = stripos($ua, 'Android') !== false;
$isIOS     = (stripos($ua, 'iPhone') !== false
           || stripos($ua, 'iPad')   !== false
           || stripos($ua, 'iPod')   !== false);

$androidUrl = $base . '/downloads/kimfresh-app.apk';
$qrTarget   = 'http://localhost/php-web/public/download';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Download App — KimFresh</title>
  <link rel="stylesheet" href="<?= $base ?>/css/role-select.css">
  <link rel="icon" href="<?= $base ?>/logo.png">
</head>
<body class="role-page">
  <div class="download-card">
    <img src="<?= $base ?>/logo.png" alt="KimFresh" class="role-logo">

    <?php if ($isIOS): ?>
      <h1>iPhone App Coming Soon</h1>
      <p class="subtitle">The KimFresh mobile app isn't on the App Store yet.</p>
      <div class="ios-notice">
        📱 <strong>iPhone users:</strong> we're working on it!
        Please check back soon, or contact support if you need access right now.
      </div>
      <a href="<?= $base ?>/welcome" class="back-link">← Back to home</a>

    <?php elseif ($isAndroid): ?>
      <h1>Download KimFresh</h1>
      <p class="subtitle">Tap below to install the app</p>
      <a href="<?= htmlspecialchars($androidUrl) ?>" class="download-btn">
        ⬇️ Download for Android
      </a>
      <p class="install-hint">
        If the install is blocked, enable
        <em>"Install unknown apps"</em> for your browser in Settings.
      </p>
      <a href="<?= $base ?>/welcome" class="back-link">← Back to home</a>

    <?php else: ?>
      <h1>Get the KimFresh App</h1>
      <p class="subtitle">Scan the QR code with your phone camera</p>
      <div id="qrcode"></div>
      <p class="scan-hint">📷 Android only — iPhone version coming soon</p>
      <a href="<?= $base ?>/welcome" class="back-link">← Back to home</a>
    <?php endif; ?>
  </div>

  <?php if (! $isIOS && ! $isAndroid): ?>
  <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
  <script>
    new QRCode(document.getElementById('qrcode'), {
      text: <?= json_encode($qrTarget) ?>,
      width: 240,
      height: 240,
      colorDark: '#252733',
      colorLight: '#ffffff',
      correctLevel: QRCode.CorrectLevel.H
    });
  </script>
  <?php endif; ?>
</body>
</html>

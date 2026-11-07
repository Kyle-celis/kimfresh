<?php
// app/Views/partials/flash.php
$flash = flash_get();
if ($flash):
    $type = $flash['type'] === 'success' ? 'success' : 'error';
    $bg = $type === 'success' ? '#dcfce7' : '#fee2e2';
    $color = $type === 'success' ? '#166534' : '#991b1b';
    $border = $type === 'success' ? '#bbf7d0' : '#f87171';
?>
    <div style="background: <?= $bg ?>; color: <?= $color ?>; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; border: 1px solid <?= $border ?>;">
        <?= htmlspecialchars($flash['message']) ?>
    </div>
<?php endif; ?>
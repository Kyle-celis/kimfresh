<?php
// app/Views/partials/pagination.php
// Usage: require __DIR__ . '/../partials/pagination.php';
// Requires in scope: $page (int, 1-based), $total_pages (int)
// Preserves existing query string (?filter=..., ?status=...)
if (!isset($page) || !isset($total_pages) || $total_pages <= 1) return;

$qs = $_GET;
unset($qs['page']);
$qsBase = http_build_query($qs);
$qsSuffix = $qsBase !== '' ? '&' . $qsBase : '';

// Window: current ±2, clamped
$start = max(1, $page - 2);
$end   = min($total_pages, $page + 2);
?>
<div class="pagination">
    <?php if ($page > 1): ?>
        <a href="?page=<?= $page - 1 ?><?= htmlspecialchars($qsSuffix) ?>">Prev</a>
    <?php else: ?>
        <span class="disabled">Prev</span>
    <?php endif; ?>

    <?php if ($start > 1): ?>
        <a href="?page=1<?= htmlspecialchars($qsSuffix) ?>">1</a>
        <?php if ($start > 2): ?><span class="disabled">…</span><?php endif; ?>
    <?php endif; ?>

    <?php for ($i = $start; $i <= $end; $i++): ?>
        <?php if ($i == $page): ?>
            <span class="active"><?= $i ?></span>
        <?php else: ?>
            <a href="?page=<?= $i ?><?= htmlspecialchars($qsSuffix) ?>"><?= $i ?></a>
        <?php endif; ?>
    <?php endfor; ?>

    <?php if ($end < $total_pages): ?>
        <?php if ($end < $total_pages - 1): ?><span class="disabled">…</span><?php endif; ?>
        <a href="?page=<?= $total_pages ?><?= htmlspecialchars($qsSuffix) ?>"><?= $total_pages ?></a>
    <?php endif; ?>

    <?php if ($page < $total_pages): ?>
        <a href="?page=<?= $page + 1 ?><?= htmlspecialchars($qsSuffix) ?>">Next</a>
    <?php else: ?>
        <span class="disabled">Next</span>
    <?php endif; ?>
</div>
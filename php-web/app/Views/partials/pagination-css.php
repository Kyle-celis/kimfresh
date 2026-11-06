<?php
// app/Views/partials/pagination-css.php
// Usage: require inside <head> or before </body>
?>
<style>
/* PAGINATION — shared partial */
.pagination { display: flex; justify-content: center; gap: 6px; margin-top: 24px; flex-wrap: wrap; }
.pagination a, .pagination span {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 36px; height: 36px; padding: 0 12px;
    border: 1px solid #ddd; border-radius: 6px;
    background: white; color: #333;
    text-decoration: none; font-size: 14px; font-weight: 500;
    transition: all 0.15s;
}
.pagination a:hover { background: #fdf0e6; border-color: #C34200; color: #C34200; }
.pagination .active { background: #C34200; border-color: #C34200; color: white; cursor: default; }
.pagination .disabled { opacity: 0.5; pointer-events: none; }
@media (max-width: 600px) {
    .pagination { gap: 4px; }
    .pagination a, .pagination span { min-width: 32px; height: 32px; padding: 0 8px; font-size: 13px; }
}
</style>
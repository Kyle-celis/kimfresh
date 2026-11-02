/**
 * Escape HTML special characters to prevent XSS.
 * 
 * Converts dangerous characters like < > " ' into safe entities
 * so they display as text instead of running as code.
 */
function escapeHtml(unsafe) {
    if (unsafe === null || unsafe === undefined) return '';
    return String(unsafe)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

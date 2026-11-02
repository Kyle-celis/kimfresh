/**
 * Auth helpers — session check and logout.
 */

function requireAdmin() {
    const admin = JSON.parse(localStorage.getItem('admin'));
    if (!admin || admin.role !== 'admin') {
        window.location.href = 'login.html';
        return null;
    }
    return admin;
}

function logout() {
    localStorage.clear();
    window.location.href = 'login.html';
}

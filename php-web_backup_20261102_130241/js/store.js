const API_BASE = '/api';
const STATUS_LABEL = {
    pending: 'Pending',
    approved: 'Approved',
    preparing: 'In Pickup',
    out: 'Out for Delivery',
    delivered: 'Delivered',
    rejected: 'Rejected'
};

function getToken() {
    return localStorage.getItem('token');
}

function authHeaders() {
    return {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${getToken()}`
    };
}

async function fetchAPI(endpoint, options = {}) {
    try {
        const res = await fetch(`${API_BASE}${endpoint}`, {
            ...options,
            headers: { ...authHeaders(), ...(options.headers || {}) }
        });
        if (res.status === 401 || res.status === 403) {
            logout();
            throw new Error('Unauthorized');
        }
        return await res.json();
    } catch (err) {
        console.error(`API Error (${endpoint}):`, err);
        return null;
    }
}

async function getOrders() {
    const data = await fetchAPI('/orders/all');
    return Array.isArray(data) ? data : [];
}

async function getProducts() {
    const data = await fetchAPI('/products/all');
    return Array.isArray(data) ? data : [];
}

async function getDrivers() {
    const data = await fetchAPI('/drivers');
    return Array.isArray(data) ? data : [];
}

function money(n) {
    return '₱' + Number(n).toFixed(2);
}

function formatDate(value, withTime) {
    if (!value) return '';
    const d = new Date(value.length === 10 ? value + 'T00:00:00' : value);
    const opts = { month: 'short', day: 'numeric', year: 'numeric' };
    if (withTime) { opts.hour = 'numeric'; opts.minute = '2-digit'; }
    return d.toLocaleString('en-US', opts);
}

function logout() {
    localStorage.removeItem('token');
    localStorage.removeItem('admin');
    window.location.href = 'login.html';
}

function toggleProfileMenu(event) {
    event.stopPropagation();
    const menu = document.getElementById('profileMenu');
    const trigger = menu && menu.querySelector('.profile-trigger');
    const dropdown = document.getElementById('profileDropdown');
    if (!trigger || !dropdown) return;
    const isOpen = trigger.getAttribute('aria-expanded') === 'true';
    trigger.setAttribute('aria-expanded', String(!isOpen));
    dropdown.hidden = isOpen;
}

document.addEventListener('click', function (event) {
    const menu = document.getElementById('profileMenu');
    if (!menu || menu.contains(event.target)) return;
    const trigger = menu.querySelector('.profile-trigger');
    const dropdown = document.getElementById('profileDropdown');
    if (trigger) trigger.setAttribute('aria-expanded', 'false');
    if (dropdown) dropdown.hidden = true;
});

(function setupPage() {
    if (!localStorage.getItem('token')) {
        window.location.href = 'login.html';
        return;
    }
    const admin = JSON.parse(localStorage.getItem('admin') || '{}');
    const name = admin.name || 'Admin';

    const userNameEl = document.getElementById('userName');
    const avatarEl = document.getElementById('avatar');
    const dropdownName = document.getElementById('dropdownUserName');

    if (userNameEl) userNameEl.textContent = name;
    if (avatarEl) avatarEl.textContent = name.charAt(0).toUpperCase();
    if (dropdownName) dropdownName.textContent = name;
})();

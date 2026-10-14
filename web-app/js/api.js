/**
 * API Model — all backend calls in one place.
 */

const API = `${window.location.protocol}//${window.location.hostname}:${window.location.port}`;

function authHeaders() {
    return {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${localStorage.getItem('token')}`
    };
}

// ---------- AUTH ----------
async function apiLogin(email, password) {
    const res = await fetch(`${API}/api/login`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({email, password})
    });
    return res.json();
}

// ---------- DASHBOARD ----------
async function apiGetSummary() {
    const res = await fetch(`${API}/api/dashboard/summary`, { headers: authHeaders() });
    return res.json();
}

async function apiGetOrdersPerWeek(weekStart) {
    const res = await fetch(`${API}/api/dashboard/orders-per-week?week_start=${weekStart}`, { headers: authHeaders() });
    return res.json();
}

async function apiGetRecentOrders() {
    const res = await fetch(`${API}/api/dashboard/recent-orders`, { headers: authHeaders() });
    return res.json();
}

// ---------- ORDERS ----------
async function apiGetAllOrders() {
    const res = await fetch(`${API}/api/orders/all`, { headers: authHeaders() });
    return res.json();
}

async function apiUpdateOrderStatus(orderId, status) {
    const res = await fetch(`${API}/api/orders/${orderId}/status`, {
        method: 'PUT',
        headers: authHeaders(),
        body: JSON.stringify({status})
    });
    return res.json();
}

// ---------- PRODUCTS ----------
async function apiGetProducts() {
    const res = await fetch(`${API}/api/products`);
    return res.json();
}

async function apiGetAllProducts() {
    const res = await fetch(`${API}/api/products/all`);
    return res.json();
}

async function apiAddProduct(product) {
    const res = await fetch(`${API}/api/products`, {
        method: 'POST',
        headers: authHeaders(),
        body: JSON.stringify(product)
    });
    return res.json();
}

async function apiHideProduct(id) {
    const res = await fetch(`${API}/api/products/${id}/hide`, {
        method: 'PUT',
        headers: authHeaders()
    });
    return res.json();
}

async function apiRestoreProduct(id) {
    const res = await fetch(`${API}/api/products/${id}/restore`, {
        method: 'PUT',
        headers: authHeaders()
    });
    return res.json();
}

// ---------- DRIVERS ----------
async function apiGetDrivers() {
    const res = await fetch(`${API}/api/drivers`, { headers: authHeaders() });
    return res.json();
}

async function apiAddDriver(driver) {
    const res = await fetch(`${API}/api/drivers`, {
        method: 'POST',
        headers: authHeaders(),
        body: JSON.stringify(driver)
    });
    return res.json();
}

async function apiAssignOrder(driverId, orderId) {
    const res = await fetch(`${API}/api/drivers/${driverId}/assign`, {
        method: 'POST',
        headers: authHeaders(),
        body: JSON.stringify({order_id: orderId})
    });
    return res.json();
}

async function apiReassignOrder(orderId, driverId) {
    const res = await fetch(`${API}/api/orders/${orderId}/reassign`, {
        method: 'POST',
        headers: authHeaders(),
        body: JSON.stringify({driver_id: driverId})
    });
    return res.json();
}

async function apiGetOrderDelivery(orderId) {
    const res = await fetch(`${API}/api/orders/${orderId}/delivery`, {
        headers: authHeaders()
    });
    return res.json();
}

async function apiApproveDriver(driverId) {
    const res = await fetch(`${API}/api/drivers/${driverId}/approve`, {
        method: 'POST',
        headers: authHeaders()
    });
    return res.json();
}

async function apiRejectDriver(driverId) {
    const res = await fetch(`${API}/api/drivers/${driverId}/reject`, {
        method: 'POST',
        headers: authHeaders()
    });
    return res.json();
}
/**
 * Orders Controller.
 */

const admin = requireAdmin();
if (!admin) throw new Error('Not logged in');

let allOrders = [];
let currentPage = 1;
const PER_PAGE = 10;

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    const hours = String(d.getHours()).padStart(2, '0');
    const mins = String(d.getMinutes()).padStart(2, '0');
    return `${year}-${month}-${day} ${hours}:${mins}`;
}

async function loadOrders(status, query) {
    const data = await apiGetAllOrders();
    allOrders = data;
    if (status !== undefined && query !== undefined) {
        applyFilter(status, query);
    } else {
        renderOrders(data);
    }
}

function applyFilter(status, query) {
    let filtered = allOrders;
    if (status && status !== 'all') {
        filtered = filtered.filter(o => o.order_status === status);
    }
    if (query) {
        const q = query.toLowerCase();
        filtered = filtered.filter(o => {
            const customerMatch = o.customer_code && o.customer_code.toLowerCase().includes(q);
            const itemMatch = o.items && o.items.some(i =>
                (i.product_name && i.product_name.toLowerCase().includes(q)) ||
                (i.sku && i.sku.toLowerCase().includes(q))
            );
            return customerMatch || itemMatch;
        });
    }
    window._filteredOrders = filtered;
    currentPage = 1;
    renderPage();
}

function renderPage() {
    const filtered = window._filteredOrders || [];
    const totalPages = Math.max(1, Math.ceil(filtered.length / PER_PAGE));
    if (currentPage > totalPages) currentPage = totalPages;
    const start = (currentPage - 1) * PER_PAGE;
    renderOrders(filtered.slice(start, start + PER_PAGE));
    renderPagination(totalPages);
}

function renderPagination(totalPages) {
    const el = document.getElementById('orderPagination');
    if (totalPages <= 1) { el.innerHTML = ''; return; }
    let html = '';
    if (currentPage > 1) html += `<button onclick="goToPage(${currentPage - 1})">◀ Prev</button>`;
    for (let i = 1; i <= totalPages; i++) {
        html += i === currentPage
            ? `<button class="active">${i}</button>`
            : `<button onclick="goToPage(${i})">${i}</button>`;
    }
    if (currentPage < totalPages) html += `<button onclick="goToPage(${currentPage + 1})">Next ▶</button>`;
    el.innerHTML = html;
}

function goToPage(page) {
    currentPage = page;
    renderPage();
}

function renderOrders(orders) {
    const list = document.getElementById('orderList');
    list.innerHTML = '';
    if (orders.length === 0) {
        list.innerHTML = '<p>No orders</p>';
        return;
    }
    orders.forEach(o => {
        const alert = o.is_alert === true || o.is_alert === 1;
        const cardClass = alert ? 'order-card alert' : 'order-card';

        let itemsHtml = '';
        if (o.items && o.items.length > 0) {
            o.items.forEach(item => {
                itemsHtml += `<div class="order-item">• ${escapeHtml(item.product_name)} (x${item.quantity}) - ₱${escapeHtml(item.subtotal)}</div>`;
            });
        }

        let tempHtml = '';
        if (o.temperature) {
            tempHtml = `<div class="temp-info ${alert ? 'alert-text' : ''}">Temp: ${escapeHtml(o.temperature)}°C | Humidity: ${escapeHtml(o.humidity)}%</div>`;
        }

        list.innerHTML += `
            <div class="${cardClass}">
                <div class="order-header">
                    <div>
                        <div class="order-id">Order #${escapeHtml(o.order_id)}</div>
                        <div class="order-meta">Customer: ${escapeHtml(o.customer_code || '-')}</div>
                        <div class="order-meta">${escapeHtml(formatDate(o.order_date))}</div>
                    </div>
                    <div style="text-align:right;">
                        <div class="order-total">₱${escapeHtml(o.total_amount)}</div>
                        <div class="order-status">${escapeHtml(o.order_status)}</div>
                    </div>
                </div>
                <div class="order-items">${itemsHtml}</div>
                ${tempHtml}
                <div class="order-actions">
                    ${o.order_status === 'pending' ? `
                        <button onclick="updateStatus(${o.order_id}, 'approved')">Approve</button>
                        <button onclick="updateStatus(${o.order_id}, 'rejected')">Reject</button>
                    ` : ''}
                </div>
            </div>
        `;
    });
}

function filterOrders() {
    applyFilter(
        document.getElementById('statusFilter').value,
        document.getElementById('searchBox').value
    );
}

async function updateStatus(orderId, status) {
    const data = await apiUpdateOrderStatus(orderId, status);
    alert(data.message);
    filterOrders();
}

loadOrders();
setInterval(() => {
    filterOrders();
}, 10000);

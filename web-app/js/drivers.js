/**
 * Drivers Controller.
 */

const admin = requireAdmin();
if (!admin) throw new Error('Not logged in');

let allDrivers = [];
let currentDriverPage = 1;
const DRIVERS_PER_PAGE = 10;

async function loadDrivers() {
    const orders = await apiGetAllOrders();
    window._allOrders = orders;

    const data = await apiGetDrivers();
    allDrivers = data;
    renderDriverPage();
}

function renderDriverPage() {
    const totalPages = Math.max(1, Math.ceil(allDrivers.length / DRIVERS_PER_PAGE));
    if (currentDriverPage > totalPages) currentDriverPage = totalPages;
    const start = (currentDriverPage - 1) * DRIVERS_PER_PAGE;
    renderDrivers(allDrivers.slice(start, start + DRIVERS_PER_PAGE));
    renderDriverPagination(totalPages);
}

function renderDriverPagination(totalPages) {
    const el = document.getElementById('driverPagination');
    if (totalPages <= 1) { el.innerHTML = ''; return; }
    let html = '';
    if (currentDriverPage > 1) html += `<button onclick="goToDriverPage(${currentDriverPage - 1})">◀ Prev</button>`;
    for (let i = 1; i <= totalPages; i++) {
        html += i === currentDriverPage
            ? `<button class="active">${i}</button>`
            : `<button onclick="goToDriverPage(${i})">${i}</button>`;
    }
    if (currentDriverPage < totalPages) html += `<button onclick="goToDriverPage(${currentDriverPage + 1})">Next ▶</button>`;
    el.innerHTML = html;
}

function goToDriverPage(page) {
    currentDriverPage = page;
    renderDriverPage();
}

function renderDrivers(data) {
    const list = document.getElementById('driverList');
    list.innerHTML = '';
    if (data.length === 0) {
        list.innerHTML = '<p>No drivers</p>';
        return;
    }
    const orders = window._allOrders || [];

    data.forEach(d => {
        let assignedHtml = '';
        let assignedOrderIds = [];
        let previousHtml = '';

        if (d.assigned_orders) {
            d.assigned_orders.split(',').map(s => s.trim()).forEach(oStr => {
                const match = oStr.match(/#(\d+)/);
                if (!match) return;
                const oid = parseInt(match[1]);
                const order = orders.find(o => o.order_id === oid);
                if (!order) return;

                const productList = (order.items || [])
                    .map(i => `${escapeHtml(i.product_name)} (x${i.quantity})`)
                    .join(', ');

                if (order.order_status !== 'delivered') {
                    assignedOrderIds.push(oid);
                    assignedHtml += `<div class="order-history-item"><strong>Order #${order.order_id}</strong> - ${productList} - ${escapeHtml(order.order_status)}</div>`;
                } else {
                    previousHtml += `<div class="order-history-item"><strong>Order #${order.order_id}</strong> - ${productList}</div>`;
                }
            });
        }

        const isPending = d.account_status === 'pending';
        const isRejected = d.account_status === 'rejected';

        let actionHtml = '';
        if (isPending) {
            actionHtml = `
                <div class="driver-actions">
                    <button class="primary" onclick="approveDriver(${d.driver_id})">Approve</button>
                    <button onclick="rejectDriver(${d.driver_id})">Reject</button>
                </div>
            `;
        } else if (isRejected) {
            actionHtml = `<div class="driver-actions"><em>Rejected — hidden after 7 days</em></div>`;
        }

        list.innerHTML += `
            <div class="driver-card ${escapeHtml(d.account_status || '')}">
                <div class="driver-header">
                    <div class="driver-name">${escapeHtml(d.name)}</div>
                    <div class="driver-status ${escapeHtml(d.account_status || '')}">${escapeHtml(d.account_status || d.status)}</div>
                </div>
                <div class="driver-info">${escapeHtml(d.email || '-')} | ${escapeHtml(d.phone || '-')}</div>
                <div class="driver-info">${escapeHtml(d.license_number || '-')}</div>

                ${actionHtml}

                ${!isPending && assignedOrderIds.length > 0
                    ? `<div class="delivered-orders"><strong>Assigned Orders:</strong>${assignedHtml}</div>`
                    : (isPending ? '' : '<div class="delivered-orders">No orders assigned</div>')}

                ${previousHtml ? `<div class="order-history"><h4>Previous Orders</h4>${previousHtml}</div>` : ''}
            </div>
        `;
    });
}

async function approveDriver(driverId) {
    if (!confirm('Approve this driver?')) return;
    const data = await apiApproveDriver(driverId);
    alert(data.message);
    loadDrivers();
}

async function rejectDriver(driverId) {
    if (!confirm('Reject this driver? They will be hidden after 7 days.')) return;
    const data = await apiRejectDriver(driverId);
    alert(data.message);
    loadDrivers();
}

loadDrivers();
setInterval(loadDrivers, 10000);
/**
 * Drivers Controller.
 */

const admin = requireAdmin();
if (!admin) throw new Error('Not logged in');

let allDrivers = [];
let currentDriverPage = 1;
const DRIVERS_PER_PAGE = 10;

async function addDriver() {
    const body = {
        name: document.getElementById('dName').value,
        email: document.getElementById('dEmail').value,
        phone: document.getElementById('dPhone').value,
        license_number: document.getElementById('dLicense').value
    };
    const data = await apiAddDriver(body);
    document.getElementById('addMsg').textContent = data.message;
    loadDrivers();
}

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
    const approvedOrders = orders.filter(o => o.order_status === 'approved');

    data.forEach(d => {
        let options = '<option value="">-- Select approved order --</option>';
        if (approvedOrders.length === 0) {
            options = '<option value="">No approved orders available</option>';
        } else {
            approvedOrders.forEach(o => {
                const productList = (o.items || [])
                    .map(i => `${i.product_name} (x${i.quantity})`)
                    .join(', ');
                options += `<option value="${o.order_id}">
                    Order #${o.order_id} - ${escapeHtml(productList)} - ${escapeHtml(o.customer_code)}
                </option>`;
            });
        }

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

        list.innerHTML += `
            <div class="driver-card" onclick="toggleDriverDropdown(event, ${d.driver_id})">
                <div class="driver-header">
                    <div class="driver-name">${escapeHtml(d.name)}</div>
                    <div class="driver-status">${escapeHtml(d.status)}</div>
                </div>
                <div class="driver-info">${escapeHtml(d.email || '-')} | ${escapeHtml(d.phone || '-')}</div>
                <div class="driver-info">${escapeHtml(d.license_number || '-')}</div>

                ${assignedOrderIds.length > 0
                    ? `<div class="delivered-orders"><strong>Assigned Orders:</strong>${assignedHtml}</div>`
                    : '<div class="delivered-orders">No orders assigned</div>'}

                <div class="dropdown" id="dropdown-${d.driver_id}" onclick="event.stopPropagation()">
                    <label>Assign order to ${escapeHtml(d.name)}</label>
                    <select id="orderSelect-${d.driver_id}">${options}</select>
                    <button onclick="assignOrder(${d.driver_id})">Assign</button>
                    <span class="msg" id="assignMsg-${d.driver_id}"></span>

                    ${previousHtml ? `<div class="order-history"><h4>Previous Orders</h4>${previousHtml}</div>` : ''}
                </div>
            </div>
        `;
    });
}

function toggleDriverDropdown(event, driverId) {
    const dropdown = document.getElementById(`dropdown-${driverId}`);
    if (!dropdown) return;
    const isOpen = dropdown.style.display === 'block';
    document.querySelectorAll('.dropdown').forEach(d => d.style.display = 'none');
    dropdown.style.display = isOpen ? 'none' : 'block';
}

async function assignOrder(driverId) {
    const orderId = document.getElementById(`orderSelect-${driverId}`).value;
    if (!orderId) {
        document.getElementById(`assignMsg-${driverId}`).textContent = 'Please select an order';
        return;
    }
    const data = await apiAssignOrder(driverId, parseInt(orderId));
    document.getElementById(`assignMsg-${driverId}`).textContent = data.message;
    setTimeout(loadDrivers, 1000);
}

loadDrivers();
setInterval(loadDrivers, 10000);

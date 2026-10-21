function escapeHtml(unsafe) {
    if (unsafe === null || unsafe === undefined) return '';
    return String(unsafe)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

let allOrders = [];
let currentStatus = 'all';
let liveUpdateInterval = null;
let currentOpenOrderId = null;

const STATUSES = ['all', 'pending', 'approved', 'waiting_for_pickup', 'in_transit', 'delivered', 'rejected'];

function getToken() {
    return localStorage.getItem('token');
}

async function loadOrders() {
    const data = await getOrders();
    if (!data) return;

    allOrders = data.sort((a, b) => new Date(b.order_date) - new Date(a.order_date));
    updateCounts();
    filterOrders();
}

function updateCounts() {
    STATUSES.forEach(status => {
        const count = status === 'all' ? allOrders.length : allOrders.filter(o => (o.order_status || '').toLowerCase() === status).length;
        const el = document.getElementById('count-' + status);
        if (el) el.textContent = count;
    });
}

function setStatus(status) {
    currentStatus = status;
    STATUSES.forEach(s => {
        const tab = document.getElementById('tab-' + s);
        if (tab) tab.classList.toggle('active', s === status);
    });
    filterOrders();
}

function filterOrders() {
    const searchBox = document.getElementById('searchBox');
    const query = searchBox ? searchBox.value.toLowerCase().trim() : '';
    
    let filtered = allOrders;

    if (currentStatus !== 'all') {
        filtered = filtered.filter(o => (o.order_status || '').toLowerCase() === currentStatus.toLowerCase());
    }

    if (query) {
        filtered = filtered.filter(o => {
            if (query.startsWith('#')) {
                return o.order_id.toString() === query.replace('#', '');
            }

            const matchesId = o.order_id.toString().includes(query);
            const matchesRetailer = o.customer_code && o.customer_code.toLowerCase().includes(query);
            const matchesDriver = o.driver_name && o.driver_name.toLowerCase().includes(query);
            
            const matchesItem = o.items && o.items.some(item => 
                (item.product_name || '').toLowerCase().includes(query)
            );

            return matchesId || matchesRetailer || matchesDriver || matchesItem;
        });
    }

    renderOrders(filtered);
}

function renderOrders(orders) {
    const list = document.getElementById('orderList');
    if (!list) return;

    if (orders.length === 0) {
        list.innerHTML = '<tr><td colspan="7" style="text-align:center; padding: 30px;">No orders found</td></tr>';
        return;
    }

    list.innerHTML = orders.map(o => {
        const orderId = o.order_id || o.id || 'N/A';
        const customer = o.customer_code || o.customer || 'Retailer';
        const retailerName = o.retailer_name || 'Retailer';
        const address = o.retailer_address || '';
        
        let itemsCount = 0;
        if (Array.isArray(o.items)) {
            itemsCount = o.items.reduce((sum, i) => sum + Number(i.quantity || i.qty || 0), 0);
        } else if (o.items) {
            itemsCount = Number(o.items);
        }

        const total = Number(o.total_amount || o.total || 0).toFixed(2);
        const displayStatus = (o.order_status || 'Unknown').replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());

        return `
            <tr>
                <td><span class="order-no">#${escapeHtml(orderId)}</span></td>
                <td>
                    <strong>${escapeHtml(retailerName)}</strong><br>
                    <small style="color: #555;">Code: ${escapeHtml(customer)}</small><br>
                    ${address ? `<small style="color: #777;">${escapeHtml(address)}</small>` : ''}
                </td>
                <td>${formatDate(o.order_date || o.date, true)}</td>
                <td>${itemsCount} items</td>
                <td class="num">₱${total}</td>
                <td><span class="status-pill ${o.order_status}">${displayStatus}</span></td>
                <td><button class="view-btn" onclick="viewOrder(${orderId})">View</button></td>
            </tr>
        `;
    }).join('');
}

async function viewOrder(id) {
    currentOpenOrderId = id;
    document.getElementById('overlay').classList.add('show');
    document.getElementById('drawer').classList.add('show');
    document.getElementById('drawerTitle').textContent = `Order #${id}`;
    document.getElementById('drawerBody').innerHTML = '<p style="text-align:center; padding: 20px;">Loading...</p>';
    document.getElementById('drawerFoot').innerHTML = '';

    await fetchOrderData(id, true);

    if (liveUpdateInterval) clearInterval(liveUpdateInterval);
    liveUpdateInterval = setInterval(() => {
        if (currentOpenOrderId) fetchOrderData(currentOpenOrderId, false);
    }, 20000);
}

async function fetchOrderData(id, isInitialLoad) {
    try {
        const token = getToken();
        if (!token) {
            window.location.href = 'login.html';
            return;
        }

        const res = await fetch(`/api/orders/single/${id}`, {
            headers: { 
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });
        
        if (res.status === 401 || res.status === 403) {
            window.location.href = 'login.html';
            return;
        }

        const order = await res.json();
        if (!res.ok) throw new Error(order.message || 'Failed to load');

        renderDrawerContent(order, id);

    } catch (err) {
        if (isInitialLoad) {
            document.getElementById('drawerBody').innerHTML = `<p style="color: red; padding: 20px;">Error: ${err.message}</p>`;
        }
    }
}

function renderDrawerContent(order, id) {
    const date = formatDate(order.order_date, true);
    const items = order.items || [];
    
    let itemsHtml = items.map(item => `
        <tr>
            <td>${escapeHtml(item.product_name)}</td>
            <td class="num">${item.quantity}</td>
            <td class="num">₱${Number(item.unit_price || 0).toFixed(2)}</td>
            <td class="num">₱${Number(item.subtotal || 0).toFixed(2)}</td>
        </tr>
    `).join('');

    let bodyHtml = `
        <div class="d-section">
            <p class="d-title">Order Information</p>
            <div class="info-grid">
                <div><label>Order ID</label><p>#${order.order_id || id}</p></div>
                <div><label>Retailer</label><p>${escapeHtml(order.customer_code || 'N/A')}</p></div>
                <div><label>Date</label><p>${escapeHtml(date)}</p></div>
                <div><label>Status</label><p><span class="status-pill ${order.order_status || 'pending'}">${(order.order_status || 'pending').replace(/_/g, ' ').toUpperCase()}</span></p></div>
            </div>
        </div>

        <div class="d-section">
            <p class="d-title">Items Ordered</p>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th class="num">Qty</th>
                        <th class="num">Price</th>
                        <th class="num">Total</th>
                    </tr>
                </thead>
                <tbody>${itemsHtml || '<tr><td colspan="4">No items found</td></tr>'}</tbody>
            </table>
            <div class="sum-row total"><span>Total Amount</span><strong>₱${Number(order.total_amount || 0).toFixed(2)}</strong></div>
        </div>
    `;

    if (order.sensor_data && order.sensor_data.length > 0) {
        let sensorHtml = order.sensor_data.map(s => `
            <tr>
                <td>${formatDate(s.reading_timestamp, true)}</td>
                <td style="color: ${s.is_alert ? 'red' : 'green'}; font-weight: bold;">
                    ${Number(s.temperature).toFixed(1)}°C
                </td>
                <td>${Number(s.humidity).toFixed(1)}%</td>
            </tr>
        `).join('');

        bodyHtml += `
            <div class="d-section cold-chain">
                <p class="d-title">Cold-chain History</p>
                <p class="history-note">IoT temperature readings recorded during this delivery.</p>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Recorded At</th>
                            <th>Temperature</th>
                            <th>Humidity</th>
                        </tr>
                    </thead>
                    <tbody>${sensorHtml}</tbody>
                </table>
            </div>
        `;
    }
    
    document.getElementById('drawerBody').innerHTML = bodyHtml;

    const foot = document.getElementById('drawerFoot');
    if (order.order_status === 'pending') {
        foot.innerHTML = `
            <button class="reject-btn" onclick="rejectIt(${id})">&times; Reject</button>
            <button class="approve-btn" onclick="approveIt(${id})">&#10003; Approve</button>
        `;
        foot.style.display = 'flex';
    } else {
        foot.style.display = 'none';
    }
}

function closeOrder() {
    currentOpenOrderId = null;
    document.getElementById('overlay').classList.remove('show');
    document.getElementById('drawer').classList.remove('show');
    if (liveUpdateInterval) clearInterval(liveUpdateInterval);
}

async function approveIt(id) {
    const res = await fetch(`/api/orders/single/${id}/approve`, {
        method: 'POST',
        headers: { 'Authorization': `Bearer ${getToken()}` }
    });
    if (res.ok) {
        closeOrder();
        loadOrders(); 
        showToast("Order Approved!");
    } else {
        showToast("Failed to approve order.");
    }
}

async function rejectIt(id) {
    if(!confirm('Are you sure you want to reject this order?')) return;
    
    const res = await fetch(`/api/orders/single/${id}/reject`, {
        method: 'POST',
        headers: { 'Authorization': `Bearer ${getToken()}` }
    });
    if (res.ok) {
        closeOrder();
        loadOrders();
        showToast("Order Rejected!");
    } else {
        showToast("Failed to reject order.");
    }
}

function showToast(msg) {
    const toast = document.getElementById('toast');
    if(!toast) return;
    toast.textContent = msg;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3000);
}

document.addEventListener('DOMContentLoaded', () => {
    loadOrders();
});
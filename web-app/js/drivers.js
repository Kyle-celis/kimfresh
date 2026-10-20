let allDrivers = [];
let allOrders = [];

function getToken() {
    return localStorage.getItem('token');
}

async function loadDrivers() {
    const list = document.getElementById('driverList');
    if (list) list.innerHTML = '<p style="text-align:center; padding: 20px;">Loading drivers...</p>';

    try {
        const [driversRes, ordersRes] = await Promise.all([
            fetch('/api/drivers', { headers: { 'Authorization': `Bearer ${getToken()}` } }),
            fetch('/api/orders/all', { headers: { 'Authorization': `Bearer ${getToken()}` } })
        ]);

        if (driversRes.status === 401 || ordersRes.status === 401) {
            window.location.href = 'login.html';
            return;
        }

        allDrivers = await driversRes.json();
        allOrders = await ordersRes.json();

        updateStats();
        renderDrivers(); // Call the render function directly

    } catch (err) {
        console.error("Error loading drivers:", err);
        if (list) list.innerHTML = '<p style="color:red; text-align:center;">Error loading data.</p>';
    }
}

function updateStats() {
    let available = 0;
    let onDelivery = 0;

    allDrivers.forEach(d => {
        if (d.status === 'available') available++;
        if (d.status === 'on_delivery') onDelivery++;
    });

    document.getElementById('statTotal').textContent = allDrivers.length;
    document.getElementById('statAvailable').textContent = available;
    document.getElementById('statPickup').textContent = 0;
    document.getElementById('statDelivery').textContent = onDelivery;
}

function renderDrivers() {
    const list = document.getElementById('driverList');
    if (!list) return;

    const query = document.getElementById('searchBox').value.toLowerCase().trim();
    const filter = document.getElementById('statusFilter').value;

    let filtered = allDrivers;

    // 1. Apply Status Filter
    if (filter !== 'all') {
        filtered = filtered.filter(d => d.status === filter);
    }

    // 2. Apply Search Text
    if (query) {
        filtered = filtered.filter(d => 
            (d.name || '').toLowerCase().includes(query) ||
            (d.email || '').toLowerCase().includes(query) ||
            (d.phone || '').toLowerCase().includes(query) ||
            (d.license_number || '').toLowerCase().includes(query)
        );
    }

    if (filtered.length === 0) {
        list.innerHTML = '<p style="text-align:center; padding: 20px;">No drivers found.</p>';
        return;
    }

    list.innerHTML = filtered.map(d => {
        const activeOrder = allOrders.find(o => 
            o.driver_id === d.driver_id && 
            ['approved', 'waiting_for_pickup', 'in_transit'].includes(o.order_status)
        );

        let statusClass = 'available';
        let statusLabel = 'Available';
        
        if (d.status === 'on_delivery') {
            statusClass = 'on-delivery';
            statusLabel = 'On Delivery';
        } else if (d.status === 'pending') {
            statusClass = 'pending';
            statusLabel = 'Pending Approval';
        }

        const license = d.license_number || d.license || 'No license';
        const initial = d.name ? d.name.charAt(0).toUpperCase() : '?';

        return `
            <div class="driver-card">
                <div class="driver-top">
                    <div class="driver-avatar">${escapeHtml(initial)}</div>
                    <div class="driver-title">
                        <div class="driver-name">${escapeHtml(d.name)}</div>
                        <div class="driver-license">${escapeHtml(license)}</div>
                    </div>
                    <span class="status ${statusClass}">${statusLabel}</span>
                </div>

                <div class="driver-contact">
                    <p>${escapeHtml(d.email || 'No email')}</p>
                    <p>${escapeHtml(d.phone || 'No phone')}</p>
                </div>

                <div class="driver-section">
                    <h4>Current Order</h4>
                    ${activeOrder ? `
                        <div class="order-item">
                            <div>
                                <strong>Order #${activeOrder.order_id}</strong>
                                <small>₱${Number(activeOrder.total_amount).toFixed(2)}</small>
                            </div>
                        </div>
                    ` : '<p class="empty-text">No active order</p>'}
                </div>
            </div>
        `;
    }).join('');
}

// --- EVENT LISTENERS ---
document.getElementById('searchBox')?.addEventListener('input', renderDrivers);
document.getElementById('statusFilter')?.addEventListener('change', renderDrivers);

document.addEventListener('DOMContentLoaded', () => {
    loadDrivers();
});
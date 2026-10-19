async function loadDrivers() {
    const drivers = await getDrivers();
    const orders = await getOrders();
    
    if (!drivers || !orders) return;

    // Update Stats
    const total = drivers.length;
    const available = drivers.filter(d => d.status === 'available').length;
    const onDelivery = drivers.filter(d => d.status === 'on_delivery').length;
    // Note: If your database doesn't have an 'in_pickup' status, we just calculate it from assigned orders
    const inPickup = drivers.filter(d => d.status === 'on_delivery').length; 

    document.getElementById('statTotal').textContent = total;
    document.getElementById('statAvailable').textContent = available;
    document.getElementById('statPickup').textContent = inPickup;
    document.getElementById('statDelivery').textContent = onDelivery;

    renderDrivers(drivers, orders);
}

function renderDrivers(drivers, orders) {
    const list = document.getElementById('driverList');
    if (!list) return;

    list.innerHTML = drivers.map(d => {
        // Find if this driver has an active order
        const activeOrder = orders.find(o => o.driver_id === d.driver_id && ['approved', 'waiting_for_pickup', 'in_transit'].includes(o.order_status));
        
        let statusClass = 'available';
        let statusLabel = 'Available';
        
        if (d.status === 'on_delivery') {
            statusClass = 'on-delivery';
            statusLabel = 'On Delivery';
        }

        return `
            <div class="driver-card">
                <div class="driver-top">
                    <div class="driver-avatar">${escapeHtml(d.name.charAt(0))}</div>
                    <div class="driver-title">
                        <div class="driver-name">${escapeHtml(d.name)}</div>
                        <div class="driver-license">${escapeHtml(d.license_number || 'No license')}</div>
                    </div>
                    <span class="status ${statusClass}">${statusLabel}</span>
                </div>
                <div class="driver-contact">
                    <p>${escapeHtml(d.email || 'No email')}</p>
                    <p>${escapeHtml(d.phone)}</p>
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

document.addEventListener('DOMContentLoaded', () => {
    loadDrivers();
    setInterval(loadDrivers, 5000);
});
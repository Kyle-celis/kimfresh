function renderOrders(orders) {
    const list = document.getElementById('orderList');
    if (!list) return;

    if (orders.length === 0) {
        list.innerHTML = '<tr><td colspan="7" style="text-align:center; padding: 30px;">No orders found</td></tr>';
        return;
    }

    list.innerHTML = orders.map(o => {
        const orderId = o.order_id || o.id || 'N/A';
        const customer = o.customer_code || o.retailer_name || o.customer || 'Retailer';
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
                    <strong>${escapeHtml(o.retailer_name || 'Retailer')}</strong><br>
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
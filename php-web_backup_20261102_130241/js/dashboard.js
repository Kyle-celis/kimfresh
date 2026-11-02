let chart = null;
let currentWeekStart = getMonday(new Date());
let globalOrders = [];

function getMonday(d) {
    const date = new Date(d);
    date.setHours(0, 0, 0, 0);
    const day = date.getDay();
    date.setDate(date.getDate() - day + (day === 0 ? -6 : 1));
    return date;
}

function setText(id, value) {
    const el = document.getElementById(id);
    if (el) el.textContent = value;
}

async function refreshPage() {
    // 1. Fetch real data
    const orders = await getOrders();
    const products = await getProducts();
    const drivers = await getDrivers();

    if (!orders || !products || !drivers) return;
    globalOrders = orders;

    // 2. Update the Stat Cards
    const count = status => orders.filter(o => o.order_status === status).length;
    
    setText('c-pending', count('pending'));
    setText('c-rejected', count('rejected'));
    
    const waiting = orders.filter(o => o.order_status === 'approved' && !o.driver_id).length;
    setText('c-waiting', waiting);

    const active = orders.filter(o => o.order_status === 'in_transit' || o.order_status === 'waiting_for_pickup').length;
    setText('c-active', active);

    const delivered = orders.filter(o => o.order_status === 'delivered');
    setText('c-delivered', delivered.length);
    
    const revenue = delivered.reduce((sum, o) => sum + Number(o.total_amount || 0), 0);
    setText('c-revenue', '₱' + revenue.toFixed(2) + ' fulfilled');

    // 3. Products & Stock
    const lowStock = products.filter(p => Number(p.stock_quantity) <= Number(p.reorder_level)).length;
    setText('c-lowstock', lowStock);
    setText('c-products', products.length + ' active products');

    // 4. Drivers
    const available = drivers.filter(d => d.status === 'available').length;
    setText('c-available', available);
    setText('c-drivers', 'of ' + drivers.length + ' drivers');

    // 5. Orders This Week
    const weekStart = currentWeekStart.getTime();
    const weekEnd = weekStart + (7 * 24 * 60 * 60 * 1000);
    const thisWeek = orders.filter(o => {
        const orderTime = new Date(o.order_date).getTime();
        return orderTime >= weekStart && orderTime < weekEnd;
    });
    setText('c-week', thisWeek.length);

    // 6. Update the components on the page
    showPipeline(orders);
    showDeliveries(orders);
    showStockAlerts(products);
    showRecentOrders(orders, drivers);
    loadChart(orders);
}

// --- PIPELINE ---
function showPipeline(orders) {
    const count = fn => orders.filter(fn).length;
    const steps = [
        ['Pending approval', count(o => o.order_status === 'pending')],
        ['Waiting for driver', count(o => o.order_status === 'approved' && !o.driver_id)],
        ['In pickup', count(o => o.order_status === 'waiting_for_pickup')],
        ['Out for delivery', count(o => o.order_status === 'in_transit')],
        ['Delivered', count(o => o.order_status === 'delivered')]
    ];

    const pipelineEl = document.getElementById('pipeline');
    if (!pipelineEl) return;

    pipelineEl.innerHTML = steps.map(s => `
        <a class="pipe-step ${s[1] > 0 ? 'attention' : ''}" href="orders.html">
            <strong>${s[1]}</strong>
            <span>${s[0]}</span>
        </a>
    `).join('');
}

// --- DELIVERY MONITORING ---
function showDeliveries(orders) {
    const active = orders.filter(o => ['approved', 'waiting_for_pickup', 'in_transit'].includes(o.order_status));
    const list = document.getElementById('deliveryList');
    if (!list) return;

    if (active.length === 0) {
        list.innerHTML = '<p class="empty-note">No deliveries in progress.</p>';
        return;
    }

    list.innerHTML = active.slice(0, 5).map(o => {
        const driver = o.driver_name || 'No driver assigned';
        let label = o.order_status.replace('_', ' ').toUpperCase();
        if (o.order_status === 'approved') label = 'WAITING FOR DRIVER';

        return `
            <div class="delivery">
                <div class="delivery-top">
                    <div>
                        <strong>Order #${o.order_id} &middot; ${o.customer_code}</strong>
                        <small>₱${Number(o.total_amount).toFixed(2)}</small>
                    </div>
                    <span class="status ${o.order_status}">${label}</span>
                </div>
                <p class="delivery-driver">Driver: <strong>${escapeHtml(driver)}</strong> &middot; ${escapeHtml(o.retailer_address || 'Address unavailable')}</p>
            </div>`;
    }).join('');
}

// --- STOCK ALERTS ---
function showStockAlerts(products) {
    const alerts = products
        .filter(p => p.status === 'available' && Number(p.stock_quantity) <= Number(p.reorder_level))
        .sort((a, b) => a.stock_quantity - b.stock_quantity);

    const list = document.getElementById('stockList');
    if (!list) return;

    if (alerts.length === 0) {
        list.innerHTML = '<p class="empty-note">All products are well stocked.</p>';
        return;
    }

    list.innerHTML = alerts.slice(0, 5).map(p => {
        const isOut = p.stock_quantity <= 0;
        const label = isOut ? 'Out of Stock' : 'Low Stock';
        const css = isOut ? 'out-of-stock' : 'low-stock';
        const percent = Math.min(100, Math.round((p.stock_quantity / Math.max(1, p.reorder_level)) * 100));

        return `
            <div class="stock-row">
                <div>
                    <strong>${escapeHtml(p.name)}</strong>
                    <small>${p.stock_quantity} left &middot; reorder at ${p.reorder_level}</small>
                    <div class="stock-bar"><i style="width:${percent}%"></i></div>
                </div>
                <span class="status ${css}">${label}</span>
            </div>`;
    }).join('');
}

// --- CHART.JS ---
function loadChart(orders) {
    const days = [];
    const counts = [];

    for (let i = 0; i < 7; i++) {
        const d = new Date(currentWeekStart);
        d.setDate(d.getDate() + i);
        days.push(d.toLocaleDateString('en-US', { weekday: 'short', day: 'numeric' }));
        
        const count = orders.filter(o => {
            if (!o.order_date) return false;
            const oDate = new Date(o.order_date);
            return oDate.getDate() === d.getDate() && oDate.getMonth() === d.getMonth();
        }).length;
        counts.push(count);
    }

    const end = new Date(currentWeekStart);
    end.setDate(end.getDate() + 6);
    const weekRangeEl = document.getElementById('weekRange');
    if (weekRangeEl) {
        weekRangeEl.textContent = currentWeekStart.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) + ' - ' + end.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    const nextBtn = document.getElementById('nextBtn');
    if (nextBtn) nextBtn.disabled = currentWeekStart >= getMonday(new Date());

    if (chart) chart.destroy();

    const canvas = document.getElementById('ordersChart');
    if (!canvas) return;

    const gradient = canvas.getContext('2d').createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, 'rgba(128, 0, 32, 0.35)');
    gradient.addColorStop(1, 'rgba(128, 0, 32, 0)');

    chart = new Chart(canvas, {
        type: 'line',
        data: {
            labels: days,
            datasets: [{
                label: 'Orders',
                data: counts,
                borderColor: '#800020',
                backgroundColor: gradient,
                pointBackgroundColor: '#800020',
                pointRadius: 4,
                borderWidth: 2,
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            animation: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });
}

// --- RECENT ORDERS ---
function showRecentOrders(orders, drivers) {
    const recent = [...orders].sort((a, b) => new Date(b.order_date) - new Date(a.order_date)).slice(0, 6);
    const table = document.getElementById('recentOrders');
    if (!table) return;
    
    table.innerHTML = recent.map(o => {
        const driver = drivers.find(d => d.driver_id === o.driver_id);
        const driverName = driver ? driver.name : '-';
        
        return `
            <tr>
                <td>#${o.order_id}</td>
                <td>${escapeHtml(o.customer_code || 'Retailer')}</td>
                <td>${o.items ? o.items.length : 0} items</td>
                <td>₱${Number(o.total_amount || 0).toFixed(2)}</td>
                <td>${escapeHtml(driverName)}</td>
                <td><span class="status ${o.order_status}">${o.order_status}</span></td>
            </tr>
        `;
    }).join('') || '<tr><td colspan="6">No orders yet</td></tr>';
}

// --- NAVIGATION BUTTONS ---
function prevWeek() {
    currentWeekStart.setDate(currentWeekStart.getDate() - 7);
    refreshPage(); 
}

function nextWeek() {
    currentWeekStart.setDate(currentWeekStart.getDate() + 7);
    refreshPage();
}

// --- INITIAL LOAD ---
document.addEventListener('DOMContentLoaded', () => {
    refreshPage();
});
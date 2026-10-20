let chart = null;
let currentWeekStart = getMonday(new Date());

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
    
    const orders = await getOrders();
    const products = await getProducts();
    const drivers = await getDrivers();

    if (!orders || !products || !drivers) return;

    
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

    
    const lowStock = products.filter(p => Number(p.stock_quantity) <= Number(p.reorder_level)).length;
    setText('c-lowstock', lowStock);
    setText('c-products', products.length + ' active products');

    
    const available = drivers.filter(d => d.status === 'available').length;
    setText('c-available', available);
    setText('c-drivers', 'of ' + drivers.length + ' drivers');

    
    const weekStart = currentWeekStart.getTime();
    const weekEnd = weekStart + (7 * 24 * 60 * 60 * 1000);
    const thisWeek = orders.filter(o => {
        const orderTime = new Date(o.order_date).getTime();
        return orderTime >= weekStart && orderTime < weekEnd;
    });
    setText('c-week', thisWeek.length);

    
    loadChart(orders);
    
    
    showRecentOrders(orders, drivers);
}

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
    document.getElementById('weekRange').textContent =
        currentWeekStart.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) + ' - ' +
        end.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });

    document.getElementById('nextBtn').disabled = currentWeekStart >= getMonday(new Date());

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

function showRecentOrders(orders, drivers) {
    const recent = [...orders].sort((a, b) => new Date(b.order_date) - new Date(a.order_date)).slice(0, 6);
    
    document.getElementById('recentOrders').innerHTML = recent.map(o => {
        const driver = drivers.find(d => d.driver_id === o.driver_id);
        const driverName = driver ? driver.name : '-';
        
        return `
            <tr>
                <td>#${escapeHtml(o.order_id)}</td>
                <td>${escapeHtml(o.customer_code || 'Retailer')}</td>
                <td>${escapeHtml(o.items ? o.items.length : 0)} items</td>
                <td>₱${Number(o.total_amount || 0).toFixed(2)}</td>
                <td>${escapeHtml(driverName)}</td>
                <td><span class="status ${o.order_status}">${escapeHtml(o.order_status)}</span></td>
            </tr>
        `;
    }).join('') || '<tr><td colspan="6">No orders yet</td></tr>';
}

function prevWeek() {
    currentWeekStart.setDate(currentWeekStart.getDate() - 7);
    refreshPage();
}

function nextWeek() {
    currentWeekStart.setDate(currentWeekStart.getDate() + 7);
    refreshPage();
}


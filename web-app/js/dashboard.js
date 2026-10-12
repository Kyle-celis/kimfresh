/**
 * Dashboard Controller.
 */

const admin = requireAdmin();
if (!admin) throw new Error('Not logged in');

let chart = null;
let currentWeekStart = getMonday(new Date());

function getMonday(d) {
    const date = new Date(d);
    const day = date.getDay();
    const diff = date.getDate() - day + (day === 0 ? -6 : 1);
    return new Date(date.setDate(diff));
}

function formatDateParam(d) {
    return d.toISOString().split('T')[0];
}

async function loadSummary() {
    const d = await apiGetSummary();
    document.getElementById('c-products').textContent = d.total_products;
    document.getElementById('c-drivers').textContent = d.total_drivers;
    document.getElementById('c-orders').textContent = d.total_orders;
    document.getElementById('c-pending').textContent = d.pending_orders;
    document.getElementById('c-approved').textContent = d.approved_orders;
    document.getElementById('c-rejected').textContent = d.rejected_orders;
    document.getElementById('c-lowstock').textContent = d.low_stock;
    document.getElementById('c-week').textContent = d.this_week;
}

async function loadChart() {
    const weekStr = formatDateParam(currentWeekStart);
    const d = await apiGetOrdersPerWeek(weekStr);

    document.getElementById('weekLabel').textContent = `${d.week_start} to ${d.week_end}`;

    const today = new Date();
    const currentMonday = getMonday(today);
    document.getElementById('nextBtn').disabled = currentWeekStart >= currentMonday;

    if (chart) chart.destroy();

    chart = new Chart(document.getElementById('ordersChart'), {
        type: 'bar',
        data: {
            labels: d.days,
            datasets: [{
                label: 'Orders',
                data: d.counts,
                backgroundColor: '#d32f2f'
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } }
        }
    });
}

async function loadRecentOrders() {
    const data = await apiGetRecentOrders();
    const list = document.getElementById('recentOrders');
    list.innerHTML = '';
    if (!Array.isArray(data)) {
        list.innerHTML = '<tr><td colspan="6">No data</td></tr>';
        return;
    }
    data.forEach(o => {
        list.innerHTML += `<tr>
            <td>${escapeHtml(o.order_id)}</td>
            <td>${escapeHtml(o.customer || '-')}</td>
            <td>${escapeHtml(o.product || '-')}</td>
            <td>${escapeHtml(o.qty || 0)}</td>
            <td>${escapeHtml(o.total_amount)}</td>
            <td>${escapeHtml(o.order_status)}</td>
        </tr>`;
    });
}

function prevWeek() {
    currentWeekStart.setDate(currentWeekStart.getDate() - 7);
    loadChart();
}

function nextWeek() {
    currentWeekStart.setDate(currentWeekStart.getDate() + 7);
    loadChart();
}

// Load all
loadSummary();
loadChart();
loadRecentOrders();

// Auto-refresh
setInterval(() => {
    loadSummary();
    loadChart();
    loadRecentOrders();
}, 10000);



const MOCK_DATA = {
    login: {
        success: true,
        token: 'mock-token-12345',
        user_id: 1,
        name: 'Admin User',
        email: 'admin@kimfresh.com',
        role: 'admin'
    },
    summary: {
        total_products: 5,
        total_drivers: 3,
        total_orders: 25,
        pending_orders: 5,
        approved_orders: 15,
        rejected_orders: 5,
        low_stock: 2,
        this_week: 8
    },
    orders: [
        {
            order_id: 25,
            customer_code: '942478',
            order_date: '2026-10-02T10:30:00',
            total_amount: '350.00',
            order_status: 'pending',
            temperature: '4.5',
            humidity: '88.0',
            is_alert: false,
            items: [
                { product_name: 'Baechu Kimchi', quantity: 1, subtotal: '175.00', sku: 'KIM-001' },
                { product_name: 'Kkakdugi', quantity: 1, subtotal: '120.00', sku: 'KIM-002' }
            ]
        },
        {
            order_id: 24,
            customer_code: '789203',
            order_date: '2026-10-02T09:15:00',
            total_amount: '500.00',
            order_status: 'approved',
            temperature: '3.2',
            humidity: '85.0',
            is_alert: false,
            items: [
                { product_name: 'Oi Sobagi', quantity: 2, subtotal: '260.00', sku: 'KIM-003' }
            ]
        },
        {
            order_id: 23,
            customer_code: '942478',
            order_date: '2026-10-01T18:00:00',
            total_amount: '175.00',
            order_status: 'delivered',
            temperature: '12.5',
            humidity: '92.0',
            is_alert: true,
            items: [
                { product_name: 'Baechu Kimchi', quantity: 1, subtotal: '175.00', sku: 'KIM-001' }
            ]
        }
    ],
    drivers: [
        {
            driver_id: 1,
            name: 'Test Driver',
            email: 'driver@kimfresh.com',
            phone: '09123456789',
            license_number: 'LIC-001',
            status: 'available',
            assigned_orders: 'Order #23'
        },
        {
            driver_id: 2,
            name: 'Juan Driver',
            email: 'juan@kimfresh.com',
            phone: '09111111111',
            license_number: 'LIC-002',
            status: 'available',
            assigned_orders: null
        },
        {
            driver_id: 3,
            name: 'Pedro Driver',
            email: 'pedro@kimfresh.com',
            phone: '09222222222',
            license_number: 'LIC-003',
            status: 'available',
            assigned_orders: null
        }
    ],
    products: [
        { product_id: 1, name: 'Baechu Kimchi', sku: 'KIM-001', unit_price: '175.00', stock_quantity: 50, reorder_level: 10, status: 'available' },
        { product_id: 2, name: 'Kkakdugi', sku: 'KIM-002', unit_price: '120.00', stock_quantity: 50, reorder_level: 8, status: 'available' },
        { product_id: 3, name: 'Oi Sobagi', sku: 'KIM-003', unit_price: '130.00', stock_quantity: 50, reorder_level: 15, status: 'available' },
        { product_id: 4, name: 'Dongchimi', sku: 'KIM-004', unit_price: '140.00', stock_quantity: 5, reorder_level: 10, status: 'available' },
        { product_id: 5, name: 'Pa Kimchi', sku: 'KIM-005', unit_price: '160.00', stock_quantity: 30, reorder_level: 8, status: 'available' }
    ],
    recent_orders: [
        { order_id: 25, customer: '942478', product: 'Baechu Kimchi, Kkakdugi', qty: 2, total_amount: '350.00', order_status: 'pending' },
        { order_id: 24, customer: '789203', product: 'Oi Sobagi', qty: 2, total_amount: '500.00', order_status: 'approved' },
        { order_id: 23, customer: '942478', product: 'Baechu Kimchi', qty: 1, total_amount: '175.00', order_status: 'delivered' }
    ],
    orders_per_week: {
        days: ['2026-09-28', '2026-09-29', '2026-09-30', '2026-10-01', '2026-10-02', '2026-10-03', '2026-10-04'],
        counts: [2, 5, 3, 4, 1, 0, 0],
        week_start: '2026-09-28',
        week_end: '2026-10-04'
    }
};

const originalFetch = window.fetch;
window.fetch = function(url, options) {
    const urlStr = typeof url === 'string' ? url : url.toString();

    console.log('[MOCK] Intercepted:', urlStr);

    if (urlStr.includes('/api/login')) {
        return Promise.resolve(new Response(JSON.stringify(MOCK_DATA.login), { status: 200 }));
    }
    if (urlStr.includes('/api/dashboard/summary')) {
        return Promise.resolve(new Response(JSON.stringify(MOCK_DATA.summary), { status: 200 }));
    }
    if (urlStr.includes('/api/dashboard/recent-orders')) {
        return Promise.resolve(new Response(JSON.stringify(MOCK_DATA.recent_orders), { status: 200 }));
    }
    if (urlStr.includes('/api/dashboard/orders-per-week')) {
        return Promise.resolve(new Response(JSON.stringify(MOCK_DATA.orders_per_week), { status: 200 }));
    }
    if (urlStr.includes('/api/orders/all') || urlStr.match(/\/api\/orders\/\d+$/)) {
        return Promise.resolve(new Response(JSON.stringify(MOCK_DATA.orders), { status: 200 }));
    }
    if (urlStr.includes('/api/drivers')) {
        return Promise.resolve(new Response(JSON.stringify(MOCK_DATA.drivers), { status: 200 }));
    }
    if (urlStr.includes('/api/products/all')) {
        return Promise.resolve(new Response(JSON.stringify(MOCK_DATA.products), { status: 200 }));
    }
    if (urlStr.includes('/api/products')) {
        return Promise.resolve(new Response(JSON.stringify(MOCK_DATA.products.filter(p => p.status === 'available')), { status: 200 }));
    }

    
    return Promise.resolve(new Response(JSON.stringify({ success: true, message: 'Mock action' }), { status: 200 }));
};


if (!localStorage.getItem('admin')) {
    localStorage.setItem('admin', JSON.stringify(MOCK_DATA.login));
    localStorage.setItem('token', MOCK_DATA.login.token);
}

console.log('[MOCK] Design mode active. No backend needed.');

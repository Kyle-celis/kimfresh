let allProducts = [];

async function loadProducts() {
    const data = await getProducts();
    if (!data) return;

    allProducts = data;
    updateStats();
    applyFilter();
}

function updateStats() {
    const total = allProducts.length;
    const low = allProducts.filter(p => Number(p.stock_quantity) <= Number(p.reorder_level)).length;
    // If your backend has a status field for hidden products
    const hidden = allProducts.filter(p => p.status === 'hidden').length;

    document.getElementById('statTotal').textContent = total;
    document.getElementById('statLow').textContent = low;
    document.getElementById('statHidden').textContent = hidden;
}

function applyFilter() {
    const filter = document.getElementById('productFilter').value;
    const query = document.getElementById('searchBox').value.toLowerCase();
    
    let filtered = allProducts;

    if (filter === 'active') {
        filtered = filtered.filter(p => p.status !== 'hidden');
    } else if (filter === 'low') {
        filtered = filtered.filter(p => Number(p.stock_quantity) <= Number(p.reorder_level));
    }

    if (query) {
        filtered = filtered.filter(p => 
            p.name.toLowerCase().includes(query) || 
            (p.sku && p.sku.toLowerCase().includes(query))
        );
    }

    renderTable(filtered);
}

function renderTable(products) {
    const list = document.getElementById('productList');
    if (!list) return;

    if (products.length === 0) {
        list.innerHTML = '<tr><td colspan="6" style="text-align:center; padding: 30px;">No products found</td></tr>';
        return;
    }

    list.innerHTML = products.map(p => {
        const isLow = Number(p.stock_quantity) <= Number(p.reorder_level);
        const stockLabel = isLow ? 'Low Stock' : 'In Stock';
        const stockClass = isLow ? 'low-stock' : 'in-stock';
        
        return `
            <tr>
                <td>
                    <div class="product-name">${escapeHtml(p.name)}</div>
                    <div class="product-sku">${escapeHtml(p.sku || 'N/A')}</div>
                </td>
                <td class="price">₱${Number(p.unit_price).toFixed(2)}</td>
                <td>${p.stock_quantity} <span class="status ${stockClass}">${stockLabel}</span></td>
                <td>${p.reorder_level}</td>
                <td><span class="status ${p.status || 'available'}">${p.status || 'Available'}</span></td>
                <td>
                    <div class="actions">
                        <button class="edit-btn" onclick="editProduct(${p.product_id})">Edit</button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function editProduct(id) {
    alert('Edit feature coming soon for product: ' + id);
}

// Search listener
document.getElementById('searchBox')?.addEventListener('input', applyFilter);
document.getElementById('productFilter')?.addEventListener('change', applyFilter);

document.addEventListener('DOMContentLoaded', () => {
    loadProducts();
    setInterval(loadProducts, 5000);
});
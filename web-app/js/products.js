function getToken() {
    return localStorage.getItem('token');
}

let allProducts = [];
let editingId = null;

async function loadProducts() {
    try {
        const res = await fetch('/api/products/all', {
            headers: { 'Authorization': `Bearer ${getToken()}` }
        });
        
        if (res.status === 401 || res.status === 403) {
            window.location.href = 'login.html';
            return;
        }

        const data = await res.json();
        allProducts = data;
        
        updateStats();
        renderTable();
    } catch (err) {
        console.error("Error loading products:", err);
    }
}

function updateStats() {
    const total = allProducts.length;
    const low = allProducts.filter(p => Number(p.stock_quantity) <= Number(p.reorder_level) && p.status !== 'inactive').length;
    const hidden = allProducts.filter(p => p.status === 'inactive').length;

    document.getElementById('statTotal').textContent = total;
    document.getElementById('statLow').textContent = low;
    document.getElementById('statHidden').textContent = hidden;
}

function renderTable() {
    const list = document.getElementById('productList');
    if (!list) return;

    const filter = document.getElementById('productFilter').value;
    const query = document.getElementById('searchBox').value.toLowerCase().trim();

    let filtered = allProducts;

    // 1. Filter by Dropdown
    if (filter === 'active') {
        filtered = filtered.filter(p => p.status === 'available');
    } else if (filter === 'low') {
        // Show only active products where stock is <= reorder level
        filtered = filtered.filter(p => 
            p.status === 'available' && 
            Number(p.stock_quantity) <= Number(p.reorder_level)
        );
    }

    // 2. Filter by Search Box
    if (query) {
        filtered = filtered.filter(p => 
            (p.name || '').toLowerCase().includes(query) || 
            (p.sku || '').toLowerCase().includes(query)
        );
    }

    if (filtered.length === 0) {
        list.innerHTML = '<tr><td colspan="6" style="text-align:center; padding: 30px;">No products found</td></tr>';
        return;
    }

    list.innerHTML = filtered.map(p => {
        const isLow = Number(p.stock_quantity) <= Number(p.reorder_level);
        const stockLabel = isLow ? 'Low Stock' : 'In Stock';
        const stockClass = isLow ? 'low-stock' : 'in-stock';
        
        // YOUR DB USES 'inactive' FOR HIDDEN
        const isHidden = p.status === 'inactive';
        const statusLabel = isHidden ? 'Hidden' : 'Available';
        const statusClass = isHidden ? 'hidden' : 'available';

        const actionButton = isHidden 
            ? `<button class="restore-btn" onclick="toggleHide(${p.product_id}, 'restore')">Restore</button>`
            : `<button class="hide-btn" onclick="toggleHide(${p.product_id}, 'hide')">Hide</button>`;

        return `
            <tr>
                <td>
                    <div class="product-name">${escapeHtml(p.name)}</div>
                    <div class="product-sku">${escapeHtml(p.sku || 'N/A')}</div>
                </td>
                <td class="price">₱${Number(p.unit_price).toFixed(2)}</td>
                <td>${p.stock_quantity} <span class="status ${stockClass}">${stockLabel}</span></td>
                <td>${p.reorder_level}</td>
                <td><span class="status ${statusClass}">${statusLabel}</span></td>
                <td>
                    <div class="actions">
                        <button class="edit-btn" onclick="openForm(${p.product_id})">Edit</button>
                        ${actionButton}
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

// --- ADD / EDIT DIALOG LOGIC ---
function openForm(id) {
    editingId = id || null;
    
    const dialogTitle = document.querySelector('#addDialog h2');
    const dialogSub = document.querySelector('#addDialog .dialog-sub');
    const saveBtn = document.querySelector('#addDialog .primary-btn');
    
    if (editingId) {
        const p = allProducts.find(x => x.product_id === editingId);
        dialogTitle.textContent = "Edit Product";
        dialogSub.textContent = "Update the details of this item.";
        saveBtn.textContent = "Save Changes";
        
        document.getElementById('pName').value = p.name || '';
        document.getElementById('pSku').value = p.sku || '';
        document.getElementById('pPrice').value = p.unit_price || '';
        document.getElementById('pStock').value = p.stock_quantity || '';
        document.getElementById('pReorder').value = p.reorder_level || '';
    } else {
        dialogTitle.textContent = "Add Product";
        dialogSub.textContent = "Fill in the details of the item.";
        saveBtn.textContent = "Add Product";
        
        document.getElementById('pName').value = '';
        document.getElementById('pSku').value = '';
        document.getElementById('pPrice').value = '';
        document.getElementById('pStock').value = '';
        document.getElementById('pReorder').value = '';
    }
    
    document.getElementById('addDialog').showModal();
}

function closeForm() {
    document.getElementById('addDialog').close();
}

async function saveProduct() {
    const name = document.getElementById('pName').value.trim();
    const sku = document.getElementById('pSku').value.trim();
    const price = document.getElementById('pPrice').value;
    const stock = document.getElementById('pStock').value;
    const reorder = document.getElementById('pReorder').value;

    if (!name || !price || !stock || !reorder) {
        showToast("Please fill in all fields.");
        return;
    }

    const url = editingId ? `/api/products/${editingId}` : '/api/products';
    const method = editingId ? 'PUT' : 'POST';

    try {
        const res = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${getToken()}`
            },
            body: JSON.stringify({
                name: name,
                sku: sku,
                unit_price: price,
                stock_quantity: stock,
                reorder_level: reorder
            })
        });

        const data = await res.json();
        
        if (res.ok) {
            closeForm();
            loadProducts(); 
            showToast(editingId ? "Product updated!" : "Product added!");
        } else {
            showToast(data.message || "Failed to save product.");
        }
    } catch (err) {
        console.error(err);
        showToast("Error connecting to server.");
    }
}

async function toggleHide(id, action) {
    try {
        const res = await fetch(`/api/products/${id}/${action}`, {
            method: 'PUT',
            headers: { 'Authorization': `Bearer ${getToken()}` }
        });
        
        const data = await res.json();
        
        if (res.ok) {
            loadProducts(); 
            showToast(action === 'hide' ? "Product hidden!" : "Product restored!");
        } else {
            showToast(data.message || "Action failed.");
        }
    } catch (err) {
        console.error(err);
        showToast("Error connecting to server.");
    }
}

function showToast(msg) {
    const toast = document.getElementById('toast');
    if(!toast) return;
    toast.textContent = msg;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3000);
}

// --- EVENT LISTENERS ---
document.getElementById('searchBox')?.addEventListener('input', renderTable);
document.getElementById('productFilter')?.addEventListener('change', renderTable);

document.addEventListener('DOMContentLoaded', () => {
    loadProducts();
});
/**
 * Products Controller.
 */

const admin = requireAdmin();
if (!admin) throw new Error('Not logged in');

let allProducts = [];
let currentProductPage = 1;
const PRODUCTS_PER_PAGE = 10;

async function addProduct() {
    const body = {
        name: document.getElementById('pName').value,
        sku: document.getElementById('pSku').value,
        unit_price: document.getElementById('pPrice').value,
        stock_quantity: document.getElementById('pStock').value,
        reorder_level: document.getElementById('pReorder').value
    };
    const data = await apiAddProduct(body);
    document.getElementById('addMsg').textContent = data.message;
    loadProducts();
}

async function loadProducts() {
    const data = await apiGetProducts();
    allProducts = data;
    currentProductPage = 1;
    renderProducts(data);
}

async function loadAllProducts() {
    const data = await apiGetAllProducts();
    allProducts = data;
    currentProductPage = 1;
    renderProducts(data);
}

function renderProducts(products) {
    const totalPages = Math.max(1, Math.ceil(products.length / PRODUCTS_PER_PAGE));
    if (currentProductPage > totalPages) currentProductPage = totalPages;
    const start = (currentProductPage - 1) * PRODUCTS_PER_PAGE;
    const pageItems = products.slice(start, start + PRODUCTS_PER_PAGE);

    const list = document.getElementById('productList');
    list.innerHTML = '';
    if (pageItems.length === 0) {
        list.innerHTML = '<tr><td colspan="8">No products found</td></tr>';
    } else {
        pageItems.forEach(p => {
            const action = p.status === 'available'
                ? `<button onclick="hideProduct(${p.product_id})">Hide</button>`
                : `<button onclick="restoreProduct(${p.product_id})">Restore</button>`;
            list.innerHTML += `<tr>
                <td>${escapeHtml(p.product_id)}</td>
                <td>${escapeHtml(p.name)}</td>
                <td>${escapeHtml(p.sku)}</td>
                <td>${escapeHtml(p.unit_price)}</td>
                <td>${escapeHtml(p.stock_quantity || 0)}</td>
                <td>${escapeHtml(p.reorder_level)}</td>
                <td>${escapeHtml(p.status)}</td>
                <td>${action}</td>
            </tr>`;
        });
    }

    renderProductPagination(totalPages);
    window._currentProducts = products;
}

function renderProductPagination(totalPages) {
    const el = document.getElementById('productPagination');
    if (totalPages <= 1) { el.innerHTML = ''; return; }
    let html = '';
    if (currentProductPage > 1) html += `<button onclick="goToProductPage(${currentProductPage - 1})">◀ Prev</button>`;
    for (let i = 1; i <= totalPages; i++) {
        html += i === currentProductPage
            ? `<button class="active">${i}</button>`
            : `<button onclick="goToProductPage(${i})">${i}</button>`;
    }
    if (currentProductPage < totalPages) html += `<button onclick="goToProductPage(${currentProductPage + 1})">Next ▶</button>`;
    el.innerHTML = html;
}

function goToProductPage(page) {
    currentProductPage = page;
    renderProducts(window._currentProducts || []);
}

function handleFilter() {
    currentProductPage = 1;
    document.getElementById('productFilter').value === 'all' ? loadAllProducts() : loadProducts();
}

async function hideProduct(id) {
    if (!confirm('Hide this product?')) return;
    const data = await apiHideProduct(id);
    alert(data.message);
    loadProducts();
}

async function restoreProduct(id) {
    const data = await apiRestoreProduct(id);
    alert(data.message);
    loadAllProducts();
}

function searchProducts() {
    const q = document.getElementById('searchBox').value.toLowerCase();
    const filtered = allProducts.filter(p =>
        p.name.toLowerCase().includes(q) ||
        p.sku.toLowerCase().includes(q) ||
        p.status.toLowerCase().includes(q)
    );
    currentProductPage = 1;
    renderProducts(filtered);
}

loadProducts();
setInterval(() => {
    const filter = document.getElementById('productFilter').value;
    filter === 'all' ? loadAllProducts() : loadProducts();
}, 10000);

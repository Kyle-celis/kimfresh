from flask import Flask, request, jsonify, send_from_directory
from flask_cors import CORS
from datetime import datetime, timedelta
from db_config import get_db_connection
import mysql.connector
import hashlib
import os

app = Flask(__name__)
CORS(app, resources={r"/api/*": {"origins": "*"}}, supports_credentials=True)

# ---------- HOME ----------
@app.route('/', methods=['GET'])
def home():
    return jsonify({"message": "KimFresh API is running!"})

# ---------- LOGIN ----------
import hashlib

@app.route('/api/login', methods=['POST'])
def login():
    data = request.get_json()
    email = data.get('email')
    password = data.get('password')
    
    if not email or not password:
        return jsonify({"success": False, "message": "Email and password required"}), 400
    
    password_hash = hashlib.sha256(password.encode()).hexdigest()
    
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    
    # Try admin
    cursor.execute("SELECT admin_id, name, email FROM admin WHERE email = %s", (email,))
    user = cursor.fetchone()
    role = "admin"
    
    if not user:
        cursor.execute("SELECT retailer_id as user_id, name, email, password_hash FROM retailer WHERE email = %s", (email,))
        user = cursor.fetchone()
        role = "retailer"
        
    if not user:
        cursor.execute("SELECT driver_id as user_id, name, email, password_hash FROM driver WHERE email = %s", (email,))
        user = cursor.fetchone()
        role = "driver"
    
    cursor.close()
    conn.close()
    
    if not user:
        return jsonify({"success": False, "message": "User not found"}), 401
    
    # Check password (skip for admin for now, or set admin password)
    if role in ['retailer', 'driver']:
        stored_hash = user.get('password_hash')
        if stored_hash and stored_hash != password_hash:
            return jsonify({"success": False, "message": "Invalid password"}), 401
    
    return jsonify({
        "success": True,
        "user_id": user['admin_id'] if role == 'admin' else user['user_id'],
        "name": user['name'],
        "email": user['email'],
        "role": role
    })

# ---------- GET ALL ACTIVE PRODUCTS ----------
@app.route('/api/products', methods=['GET'])
def get_products():
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT * FROM product WHERE status = 'available' ORDER BY product_id ASC")
    products = cursor.fetchall()
    cursor.close()
    conn.close()
    return jsonify(products)

# ---------- GET ALL PRODUCTS (INCLUDING INACTIVE) ----------
@app.route('/api/products/all', methods=['GET'])
def get_all_products():
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT * FROM product ORDER BY product_id ASC")
    products = cursor.fetchall()
    cursor.close()
    conn.close()
    return jsonify(products)

# ---------- GET PRODUCT BY ID ----------
@app.route('/api/products/<int:product_id>', methods=['GET'])
def get_product(product_id):
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT * FROM product WHERE product_id = %s", (product_id,))
    product = cursor.fetchone()
    cursor.close()
    conn.close()
    
    if product:
        return jsonify(product)
    return jsonify({"message": "Product not found"}), 404

# ---------- ADD PRODUCT (AUTO SKU) ----------
@app.route('/api/products', methods=['POST'])
def add_product():
    data = request.get_json()
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    
    name = data.get('name')
    sku = data.get('sku')
    
    if not name and not sku:
        return jsonify({"success": False, "message": "Name or SKU required"}), 400
    
    try:
        existing = None
        
        if sku:
            cursor.execute("SELECT * FROM product WHERE sku = %s", (sku,))
            existing = cursor.fetchone()
        
        if not existing and name:
            cursor.execute("SELECT * FROM product WHERE name = %s", (name,))
            existing = cursor.fetchone()
        
        if existing:
            new_name = name if name else existing['name']
            new_sku = sku if sku else existing['sku']
            new_price = data.get('unit_price') if data.get('unit_price') not in [None, ''] else existing['unit_price']
            new_stock = data.get('reorder_level') if data.get('reorder_level') not in [None, ''] else existing['reorder_level']
            
            cursor.execute(
                "UPDATE product SET name = %s, sku = %s, unit_price = %s, reorder_level = %s, status = 'available' WHERE product_id = %s",
                (new_name, new_sku, new_price, new_stock, existing['product_id'])
            )
            conn.commit()
            cursor.close()
            conn.close()
            return jsonify({"success": True, "message": f"Product '{new_name}' updated"})
        else:
            if not sku:
                cursor.execute("SELECT COUNT(*) as count FROM product")
                count = cursor.fetchone()['count'] + 1
                sku = f"KIM-{count:03d}"
            
            price = data.get('unit_price') if data.get('unit_price') not in [None, ''] else 0
            stock = data.get('reorder_level') if data.get('reorder_level') not in [None, ''] else 5
            
            cursor.execute(
                "INSERT INTO product (name, sku, description, unit_price, reorder_level, stock_quantity, status) VALUES (%s, %s, %s, %s, %s, %s, 'available')",
                (name, sku, '', price, stock, stock)
            )
            conn.commit()
            cursor.close()
            conn.close()
            return jsonify({"success": True, "message": f"Product added with SKU {sku}"})
    except mysql.connector.Error as err:
        conn.rollback()
        cursor.close()
        conn.close()
        return jsonify({"success": False, "message": str(err)}), 400

# ---------- UPDATE PRODUCT ----------
@app.route('/api/products/<int:product_id>', methods=['PUT'])
def update_product(product_id):
    data = request.get_json()
    conn = get_db_connection()
    cursor = conn.cursor()
    
    try:
        cursor.execute(
            "UPDATE product SET name = %s, sku = %s, unit_price = %s, reorder_level = %s WHERE product_id = %s",
            (data['name'], data['sku'], data['unit_price'], data['reorder_level'], product_id)
        )
        conn.commit()
        cursor.close()
        conn.close()
        return jsonify({"success": True, "message": "Product updated"})
    except mysql.connector.Error as err:
        conn.rollback()
        cursor.close()
        conn.close()
        return jsonify({"success": False, "message": str(err)}), 400

# ---------- SOFT DELETE ----------
@app.route('/api/products/<int:product_id>/hide', methods=['PUT'])
def hide_product(product_id):
    conn = get_db_connection()
    cursor = conn.cursor()
    try:
        cursor.execute("UPDATE product SET status = 'inactive' WHERE product_id = %s", (product_id,))
        conn.commit()
        cursor.close()
        conn.close()
        return jsonify({"success": True, "message": f"Product {product_id} hidden"})
    except Exception as e:
        conn.rollback()
        cursor.close()
        conn.close()
        return jsonify({"success": False, "message": str(e)}), 400

# ---------- RESTORE ----------
@app.route('/api/products/<int:product_id>/restore', methods=['PUT'])
def restore_product(product_id):
    conn = get_db_connection()
    cursor = conn.cursor()
    try:
        cursor.execute("UPDATE product SET status = 'available' WHERE product_id = %s", (product_id,))
        conn.commit()
        cursor.close()
        conn.close()
        return jsonify({"success": True, "message": f"Product {product_id} restored"})
    except Exception as e:
        conn.rollback()
        cursor.close()
        conn.close()
        return jsonify({"success": False, "message": str(e)}), 400

# ---------- PLACE ORDER ----------
@app.route('/api/orders', methods=['POST'])
def place_order():
    data = request.get_json()
    retailer_id = data.get('retailer_id')
    items = data.get('items', [])
    
    if not retailer_id or not items:
        return jsonify({"success": False, "message": "Missing retailer_id or items"}), 400
    
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    
    try:
        total = 0
        for item in items:
            cursor.execute(
                "SELECT product_id, name, unit_price, stock_quantity FROM product WHERE product_id = %s",
                (item['product_id'],)
            )
            product = cursor.fetchone()
            
            if not product:
                return jsonify({"success": False, "message": f"Product {item['product_id']} not found"}), 400
            
            if product['stock_quantity'] < item['quantity']:
                return jsonify({
                    "success": False,
                    "message": f"Not enough stock for {product['name']}. Available: {product['stock_quantity']}"
                }), 400
            
            total += product['unit_price'] * item['quantity']
        
        cursor.execute(
            "INSERT INTO `order` (retailer_id, order_date, total_amount, order_status) VALUES (%s, NOW(), %s, 'pending')",
            (retailer_id, total)
        )
        order_id = cursor.lastrowid
        
        for item in items:
            cursor.execute("SELECT unit_price, stock_quantity FROM product WHERE product_id = %s", (item['product_id'],))
            product = cursor.fetchone()
            unit_price = product['unit_price']
            subtotal = unit_price * item['quantity']
            
            cursor.execute(
                "INSERT INTO order_item (order_id, product_id, quantity, unit_price, subtotal) VALUES (%s, %s, %s, %s, %s)",
                (order_id, item['product_id'], item['quantity'], unit_price, subtotal)
            )
            
            new_stock = product['stock_quantity'] - item['quantity']
            cursor.execute(
                "UPDATE product SET stock_quantity = %s WHERE product_id = %s",
                (new_stock, item['product_id'])
            )
        
        conn.commit()
        cursor.close()
        conn.close()
        
        return jsonify({
            "success": True,
            "order_id": order_id,
            "total": total,
            "message": "Order placed successfully"
        })
        
    except Exception as e:
        conn.rollback()
        cursor.close()
        conn.close()
        return jsonify({"success": False, "message": str(e)}), 500

# ---------- GET ORDERS BY RETAILER ----------
@app.route('/api/orders/<int:retailer_id>', methods=['GET'])
def get_orders(retailer_id):
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("""
        SELECT 
            r.customer_code,
            o.order_id,
            o.order_date,
            o.total_amount,
            o.order_status,
            oi.order_item_id,
            oi.quantity,
            oi.unit_price,
            oi.subtotal,
            p.name AS product_name,
            p.sku
        FROM `order` o
        LEFT JOIN order_item oi ON o.order_id = oi.order_id
        LEFT JOIN product p ON oi.product_id = p.product_id
        LEFT JOIN retailer r ON o.retailer_id = r.retailer_id
        WHERE o.retailer_id = %s
        ORDER BY o.order_date DESC
    """, (retailer_id,))
    orders = cursor.fetchall()
    cursor.close()
    conn.close()
    return jsonify(orders)

# ---------- GET ALL ORDERS ----------
@app.route('/api/orders/all', methods=['GET'])
def get_all_orders():
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("""
        SELECT 
            r.customer_code,
            o.order_id,
            o.order_date,
            o.total_amount,
            o.order_status,
            oi.quantity,
            oi.unit_price,
            oi.subtotal,
            p.name AS product_name,
            p.sku
        FROM `order` o
        LEFT JOIN order_item oi ON o.order_id = oi.order_id
        LEFT JOIN product p ON oi.product_id = p.product_id
        LEFT JOIN retailer r ON o.retailer_id = r.retailer_id
        ORDER BY o.order_date DESC
    """)
    orders = cursor.fetchall()
    cursor.close()
    conn.close()
    return jsonify(orders)

# ---------- UPDATE ORDER STATUS ----------
@app.route('/api/orders/<int:order_id>/status', methods=['PUT'])
def update_order_status(order_id):
    data = request.get_json()
    new_status = data.get('status')
    
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute("UPDATE `order` SET order_status = %s WHERE order_id = %s", (new_status, order_id))
    conn.commit()
    cursor.close()
    conn.close()
    return jsonify({"success": True, "message": f"Order {order_id} set to {new_status}"})

# ---------- GET ALL DRIVERS ----------
@app.route('/api/drivers', methods=['GET'])
def get_drivers():
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("""
        SELECT 
            d.driver_id,
            d.name,
            d.email,
            d.phone,
            d.license_number,
            d.status,
            GROUP_CONCAT(CONCAT('Order #', dl.order_id) SEPARATOR ', ') AS assigned_orders
        FROM driver d
        LEFT JOIN delivery dl ON d.driver_id = dl.driver_id
        GROUP BY d.driver_id
        ORDER BY d.driver_id ASC
    """)
    drivers = cursor.fetchall()
    cursor.close()
    conn.close()
    return jsonify(drivers)

# ---------- ADD DRIVER ----------
@app.route('/api/drivers', methods=['POST'])
def add_driver():
    data = request.get_json()
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    
    name = data.get('name')
    email = data.get('email')
    
    if not name:
        return jsonify({"success": False, "message": "Name is required"}), 400
    
    try:
        if email:
            cursor.execute("SELECT driver_id FROM driver WHERE email = %s", (email,))
            existing = cursor.fetchone()
            if existing:
                cursor.execute(
                    "UPDATE driver SET name = %s, phone = %s, license_number = %s WHERE driver_id = %s",
                    (name, data.get('phone', ''), data.get('license_number', ''), existing['driver_id'])
                )
                conn.commit()
                cursor.close()
                conn.close()
                return jsonify({"success": True, "message": f"Driver '{name}' updated"})
        
        cursor.execute(
            "INSERT INTO driver (name, email, phone, license_number, status) VALUES (%s, %s, %s, %s, 'available')",
            (name, email, data.get('phone', ''), data.get('license_number', ''))
        )
        conn.commit()
        cursor.close()
        conn.close()
        return jsonify({"success": True, "message": f"Driver '{name}' added"})
    except mysql.connector.Error as err:
        conn.rollback()
        cursor.close()
        conn.close()
        return jsonify({"success": False, "message": str(err)}), 400

# ---------- ASSIGN ORDER TO DRIVER ----------
@app.route('/api/drivers/<int:driver_id>/assign', methods=['POST'])
def assign_order_to_driver(driver_id):
    data = request.get_json()
    order_id = data.get('order_id')
    
    if not order_id:
        return jsonify({"success": False, "message": "Order ID required"}), 400
    
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    
    cursor.execute("SELECT order_id, order_status FROM `order` WHERE order_id = %s", (order_id,))
    order = cursor.fetchone()
    if not order:
        cursor.close()
        conn.close()
        return jsonify({"success": False, "message": f"Order #{order_id} does not exist"}), 400
    
    if order['order_status'] != 'approved':
        cursor.close()
        conn.close()
        return jsonify({"success": False, "message": f"Order #{order_id} is not approved yet. Current status: {order['order_status']}"}), 400
    
    cursor.execute("SELECT delivery_id FROM delivery WHERE order_id = %s", (order_id,))
    existing = cursor.fetchone()
    if existing:
        cursor.close()
        conn.close()
        return jsonify({"success": False, "message": f"Order #{order_id} already assigned"}), 400
    
    try:
        cursor.execute(
            "INSERT INTO delivery (order_id, driver_id, delivery_status) VALUES (%s, %s, 'assigned')",
            (order_id, driver_id)
        )
        conn.commit()
        cursor.close()
        conn.close()
        return jsonify({"success": True, "message": f"Order #{order_id} assigned to driver {driver_id}"})
    except Exception as e:
        conn.rollback()
        cursor.close()
        conn.close()
        return jsonify({"success": False, "message": str(e)}), 400

# ---------- SERVE WEB FILES ----------

WEB_DIR = os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', 'web-app')

@app.route('/app/<path:filename>')
def serve_web(filename):
    return send_from_directory(WEB_DIR, filename)

    # ---------- DASHBOARD SUMMARY ----------
@app.route('/api/dashboard/summary', methods=['GET'])
def dashboard_summary():
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    
    cursor.execute("SELECT COUNT(*) AS count FROM product WHERE status='available'")
    total_products = cursor.fetchone()['count']
    
    cursor.execute("SELECT COUNT(*) AS count FROM driver WHERE status='available'")
    total_drivers = cursor.fetchone()['count']
    
    cursor.execute("SELECT COUNT(*) AS count FROM `order`")
    total_orders = cursor.fetchone()['count']
    
    cursor.execute("SELECT COUNT(*) AS count FROM `order` WHERE order_status='pending'")
    pending_orders = cursor.fetchone()['count']
    
    cursor.execute("SELECT COUNT(*) AS count FROM `order` WHERE order_status='approved'")
    approved_orders = cursor.fetchone()['count']
    
    cursor.execute("SELECT COUNT(*) AS count FROM `order` WHERE order_status='rejected'")
    rejected_orders = cursor.fetchone()['count']
    
    cursor.execute("SELECT COUNT(*) AS count FROM product WHERE stock_quantity <= reorder_level AND status='available'")
    low_stock = cursor.fetchone()['count']
    
    cursor.execute("SELECT COUNT(*) AS count FROM `order` WHERE order_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)")
    this_week = cursor.fetchone()['count']
    
    cursor.close()
    conn.close()
    
    return jsonify({
        "total_products": total_products,
        "total_drivers": total_drivers,
        "total_orders": total_orders,
        "pending_orders": pending_orders,
        "approved_orders": approved_orders,
        "rejected_orders": rejected_orders,
        "low_stock": low_stock,
        "this_week": this_week
    })

# ---------- ORDERS PER WEEK (7 DAYS STARTING FROM week_start) ----------
@app.route('/api/dashboard/orders-per-week', methods=['GET'])
def orders_per_week():
    week_start = request.args.get('week_start')
    
    if not week_start:
        week_start = (datetime.now() - timedelta(days=datetime.now().weekday())).strftime('%Y-%m-%d')
    
    start_date = datetime.strptime(week_start, '%Y-%m-%d')
    end_date = start_date + timedelta(days=6)
    
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("""
        SELECT 
            DATE(order_date) AS day,
            COUNT(*) AS count
        FROM `order`
        WHERE DATE(order_date) >= %s AND DATE(order_date) <= %s
        GROUP BY DATE(order_date)
        ORDER BY day ASC
    """, (week_start, end_date.strftime('%Y-%m-%d')))
    rows = cursor.fetchall()
    cursor.close()
    conn.close()
    
    days = []
    counts = []
    for i in range(7):
        d = (start_date + timedelta(days=i)).strftime('%Y-%m-%d')
        days.append(d)
        found = next((r for r in rows if str(r['day']) == d), None)
        counts.append(found['count'] if found else 0)
    
    return jsonify({
        "days": days,
        "counts": counts,
        "week_start": week_start,
        "week_end": end_date.strftime('%Y-%m-%d')
    })



# ---------- RETAILER REGISTER ----------
@app.route('/api/retailer/register', methods=['POST'])
def retailer_register():
    data = request.get_json()
    name = data.get('name')
    email = data.get('email')
    password = data.get('password')
    phone = data.get('phone', '')
    address = data.get('address', '')
    
    if not name or not email or not password:
        return jsonify({"success": False, "message": "Name, email, and password required"}), 400
    
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    
    cursor.execute("SELECT retailer_id FROM retailer WHERE email = %s", (email,))
    if cursor.fetchone():
        cursor.close()
        conn.close()
        return jsonify({"success": False, "message": "Email already registered"}), 400
    
    import random
    while True:
        code = str(random.randint(100000, 999999))
        cursor.execute("SELECT retailer_id FROM retailer WHERE customer_code = %s", (code,))
        if not cursor.fetchone():
            break
    
    password_hash = hashlib.sha256(password.encode()).hexdigest()
    
    try:
        cursor.execute(
            "INSERT INTO retailer (name, email, phone, password_hash, address, customer_code) VALUES (%s, %s, %s, %s, %s, %s)",
            (name, email, phone, password_hash, address, code)
        )
        conn.commit()
        retailer_id = cursor.lastrowid
        cursor.close()
        conn.close()
        return jsonify({
            "success": True,
            "retailer_id": retailer_id,
            "customer_code": code,
            "name": name,
            "message": f"Registered! Your customer code: {code}"
        })
    except mysql.connector.Error as err:
        conn.rollback()
        cursor.close()
        conn.close()
        return jsonify({"success": False, "message": str(err)}), 400


# ---------- RUN ----------
if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=False)

"""Order management routes."""
from flask import Blueprint, request, jsonify
from db_config import get_db_connection
from config import (
    ORDER_STATUS_PENDING,
    ORDER_STATUS_APPROVED,
    ORDER_STATUS_REJECTED,
    ORDER_STATUS_DELIVERED,
)

orders_bp = Blueprint('orders', __name__)


@orders_bp.route('/api/orders', methods=['POST'])
@orders_bp.route('/api/orders', methods=['POST'])
def place_order():
    """
    Place a new order.
    
    Validates stock for all items before creating the order.
    Reduces stock quantity after successful creation.
    
    Performance note: Fetches all products in a single query (instead of
    one per item) to avoid the N+1 query problem.
    """
    data = request.get_json()
    retailer_id = data.get('retailer_id')
    items = data.get('items', [])

    if not retailer_id or not items:
        return jsonify({"success": False, "message": "Missing retailer_id or items"}), 400

    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)

    try:
        # --- FETCH ALL PRODUCTS IN ONE QUERY (fixes N+1) ---
        product_ids = [item['product_id'] for item in items]
        placeholders = ','.join(['%s'] * len(product_ids))
        cursor.execute(
            f"SELECT product_id, name, unit_price, stock_quantity FROM product WHERE product_id IN ({placeholders})",
            tuple(product_ids)
        )
        products = {p['product_id']: p for p in cursor.fetchall()}

        # --- VALIDATE STOCK ---
        total = 0
        for item in items:
            product = products.get(item['product_id'])
            if not product:
                return jsonify({"success": False, "message": f"Product {item['product_id']} not found"}), 400
            if product['stock_quantity'] < item['quantity']:
                return jsonify({
                    "success": False,
                    "message": f"Not enough stock for {product['name']}. Available: {product['stock_quantity']}"
                }), 400
            total += product['unit_price'] * item['quantity']

        # --- CREATE ORDER HEADER ---
        cursor.execute(
            """INSERT INTO `order` 
               (retailer_id, order_date, total_amount, order_status) 
               VALUES (%s, NOW(), %s, %s)""",
            (retailer_id, total, ORDER_STATUS_PENDING)
        )
        order_id = cursor.lastrowid

        # --- CREATE ORDER ITEMS AND UPDATE STOCK ---
        order_items = []
        update_stocks = []

        for item in items:
            product = products[item['product_id']]
            unit_price = product['unit_price']
            subtotal = unit_price * item['quantity']
            order_items.append((
                order_id,
                item['product_id'],
                item['quantity'],
                unit_price,
                subtotal
            ))
            new_stock = product['stock_quantity'] - item['quantity']
            update_stocks.append((new_stock, item['product_id']))

        # Bulk insert all order items in one query
        cursor.executemany(
            """INSERT INTO order_item 
               (order_id, product_id, quantity, unit_price, subtotal) 
               VALUES (%s, %s, %s, %s, %s)""",
            order_items
        )

        # Bulk update all product stocks in one query
        cursor.executemany(
            "UPDATE product SET stock_quantity = %s WHERE product_id = %s",
            update_stocks
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

def _validate_stock(cursor, items):
    """Check if enough stock exists for all items. Returns total price or error."""
    total = 0
    for item in items:
        cursor.execute(
            "SELECT product_id, name, unit_price, stock_quantity FROM product WHERE product_id = %s",
            (item['product_id'],)
        )
        product = cursor.fetchone()

        if not product:
            return {'error': f"Product {item['product_id']} not found", 'total': 0}

        if product['stock_quantity'] < item['quantity']:
            return {
                'error': f"Not enough stock for {product['name']}. Available: {product['stock_quantity']}",
                'total': 0
            }

        total += product['unit_price'] * item['quantity']

    return {'error': None, 'total': total}


def _create_order_item(cursor, order_id, item):
    """Insert an order item and reduce its product stock."""
    cursor.execute(
        "SELECT unit_price, stock_quantity FROM product WHERE product_id = %s",
        (item['product_id'],)
    )
    product = cursor.fetchone()
    unit_price = product['unit_price']
    subtotal = unit_price * item['quantity']

    cursor.execute(
        """INSERT INTO order_item 
           (order_id, product_id, quantity, unit_price, subtotal) 
           VALUES (%s, %s, %s, %s, %s)""",
        (order_id, item['product_id'], item['quantity'], unit_price, subtotal)
    )

    new_stock = product['stock_quantity'] - item['quantity']
    cursor.execute(
        "UPDATE product SET stock_quantity = %s WHERE product_id = %s",
        (new_stock, item['product_id'])
    )


@orders_bp.route('/api/orders/<int:retailer_id>', methods=['GET'])
def get_orders(retailer_id):
    """Return all orders for a specific retailer. Requires auth."""
    requester_role = request.headers.get('X-User-Role')
    requester_id = request.headers.get('X-User-Id')

    if requester_role != 'admin':
        if requester_role != 'retailer' or str(retailer_id) != str(requester_id):
            return jsonify({"success": False, "message": "Unauthorized"}), 403

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


@orders_bp.route('/api/orders/all', methods=['GET'])
def get_all_orders():
    """Return all orders with sensor data for admin dashboard."""
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
            p.sku,
            sd.temperature,
            sd.humidity,
            sd.is_alert
        FROM `order` o
        LEFT JOIN order_item oi ON o.order_id = oi.order_id
        LEFT JOIN product p ON oi.product_id = p.product_id
        LEFT JOIN retailer r ON o.retailer_id = r.retailer_id
        LEFT JOIN delivery d ON o.order_id = d.order_id
        LEFT JOIN (
            SELECT delivery_id, MAX(sensor_id) AS latest_id
            FROM sensor_data
            GROUP BY delivery_id
        ) latest ON d.delivery_id = latest.delivery_id
        LEFT JOIN sensor_data sd ON sd.sensor_id = latest.latest_id
        ORDER BY o.order_date DESC
    """)
    orders = cursor.fetchall()
    cursor.close()
    conn.close()
    return jsonify(orders)


@orders_bp.route('/api/orders/<int:order_id>/status', methods=['PUT'])
def update_order_status(order_id):
    """Update the status of an order."""
    data = request.get_json()
    new_status = data.get('status')

    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute(
        "UPDATE `order` SET order_status = %s WHERE order_id = %s",
        (new_status, order_id)
    )
    conn.commit()
    cursor.close()
    conn.close()
    return jsonify({"success": True, "message": f"Order {order_id} set to {new_status}"})

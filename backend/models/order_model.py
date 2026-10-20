"""
Order model — all database queries for orders.
"""

from db_config import get_db_connection


def get_order_header(order_id):
    """Return order header row."""
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT * FROM `order` WHERE order_id = %s", (order_id,))
    row = cursor.fetchone()
    cursor.close()
    conn.close()
    return row


def get_orders_by_retailer(retailer_id):
    """Return all orders for a retailer with items."""
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
    rows = cursor.fetchall()
    cursor.close()
    conn.close()
    return rows

def get_all_orders():
    """Return all orders with items + latest sensor data."""
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)

    # NOTICE: dr.driver_id and dr.name are explicitly selected here
    cursor.execute("""
        SELECT 
            r.customer_code,
            o.order_id,
            o.order_date,
            o.total_amount,
            o.order_status,
            dr.driver_id,
            dr.name AS driver_name,
            sd.temperature,
            sd.humidity,
            sd.is_alert
        FROM `order` o
        LEFT JOIN retailer r ON o.retailer_id = r.retailer_id
        LEFT JOIN delivery d ON o.order_id = d.order_id
        LEFT JOIN driver dr ON d.driver_id = dr.driver_id
        LEFT JOIN (
            SELECT delivery_id, MAX(sensor_id) AS latest_id
            FROM sensor_data
            GROUP BY delivery_id
        ) latest ON d.delivery_id = latest.delivery_id
        LEFT JOIN sensor_data sd ON sd.sensor_id = latest.latest_id
        ORDER BY o.order_date DESC
    """)
    orders = cursor.fetchall()

    for order in orders:
        cursor.execute("""
            SELECT 
                oi.quantity,
                p.name AS product_name,
                p.sku
            FROM order_item oi
            LEFT JOIN product p ON oi.product_id = p.product_id
            WHERE oi.order_id = %s
        """, (order['order_id'],))
        order['items'] = cursor.fetchall()

    cursor.close()
    conn.close()
    return orders

def create_order(retailer_id, total):
    """Insert order header, return order_id."""
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute(
        "INSERT INTO `order` (retailer_id, order_date, total_amount, order_status) VALUES (%s, NOW(), %s, 'pending')",
        (retailer_id, total)
    )
    conn.commit()
    order_id = cursor.lastrowid
    cursor.close()
    conn.close()
    return order_id


def create_order_item(order_id, product_id, quantity, unit_price, subtotal):
    """Insert an order item."""
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute(
        "INSERT INTO order_item (order_id, product_id, quantity, unit_price, subtotal) VALUES (%s, %s, %s, %s, %s)",
        (order_id, product_id, quantity, unit_price, subtotal)
    )
    conn.commit()
    cursor.close()
    conn.close()


def update_order_status(order_id, status):
    """Update order status."""
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute("UPDATE `order` SET order_status = %s WHERE order_id = %s", (status, order_id))
    conn.commit()
    cursor.close()
    conn.close()


def get_products_by_ids(product_ids):
    """Return products matching a list of IDs."""
    if not product_ids:
        return []
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    placeholders = ','.join(['%s'] * len(product_ids))
    cursor.execute(
        f"SELECT * FROM product WHERE product_id IN ({placeholders})",
        tuple(product_ids)
    )
    rows = cursor.fetchall()
    cursor.close()
    conn.close()
    return rows


def reduce_stock(product_id, new_quantity):
    """Set product stock_quantity."""
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute("UPDATE product SET stock_quantity = %s WHERE product_id = %s", (new_quantity, product_id))
    conn.commit()
    cursor.close()
    conn.close()

def get_order_by_id(order_id):
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    
    cursor.execute("SELECT * FROM `order` WHERE order_id = %s", (order_id,))
    order = cursor.fetchone()
    
    if order:
        # 1. Get the items
        cursor.execute("""
            SELECT oi.quantity, oi.unit_price, oi.subtotal, p.name AS product_name 
            FROM order_item oi
            JOIN product p ON oi.product_id = p.product_id
            WHERE oi.order_id = %s
        """, (order_id,))
        order['items'] = cursor.fetchall()
        
        # 2. Get customer code
        cursor.execute("SELECT customer_code FROM retailer WHERE retailer_id = %s", (order['retailer_id'],))
        retailer = cursor.fetchone()
        if retailer:
            order['customer_code'] = retailer['customer_code']
            
        # 3. Get Temperature History (NEW)
        cursor.execute("""
            SELECT sd.temperature, sd.humidity, sd.reading_timestamp, sd.is_alert
            FROM sensor_data sd
            JOIN delivery d ON sd.delivery_id = d.delivery_id
            WHERE d.order_id = %s
            ORDER BY sd.reading_timestamp DESC
        """, (order_id,))
        order['sensor_data'] = cursor.fetchall()
            
    cursor.close()
    conn.close()
    return order
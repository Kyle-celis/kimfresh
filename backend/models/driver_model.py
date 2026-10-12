"""
Driver model — all database queries for drivers and deliveries.
"""

from db_config import get_db_connection


def get_all_drivers():
    """Return all drivers with assigned orders list."""
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
    rows = cursor.fetchall()
    cursor.close()
    conn.close()
    return rows


def get_driver_by_email(email):
    """Return driver row by email."""
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT * FROM driver WHERE email = %s", (email,))
    row = cursor.fetchone()
    cursor.close()
    conn.close()
    return row


def create_driver(name, email, phone, license_number):
    """Insert new driver."""
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute(
        "INSERT INTO driver (name, email, phone, license_number, status) VALUES (%s, %s, %s, %s, 'available')",
        (name, email, phone, license_number)
    )
    conn.commit()
    cursor.close()
    conn.close()


def update_driver(driver_id, name, phone, license_number):
    """Update driver fields."""
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute(
        "UPDATE driver SET name = %s, phone = %s, license_number = %s WHERE driver_id = %s",
        (name, phone, license_number, driver_id)
    )
    conn.commit()
    cursor.close()
    conn.close()


def update_driver_status(driver_id, status):
    """Update driver availability status."""
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute("UPDATE driver SET status = %s WHERE driver_id = %s", (status, driver_id))
    conn.commit()
    cursor.close()
    conn.close()


def get_delivery_by_order(order_id):
    """Return delivery row for an order."""
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT * FROM delivery WHERE order_id = %s", (order_id,))
    row = cursor.fetchone()
    cursor.close()
    conn.close()
    return row


def create_delivery(order_id, driver_id):
    """Insert new delivery assignment."""
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute(
        "INSERT INTO delivery (order_id, driver_id, delivery_status) VALUES (%s, %s, 'assigned')",
        (order_id, driver_id)
    )
    conn.commit()
    cursor.close()
    conn.close()


def get_driver_deliveries(driver_id):
    """Return all deliveries for a driver with items."""
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)

    cursor.execute("""
        SELECT 
            dl.delivery_id,
            dl.order_id,
            dl.delivery_status,
            r.name AS retailer_name,
            r.address AS retailer_address,
            r.phone AS retailer_phone
        FROM delivery dl
        LEFT JOIN `order` o ON dl.order_id = o.order_id
        LEFT JOIN retailer r ON o.retailer_id = r.retailer_id
        WHERE dl.driver_id = %s
        ORDER BY dl.delivery_id DESC
    """, (driver_id,))
    deliveries = cursor.fetchall()

    for d in deliveries:
        cursor.execute("""
            SELECT 
                oi.quantity,
                p.name AS product_name,
                p.sku
            FROM order_item oi
            LEFT JOIN product p ON oi.product_id = p.product_id
            WHERE oi.order_id = %s
        """, (d['order_id'],))
        d['items'] = cursor.fetchall()

    cursor.close()
    conn.close()
    return deliveries


def get_delivery_by_id(delivery_id):
    """Return delivery row."""
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT * FROM delivery WHERE delivery_id = %s", (delivery_id,))
    row = cursor.fetchone()
    cursor.close()
    conn.close()
    return row


def update_delivery_status(delivery_id, status):
    """Update delivery status."""
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute("UPDATE delivery SET delivery_status = %s WHERE delivery_id = %s", (status, delivery_id))
    conn.commit()
    cursor.close()
    conn.close()


def get_driver_sensor_history(driver_id):
    """Return last 100 sensor readings for a driver."""
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("""
        SELECT 
            sd.sensor_id,
            sd.delivery_id,
            sd.temperature,
            sd.humidity,
            sd.is_alert,
            sd.reading_timestamp
        FROM sensor_data sd
        JOIN delivery d ON sd.delivery_id = d.delivery_id
        WHERE d.driver_id = %s
        ORDER BY sd.reading_timestamp DESC
        LIMIT 100
    """, (driver_id,))
    rows = cursor.fetchall()
    cursor.close()
    conn.close()
    return rows

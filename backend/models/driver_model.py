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
            d.account_status,
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


def get_available_drivers():
    """Return drivers with status='available', ordered by ID."""
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT * FROM driver WHERE status = 'available' AND account_status = 'approved' ORDER BY driver_id ASC")
    rows = cursor.fetchall()
    cursor.close()
    conn.close()
    return rows


def get_last_assigned_driver_id():
    """Return driver_id of the most recent delivery, or 0."""
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute("SELECT driver_id FROM delivery ORDER BY delivery_id DESC LIMIT 1")
    row = cursor.fetchone()
    cursor.close()
    conn.close()
    return row[0] if row else 0


def auto_pick_driver():
    """
    Round-robin: pick next available driver after the last assigned one.

    Returns driver_id or None.
    """
    available = get_available_drivers()
    if not available:
        return None

    last_id = get_last_assigned_driver_id()

    # Find next driver with id > last_id
    for d in available:
        if d['driver_id'] > last_id:
            return d['driver_id']

    # Wrap around to first available
    return available[0]['driver_id']


def assign_delivery(order_id, driver_id):
    """Create delivery and mark driver on_delivery."""
    conn = get_db_connection()
    cursor = conn.cursor()

    cursor.execute(
        "INSERT INTO delivery (order_id, driver_id, delivery_status) VALUES (%s, %s, 'assigned')",
        (order_id, driver_id)
    )
    cursor.execute("UPDATE driver SET status = 'on_delivery' WHERE driver_id = %s", (driver_id,))
    conn.commit()
    cursor.close()
    conn.close()


def approve_driver(driver_id):
    """Set account_status to approved."""
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute(
        "UPDATE driver SET account_status = 'approved' WHERE driver_id = %s",
        (driver_id,)
    )
    conn.commit()
    cursor.close()
    conn.close()


def reject_driver(driver_id):
    """Set account_status to rejected and record time."""
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute(
        "UPDATE driver SET account_status = 'rejected', rejected_at = NOW() WHERE driver_id = %s",
        (driver_id,)
    )
    conn.commit()
    cursor.close()
    conn.close()


def delete_old_rejected_drivers():
    """Delete rejected drivers older than 7 days."""
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute(
        "DELETE FROM driver WHERE account_status = 'rejected' AND rejected_at < DATE_SUB(NOW(), INTERVAL 7 DAY)"
    )
    conn.commit()
    cursor.close()
    conn.close()

def get_driver_by_id(driver_id):
    """Return single driver row by ID (for profile view)."""
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("""
        SELECT driver_id, name, email, phone, license_number, status
        FROM driver WHERE driver_id = %s
    """, (driver_id,))
    row = cursor.fetchone()
    cursor.close()
    conn.close()
    return row


def reassign_delivery(order_id, old_driver_id, new_driver_id):
    """Reassign a delivery to a new driver. Frees old, marks new on_delivery."""
    conn = get_db_connection()
    cursor = conn.cursor()
    try:
        cursor.execute(
            "UPDATE delivery SET driver_id = %s WHERE order_id = %s",
            (new_driver_id, order_id)
        )
        cursor.execute(
            "UPDATE driver SET status = 'available' WHERE driver_id = %s",
            (old_driver_id,)
        )
        cursor.execute(
            "UPDATE driver SET status = 'on_delivery' WHERE driver_id = %s",
            (new_driver_id,)
        )
        conn.commit()
    except Exception:
        conn.rollback()
        raise
    finally:
        cursor.close()
        conn.close()


def get_order_delivery_with_driver(order_id):
    """Return delivery + driver name for an order."""
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("""
        SELECT 
            d.delivery_id,
            d.driver_id,
            d.delivery_status,
            dr.name AS driver_name
        FROM delivery d
        LEFT JOIN driver dr ON d.driver_id = dr.driver_id
        WHERE d.order_id = %s
    """, (order_id,))
    row = cursor.fetchone()
    cursor.close()
    conn.close()
    return row or {}
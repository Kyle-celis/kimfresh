"""Driver management routes."""
from flask import Blueprint, request, jsonify
from extensions import require_auth
from utils.validators import validate_name, validate_positive_int, validate_email
from db_config import get_db_connection
import mysql.connector

drivers_bp = Blueprint('drivers', __name__)


@drivers_bp.route('/api/drivers', methods=['GET'])
@require_auth(['admin'])
def get_drivers():
    """Return all drivers with their assigned orders."""
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


@drivers_bp.route('/api/drivers', methods=['POST'])
@require_auth(['admin'])
def add_driver():
    """
    Add a new driver or update an existing one.
    
    If email matches an existing driver, it is updated.
    Otherwise, a new driver is created.
    """
    data = request.get_json()
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)

    name = data.get('name')
    email = data.get('email')

    is_valid, err = validate_name(name, "Driver name")
    if not is_valid:
        return jsonify({"success": False, "message": err}), 400

    if email:
        is_valid, err = validate_email(email)
        if not is_valid:
            return jsonify({"success": False, "message": err}), 400

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


@drivers_bp.route('/api/drivers/<int:driver_id>/assign', methods=['POST'])
@require_auth(['admin'])
def assign_order_to_driver(driver_id):
    """
    Assign an order to a driver.
    
    Rules:
    - Order must exist.
    - Order must be approved.
    - Order must not already be assigned to another driver.
    """
    data = request.get_json()
    order_id = data.get('order_id')

    is_valid, err = validate_positive_int(order_id, "Order ID")
    if not is_valid:
        return jsonify({"success": False, "message": err}), 400
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


@drivers_bp.route('/api/driver/<int:driver_id>/deliveries', methods=['GET'])
@require_auth(['admin', 'driver'])
def get_driver_deliveries(driver_id):
    """Return all deliveries assigned to a specific driver."""
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("""
        SELECT 
            dl.delivery_id,
            dl.order_id,
            dl.delivery_status,
            r.name AS retailer_name,
            r.address AS retailer_address,
            r.phone AS retailer_phone,
            p.name AS product_name,
            oi.quantity
        FROM delivery dl
        LEFT JOIN `order` o ON dl.order_id = o.order_id
        LEFT JOIN retailer r ON o.retailer_id = r.retailer_id
        LEFT JOIN order_item oi ON o.order_id = oi.order_id
        LEFT JOIN product p ON oi.product_id = p.product_id
        WHERE dl.driver_id = %s
        ORDER BY dl.delivery_id DESC
    """, (driver_id,))
    deliveries = cursor.fetchall()
    cursor.close()
    conn.close()
    return jsonify(deliveries)


@drivers_bp.route('/api/deliveries/<int:delivery_id>/status', methods=['PUT'])
@require_auth(['driver'])
def update_delivery_status(delivery_id):
    """
    Update a delivery's status and sync the related order status.
    
    Delivery statuses: assigned, picked_up, in_transit, delivered.
    """
    data = request.get_json()
    new_status = data.get('delivery_status')

    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)

    cursor.execute("SELECT order_id FROM delivery WHERE delivery_id = %s", (delivery_id,))
    row = cursor.fetchone()
    if not row:
        cursor.close()
        conn.close()
        return jsonify({"success": False, "message": "Delivery not found"}), 404

    order_id = row['order_id']

    cursor.execute("UPDATE delivery SET delivery_status = %s WHERE delivery_id = %s", (new_status, delivery_id))

    order_status_map = {
        'assigned': 'approved',
        'picked_up': 'waiting_for_pickup',
        'in_transit': 'in_transit',
        'delivered': 'delivered'
    }
    new_order_status = order_status_map.get(new_status, 'approved')

    cursor.execute("UPDATE `order` SET order_status = %s WHERE order_id = %s", (new_order_status, order_id))

    conn.commit()
    cursor.close()
    conn.close()
    return jsonify({"success": True, "message": f"Delivery {delivery_id} set to {new_status}, order set to {new_order_status}"})

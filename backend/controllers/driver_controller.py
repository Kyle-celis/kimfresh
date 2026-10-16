"""
Driver controller — driver and delivery routes.
"""

from flask import Blueprint, request, jsonify
from extensions import require_auth
from utils.validators import validate_name, validate_positive_int, validate_email
from utils.logger import logger
from models import driver_model, order_model
from models.user_model import get_driver_account_status as get_driver_account_status_model

drivers_bp = Blueprint('drivers', __name__)


# =========================================================
# DRIVERS — LIST / CREATE
# =========================================================

@drivers_bp.route('/api/drivers', methods=['GET'])
@require_auth(['admin'])
def get_drivers():
    """Return all drivers."""
    return jsonify(driver_model.get_all_drivers())


@drivers_bp.route('/api/drivers', methods=['POST'])
@require_auth(['admin'])
def add_driver():
    """Add or update driver."""
    data = request.get_json()
    name = data.get('name')
    email = data.get('email')

    is_valid, err = validate_name(name, "Driver name")
    if not is_valid:
        return jsonify({"success": False, "message": err}), 400

    if email:
        is_valid, err = validate_email(email)
        if not is_valid:
            return jsonify({"success": False, "message": err}), 400

        existing = driver_model.get_driver_by_email(email)
        if existing:
            driver_model.update_driver(
                existing['driver_id'],
                name,
                data.get('phone', ''),
                data.get('license_number', '')
            )
            return jsonify({"success": True, "message": f"Driver '{name}' updated"})

    driver_model.create_driver(
        name, email, data.get('phone', ''), data.get('license_number', '')
    )
    return jsonify({"success": True, "message": f"Driver '{name}' added"})


# =========================================================
# DRIVER PROFILE — GET / UPDATE
# =========================================================

@drivers_bp.route('/api/driver/<int:driver_id>/profile', methods=['GET'])
@require_auth(['driver', 'admin'])
def get_driver_profile(driver_id):
    """Return driver profile info."""
    driver = driver_model.get_driver_by_id(driver_id)
    if not driver:
        return jsonify({"success": False, "message": "Driver not found"}), 404
    return jsonify(driver)


@drivers_bp.route('/api/driver/<int:driver_id>/profile', methods=['PUT'])
@require_auth(['driver', 'admin'])
def update_driver_profile(driver_id):
    """Update driver name, phone, license. Email and status not editable."""
    data = request.get_json() or {}
    name = (data.get('name') or '').strip()
    phone = (data.get('phone') or '').strip()
    license_number = (data.get('license_number') or '').strip()

    is_valid, err = validate_name(name, "Driver name")
    if not is_valid:
        return jsonify({"success": False, "message": err}), 400

    if not driver_model.get_driver_by_id(driver_id):
        return jsonify({"success": False, "message": "Driver not found"}), 404

    try:
        driver_model.update_driver(driver_id, name, phone, license_number)
    except Exception as e:
        logger.error(f"Failed to update driver {driver_id}: {e}")
        return jsonify({"success": False, "message": "Update failed"}), 400

    logger.info(f"Driver {driver_id} profile updated")
    return jsonify({"success": True, "message": "Profile updated"})


# =========================================================
# DRIVER ACCOUNT STATUS (for pending-approval polling)
# =========================================================

@drivers_bp.route('/api/driver/<int:driver_id>/account-status', methods=['GET'])
def get_driver_account_status(driver_id):
    """Return account_status for a driver (no auth — driver checks own)."""
    status = get_driver_account_status_model(driver_id)
    if status is None:
        return jsonify({"success": False, "message": "Driver not found"}), 404
    return jsonify({"success": True, "account_status": status})


# =========================================================
# DRIVER APPROVAL
# =========================================================

@drivers_bp.route('/api/drivers/<int:driver_id>/approve', methods=['POST'])
@require_auth(['admin'])
def approve_driver(driver_id):
    """Approve a pending driver account."""
    driver_model.approve_driver(driver_id)
    logger.info(f"Driver {driver_id} approved")
    return jsonify({"success": True, "message": f"Driver {driver_id} approved"})


@drivers_bp.route('/api/drivers/<int:driver_id>/reject', methods=['POST'])
@require_auth(['admin'])
def reject_driver(driver_id):
    """Reject a pending driver account."""
    driver_model.reject_driver(driver_id)
    logger.info(f"Driver {driver_id} rejected")
    return jsonify({"success": True, "message": f"Driver {driver_id} rejected"})


# =========================================================
# DELIVERIES
# =========================================================

@drivers_bp.route('/api/drivers/<int:driver_id>/assign', methods=['POST'])
@require_auth(['admin'])
def assign_order_to_driver(driver_id):
    """Assign order to driver. Marks driver on_delivery."""
    data = request.get_json()
    order_id = data.get('order_id')

    is_valid, err = validate_positive_int(order_id, "Order ID")
    if not is_valid:
        return jsonify({"success": False, "message": err}), 400

    order = order_model.get_order_header(order_id)
    if not order:
        return jsonify({"success": False, "message": f"Order #{order_id} does not exist"}), 400

    if order['order_status'] != 'approved':
        return jsonify({"success": False, "message": f"Order #{order_id} is not approved yet."}), 400

    existing = driver_model.get_delivery_by_order(order_id)
    if existing:
        return jsonify({"success": False, "message": f"Order #{order_id} already assigned"}), 400

    driver_model.assign_delivery(order_id, driver_id)
    return jsonify({"success": True, "message": f"Order #{order_id} assigned to driver {driver_id}"})


@drivers_bp.route('/api/driver/<int:driver_id>/deliveries', methods=['GET'])
@require_auth(['admin', 'driver'])
def get_driver_deliveries(driver_id):
    """Return driver deliveries."""
    return jsonify(driver_model.get_driver_deliveries(driver_id))


@drivers_bp.route('/api/deliveries/<int:delivery_id>/status', methods=['PUT'])
@require_auth(['driver'])
def update_delivery_status(delivery_id):
    """Update delivery status. Free driver when delivered."""
    data = request.get_json()
    new_status = data.get('delivery_status')

    row = driver_model.get_delivery_by_id(delivery_id)
    if not row:
        return jsonify({"success": False, "message": "Delivery not found"}), 404

    order_id = row['order_id']
    driver_id = row['driver_id']
    driver_model.update_delivery_status(delivery_id, new_status)

    order_status_map = {
        'assigned': 'approved',
        'picked_up': 'waiting_for_pickup',
        'in_transit': 'in_transit',
        'delivered': 'delivered'
    }
    new_order_status = order_status_map.get(new_status, 'approved')
    order_model.update_order_status(order_id, new_order_status)

    if new_status == 'delivered':
        driver_model.update_driver_status(driver_id, 'available')
        logger.info(f"Driver {driver_id} is now available")

    return jsonify({
        "success": True,
        "message": f"Delivery {delivery_id} set to {new_status}, order set to {new_order_status}"
    })


@drivers_bp.route('/api/driver/<int:driver_id>/sensor-history', methods=['GET'])
@require_auth(['driver', 'admin'])
def get_driver_sensor_history(driver_id):
    """Return sensor history."""
    return jsonify(driver_model.get_driver_sensor_history(driver_id))


@drivers_bp.route('/api/orders/<int:order_id>/reassign', methods=['POST'])
@require_auth(['admin'])
def reassign_driver(order_id):
    """Change driver for an existing delivery. Frees old driver."""
    data = request.get_json()
    new_driver_id = data.get('driver_id')

    is_valid, err = validate_positive_int(new_driver_id, "Driver ID")
    if not is_valid:
        return jsonify({"success": False, "message": err}), 400

    existing = driver_model.get_delivery_by_order(order_id)
    if not existing:
        return jsonify({"success": False, "message": f"Order #{order_id} is not assigned yet"}), 400

    old_driver_id = existing['driver_id']

    try:
        driver_model.reassign_delivery(order_id, old_driver_id, new_driver_id)
    except Exception as e:
        logger.error(f"Reassign failed for order {order_id}: {e}")
        return jsonify({"success": False, "message": "Reassign failed"}), 400

    logger.info(f"Order #{order_id} reassigned from driver {old_driver_id} to {new_driver_id}")
    return jsonify({
        "success": True,
        "message": f"Order #{order_id} reassigned to driver {new_driver_id}"
    })


@drivers_bp.route('/api/orders/<int:order_id>/delivery', methods=['GET'])
@require_auth(['admin'])
def get_order_delivery(order_id):
    """Return delivery info + driver name for an order."""
    return jsonify(driver_model.get_order_delivery_with_driver(order_id))
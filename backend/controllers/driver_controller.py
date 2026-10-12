"""
Driver controller — driver and delivery routes.
"""

from flask import Blueprint, request, jsonify
from extensions import require_auth
from utils.validators import validate_name, validate_positive_int, validate_email
from models import driver_model, order_model

drivers_bp = Blueprint('drivers', __name__)


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
            driver_model.update_driver(existing['driver_id'], name, data.get('phone', ''), data.get('license_number', ''))
            return jsonify({"success": True, "message": f"Driver '{name}' updated"})

    driver_model.create_driver(name, email, data.get('phone', ''), data.get('license_number', ''))
    return jsonify({"success": True, "message": f"Driver '{name}' added"})


@drivers_bp.route('/api/drivers/<int:driver_id>/assign', methods=['POST'])
@require_auth(['admin'])
def assign_order_to_driver(driver_id):
    """Assign order to driver."""
    data = request.get_json()
    order_id = data.get('order_id')

    is_valid, err = validate_positive_int(order_id, "Order ID")
    if not is_valid:
        return jsonify({"success": False, "message": err}), 400

    order = order_model.get_order_header(order_id)
    if not order:
        return jsonify({"success": False, "message": f"Order #{order_id} does not exist"}), 400

    if order['order_status'] != 'approved':
        return jsonify({"success": False, "message": f"Order #{order_id} is not approved yet. Current status: {order['order_status']}"}), 400

    existing = driver_model.get_delivery_by_order(order_id)
    if existing:
        return jsonify({"success": False, "message": f"Order #{order_id} already assigned"}), 400

    driver_model.create_delivery(order_id, driver_id)
    return jsonify({"success": True, "message": f"Order #{order_id} assigned to driver {driver_id}"})


@drivers_bp.route('/api/driver/<int:driver_id>/deliveries', methods=['GET'])
@require_auth(['admin', 'driver'])
def get_driver_deliveries(driver_id):
    """Return driver deliveries."""
    return jsonify(driver_model.get_driver_deliveries(driver_id))


@drivers_bp.route('/api/deliveries/<int:delivery_id>/status', methods=['PUT'])
@require_auth(['driver'])
def update_delivery_status(delivery_id):
    """Update delivery status."""
    data = request.get_json()
    new_status = data.get('delivery_status')

    row = driver_model.get_delivery_by_id(delivery_id)
    if not row:
        return jsonify({"success": False, "message": "Delivery not found"}), 404

    order_id = row['order_id']
    driver_model.update_delivery_status(delivery_id, new_status)

    order_status_map = {
        'assigned': 'approved',
        'picked_up': 'waiting_for_pickup',
        'in_transit': 'in_transit',
        'delivered': 'delivered'
    }
    new_order_status = order_status_map.get(new_status, 'approved')
    order_model.update_order_status(order_id, new_order_status)

    return jsonify({"success": True, "message": f"Delivery {delivery_id} set to {new_status}, order set to {new_order_status}"})


@drivers_bp.route('/api/driver/<int:driver_id>/sensor-history', methods=['GET'])
@require_auth(['driver', 'admin'])
def get_driver_sensor_history(driver_id):
    """Return sensor history."""
    return jsonify(driver_model.get_driver_sensor_history(driver_id))

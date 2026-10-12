"""
Sensor controller — sensor data routes.
"""

from flask import Blueprint, request, jsonify
from extensions import require_auth
from utils.logger import logger
from utils.validators import validate_positive_int, validate_positive_float
from models import sensor_model

sensor_bp = Blueprint('sensor', __name__)


@sensor_bp.route('/api/sensor', methods=['POST'])
@require_auth(['driver'])
def save_sensor():
    """Save one sensor reading."""
    data = request.get_json()
    delivery_id = data.get('delivery_id')
    temperature = data.get('temperature')
    humidity = data.get('humidity')
    is_alert = data.get('is_alert', False)

    is_valid, err = validate_positive_int(delivery_id, "Delivery ID")
    if not is_valid:
        return jsonify({"success": False, "message": err}), 400

    is_valid, err = validate_positive_float(temperature, "Temperature")
    if not is_valid:
        return jsonify({"success": False, "message": err}), 400

    is_valid, err = validate_positive_float(humidity, "Humidity")
    if not is_valid:
        return jsonify({"success": False, "message": err}), 400

    try:
        sensor_model.save_sensor_data(delivery_id, temperature, humidity, is_alert)
        return jsonify({"success": True, "message": "Sensor data saved"})
    except Exception as e:
        logger.error(f"Sensor save failed: {e}", exc_info=True)
        return jsonify({"success": False, "message": str(e)}), 400


@sensor_bp.route('/api/sensor/<int:delivery_id>', methods=['GET'])
def get_sensor_data(delivery_id):
    """Return sensor readings for a delivery."""
    return jsonify(sensor_model.get_sensor_by_delivery(delivery_id))

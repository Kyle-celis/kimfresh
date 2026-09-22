"""Sensor data routes."""
from flask import Blueprint, request, jsonify
from db_config import get_db_connection

sensor_bp = Blueprint('sensor', __name__)


@sensor_bp.route('/api/sensor', methods=['POST'])
def save_sensor():
    """
    Save a sensor reading for a delivery.
    
    Called automatically by the driver app on every BLE reading.
    """
    data = request.get_json()
    delivery_id = data.get('delivery_id')
    temperature = data.get('temperature')
    humidity = data.get('humidity')
    is_alert = data.get('is_alert', False)

    if not delivery_id or temperature is None or humidity is None:
        return jsonify({"success": False, "message": "Missing required fields"}), 400

    conn = get_db_connection()
    cursor = conn.cursor()
    try:
        cursor.execute(
            """INSERT INTO sensor_data 
               (delivery_id, temperature, humidity, is_alert) 
               VALUES (%s, %s, %s, %s)""",
            (delivery_id, temperature, humidity, is_alert)
        )
        conn.commit()
        cursor.close()
        conn.close()
        return jsonify({"success": True, "message": "Sensor data saved"})
    except Exception as e:
        conn.rollback()
        cursor.close()
        conn.close()
        return jsonify({"success": False, "message": str(e)}), 400


@sensor_bp.route('/api/sensor/<int:delivery_id>', methods=['GET'])
def get_sensor_data(delivery_id):
    """Return all sensor readings for a delivery, newest first."""
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("""
        SELECT * FROM sensor_data 
        WHERE delivery_id = %s 
        ORDER BY reading_timestamp DESC
    """, (delivery_id,))
    readings = cursor.fetchall()
    cursor.close()
    conn.close()
    return jsonify(readings)

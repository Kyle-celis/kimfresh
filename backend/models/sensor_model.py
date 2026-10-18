"""
Sensor model — all database queries for sensor data.
"""

from db_config import get_db_connection


def save_sensor_data(delivery_id, temperature, humidity, is_alert):
    """Insert one sensor reading."""
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute(
        """INSERT INTO sensor_data 
           (delivery_id, temperature, humidity, is_alert) 
           VALUES (%s, %s, %s, %s)""",
        (delivery_id, temperature, humidity, is_alert)
    )
    conn.commit()
    cursor.close()
    conn.close()


def get_sensor_by_delivery(delivery_id):
    """Return all readings for a delivery, newest first."""
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("""
        SELECT * FROM sensor_data 
        WHERE delivery_id = %s 
        ORDER BY reading_timestamp DESC
    """, (delivery_id,))
    rows = cursor.fetchall()
    cursor.close()
    conn.close()
    return rows


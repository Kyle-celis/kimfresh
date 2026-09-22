"""
Unit tests for sensor data routes.

These tests check that saving and retrieving temperature/humidity
readings works correctly.
"""

import pytest
import sys
import os

sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), '..')))

from app import app


@pytest.fixture
def client():
    """Create a test client for the Flask app."""
    app.config['TESTING'] = True
    with app.test_client() as client:
        yield client


# ---------- SAVE SENSOR DATA ----------

def test_save_sensor_success(client):
    """Saving a valid sensor reading should succeed."""
    response = client.post('/api/sensor', json={
        'delivery_id': 1,
        'temperature': 28.5,
        'humidity': 85.0,
        'is_alert': False
    })
    assert response.status_code == 200
    data = response.get_json()
    assert data['success'] is True


def test_save_sensor_with_alert(client):
    """Saving an alert reading should also succeed."""
    response = client.post('/api/sensor', json={
        'delivery_id': 1,
        'temperature': 35.0,
        'humidity': 90.0,
        'is_alert': True
    })
    assert response.status_code == 200
    data = response.get_json()
    assert data['success'] is True


def test_save_sensor_missing_delivery_id(client):
    """Missing delivery_id should return 400."""
    response = client.post('/api/sensor', json={
        'temperature': 28.5,
        'humidity': 85.0
    })
    assert response.status_code == 400
    data = response.get_json()
    assert data['success'] is False


def test_save_sensor_missing_temperature(client):
    """Missing temperature should return 400."""
    response = client.post('/api/sensor', json={
        'delivery_id': 1,
        'humidity': 85.0
    })
    assert response.status_code == 400


def test_save_sensor_missing_humidity(client):
    """Missing humidity should return 400."""
    response = client.post('/api/sensor', json={
        'delivery_id': 1,
        'temperature': 28.5
    })
    assert response.status_code == 400


def test_save_sensor_empty_payload(client):
    """Empty JSON should return 400."""
    response = client.post('/api/sensor', json={})
    assert response.status_code == 400


# ---------- GET SENSOR DATA ----------

def test_get_sensor_data(client):
    """Getting sensor data for a delivery should return a list."""
    response = client.get('/api/sensor/1')
    assert response.status_code == 200
    data = response.get_json()
    assert isinstance(data, list)


def test_get_sensor_data_invalid_delivery(client):
    """Invalid delivery ID should return empty list."""
    response = client.get('/api/sensor/99999')
    assert response.status_code == 200
    data = response.get_json()
    assert data == []


def test_sensor_data_has_fields(client):
    """Sensor readings should have expected fields."""
    # Save one first
    client.post('/api/sensor', json={
        'delivery_id': 1,
        'temperature': 28.5,
        'humidity': 85.0,
        'is_alert': False
    })

    response = client.get('/api/sensor/1')
    data = response.get_json()
    assert len(data) > 0
    reading = data[0]
    assert 'sensor_id' in reading
    assert 'delivery_id' in reading
    assert 'temperature' in reading
    assert 'humidity' in reading
    assert 'is_alert' in reading

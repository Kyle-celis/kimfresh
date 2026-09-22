"""
Unit tests for driver routes.

These tests check that listing drivers, adding drivers, and
assigning orders to drivers all work correctly.
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


# ---------- GET DRIVERS ----------

def test_get_drivers(client):
    """Should return a list of drivers."""
    response = client.get('/api/drivers')
    assert response.status_code == 200
    data = response.get_json()
    assert isinstance(data, list)


def test_drivers_have_fields(client):
    """Each driver should have the required fields."""
    response = client.get('/api/drivers')
    data = response.get_json()
    if len(data) > 0:
        driver = data[0]
        assert 'driver_id' in driver
        assert 'name' in driver
        assert 'email' in driver
        assert 'status' in driver


# ---------- ADD DRIVER ----------

def test_add_driver_success(client):
    """Adding a new driver should succeed."""
    response = client.post('/api/drivers', json={
        'name': 'Test Driver ABC',
        'email': 'testdriver@kimfresh.com',
        'phone': '09111111111',
        'license_number': 'TEST-001'
    })
    assert response.status_code == 200
    data = response.get_json()
    assert data['success'] is True


def test_add_driver_missing_name(client):
    """Adding a driver without name should fail."""
    response = client.post('/api/drivers', json={
        'email': 'noname@kimfresh.com'
    })
    assert response.status_code == 400
    data = response.get_json()
    assert data['success'] is False


def test_add_existing_driver_updates(client):
    """Adding a driver with existing email should update."""
    response = client.post('/api/drivers', json={
        'name': 'Test Driver ABC Updated',
        'email': 'testdriver@kimfresh.com',
        'phone': '09222222222',
        'license_number': 'TEST-002'
    })
    assert response.status_code == 200
    data = response.get_json()
    assert data['success'] is True
    assert 'updated' in data['message']


# ---------- ASSIGN ORDER ----------

def test_assign_order_missing_order_id(client):
    """Assigning without order_id should return 400."""
    response = client.post('/api/drivers/2/assign', json={})
    assert response.status_code == 400


def test_assign_nonexistent_order(client):
    """Assigning a non-existent order should fail."""
    response = client.post('/api/drivers/2/assign', json={
        'order_id': 99999
    })
    assert response.status_code == 400
    data = response.get_json()
    assert data['success'] is False
    assert 'does not exist' in data['message']


def test_assign_unapproved_order(client):
    """Assigning an unapproved order should fail."""
    # First place an order (it will be pending)
    res = client.post('/api/orders', json={
        'retailer_id': 1,
        'items': [{'product_id': 1, 'quantity': 1}]
    })
    order_id = res.get_json()['order_id']

    # Try to assign while still pending
    response = client.post('/api/drivers/2/assign', json={
        'order_id': order_id
    })
    assert response.status_code == 400
    data = response.get_json()
    assert data['success'] is False
    assert 'not approved' in data['message']


def test_assign_approved_order(client):
    """Assigning an approved order should succeed."""
    # Place an order
    res = client.post('/api/orders', json={
        'retailer_id': 1,
        'items': [{'product_id': 2, 'quantity': 1}]
    })
    order_id = res.get_json()['order_id']

    # Approve it
    client.put(f'/api/orders/{order_id}/status', json={
        'status': 'approved'
    })

    # Assign to driver
    response = client.post('/api/drivers/2/assign', json={
        'order_id': order_id
    })
    assert response.status_code == 200
    data = response.get_json()
    assert data['success'] is True


# ---------- GET DRIVER DELIVERIES ----------

def test_get_driver_deliveries(client):
    """Should return deliveries for a driver."""
    response = client.get('/api/driver/2/deliveries')
    assert response.status_code == 200
    data = response.get_json()
    assert isinstance(data, list)


def test_get_driver_deliveries_empty(client):
    """Driver with no deliveries should return empty list."""
    response = client.get('/api/driver/99999/deliveries')
    assert response.status_code == 200
    data = response.get_json()
    assert data == []

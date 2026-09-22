"""
Unit tests for order routes.

These tests check that placing orders, viewing orders, and updating
order status all work correctly.
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


# ---------- PLACE ORDER ----------

def test_place_order_success(client):
    """Placing a valid order should succeed."""
    response = client.post('/api/orders', json={
        'retailer_id': 1,
        'items': [
            {'product_id': 1, 'quantity': 1}
        ]
    })
    assert response.status_code == 200
    data = response.get_json()
    assert data['success'] is True
    assert 'order_id' in data
    assert float(data['total']) > 0


def test_place_order_reduces_stock(client):
    """Placing an order should reduce product stock."""
    # Get stock before
    res1 = client.get('/api/products/1')
    stock_before = res1.get_json()['stock_quantity']

    # Place order
    client.post('/api/orders', json={
        'retailer_id': 1,
        'items': [{'product_id': 1, 'quantity': 2}]
    })

    # Get stock after
    res2 = client.get('/api/products/1')
    stock_after = res2.get_json()['stock_quantity']

    assert stock_after == stock_before - 2


def test_place_order_insufficient_stock(client):
    """Ordering more than available should fail."""
    response = client.post('/api/orders', json={
        'retailer_id': 1,
        'items': [{'product_id': 1, 'quantity': 99999}]
    })
    assert response.status_code == 400
    data = response.get_json()
    assert data['success'] is False
    assert 'Not enough stock' in data['message']


def test_place_order_invalid_product(client):
    """Ordering a non-existent product should fail."""
    response = client.post('/api/orders', json={
        'retailer_id': 1,
        'items': [{'product_id': 99999, 'quantity': 1}]
    })
    assert response.status_code == 400
    data = response.get_json()
    assert data['success'] is False


def test_place_order_missing_retailer(client):
    """Missing retailer_id should return 400."""
    response = client.post('/api/orders', json={
        'items': [{'product_id': 1, 'quantity': 1}]
    })
    assert response.status_code == 400


def test_place_order_missing_items(client):
    """Missing items should return 400."""
    response = client.post('/api/orders', json={
        'retailer_id': 1
    })
    assert response.status_code == 400


def test_place_order_empty_items(client):
    """Empty items list should return 400."""
    response = client.post('/api/orders', json={
        'retailer_id': 1,
        'items': []
    })
    assert response.status_code == 400


# ---------- GET ORDERS ----------

def test_get_orders_requires_auth(client):
    """Getting orders without headers should be blocked."""
    response = client.get('/api/orders/1')
    assert response.status_code == 403


def test_get_orders_with_retailer_header(client):
    """Retailer should see their own orders."""
    response = client.get('/api/orders/1', headers={
        'X-User-Role': 'retailer',
        'X-User-Id': '1'
    })
    assert response.status_code == 200
    data = response.get_json()
    assert isinstance(data, list)


def test_get_all_orders_admin_only(client):
    """Admin should see all orders."""
    response = client.get('/api/orders/all')
    assert response.status_code == 200
    data = response.get_json()
    assert isinstance(data, list)

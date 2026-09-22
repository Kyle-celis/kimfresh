"""
Unit tests for tenant isolation.

These tests check that one retailer cannot see another retailer's
orders by changing the ID in the URL. This is a critical security check.
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


# ---------- ALLOWED ACCESS ----------

def test_retailer_can_see_own_orders(client):
    """Retailer 1 should see their own orders."""
    response = client.get('/api/orders/1', headers={
        'X-User-Role': 'retailer',
        'X-User-Id': '1'
    })
    assert response.status_code == 200


def test_admin_can_see_any_orders(client):
    """Admin should see orders from any retailer."""
    response = client.get('/api/orders/1', headers={
        'X-User-Role': 'admin'
    })
    assert response.status_code == 200


def test_admin_can_see_retailer_2_orders(client):
    """Admin should see retailer 2's orders too."""
    response = client.get('/api/orders/2', headers={
        'X-User-Role': 'admin'
    })
    assert response.status_code == 200


# ---------- BLOCKED ACCESS ----------

def test_retailer_cannot_see_other_retailer_orders(client):
    """Retailer 1 should NOT see retailer 2's orders."""
    response = client.get('/api/orders/2', headers={
        'X-User-Role': 'retailer',
        'X-User-Id': '1'
    })
    assert response.status_code == 403
    data = response.get_json()
    assert data['success'] is False


def test_retailer_cannot_see_other_retailer_orders_reverse(client):
    """Retailer 2 should NOT see retailer 1's orders."""
    response = client.get('/api/orders/1', headers={
        'X-User-Role': 'retailer',
        'X-User-Id': '2'
    })
    assert response.status_code == 403


def test_no_headers_is_blocked(client):
    """Request without any headers should be blocked."""
    response = client.get('/api/orders/1')
    assert response.status_code == 403


def test_only_role_is_blocked(client):
    """Request with only role but no ID should be blocked."""
    response = client.get('/api/orders/2', headers={
        'X-User-Role': 'retailer'
    })
    assert response.status_code == 403


def test_unknown_role_is_blocked(client):
    """Request with unknown role should be blocked."""
    response = client.get('/api/orders/1', headers={
        'X-User-Role': 'hacker',
        'X-User-Id': '1'
    })
    assert response.status_code == 403

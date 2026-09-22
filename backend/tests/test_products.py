"""
Unit tests for product routes.

These tests check that listing, adding, hiding, and restoring
products all work correctly.
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


# ---------- LIST PRODUCTS ----------

def test_get_active_products(client):
    """Should return a list of available products."""
    response = client.get('/api/products')
    assert response.status_code == 200
    data = response.get_json()
    assert isinstance(data, list)
    assert len(data) > 0


def test_active_products_have_fields(client):
    """Each product should have the required fields."""
    response = client.get('/api/products')
    data = response.get_json()
    product = data[0]
    assert 'product_id' in product
    assert 'name' in product
    assert 'sku' in product
    assert 'unit_price' in product
    assert 'stock_quantity' in product


def test_get_all_products(client):
    """Should return all products including inactive ones."""
    response = client.get('/api/products/all')
    assert response.status_code == 200
    data = response.get_json()
    assert isinstance(data, list)


def test_get_product_by_id(client):
    """Should return a single product by ID."""
    response = client.get('/api/products/1')
    assert response.status_code == 200
    data = response.get_json()
    assert data['product_id'] == 1


def test_get_product_invalid_id(client):
    """Invalid product ID should return 404."""
    response = client.get('/api/products/99999')
    assert response.status_code == 404


# ---------- ADD PRODUCT ----------

def test_add_new_product(client):
    """Adding a new product with unique name should succeed."""
    response = client.post('/api/products', json={
        'name': 'Test Kimchi XYZ',
        'sku': '',
        'unit_price': 99.99,
        'reorder_level': 10
    })
    assert response.status_code == 200
    data = response.get_json()
    assert data['success'] is True


def test_add_duplicate_product_updates(client):
    """Adding a product with existing name should update it."""
    response = client.post('/api/products', json={
        'name': 'Baechu Kimchi',
        'sku': 'KIM-001',
        'unit_price': 175.00,
        'reorder_level': 10
    })
    assert response.status_code == 200
    data = response.get_json()
    assert data['success'] is True
    assert 'updated' in data['message']


def test_add_product_missing_name_and_sku(client):
    """Adding a product without name or SKU should fail."""
    response = client.post('/api/products', json={
        'unit_price': 99.99
    })
    assert response.status_code == 400


# ---------- HIDE AND RESTORE ----------

def test_hide_product(client):
    """Hiding a product should set status to inactive."""
    response = client.put('/api/products/3/hide')
    assert response.status_code == 200
    data = response.get_json()
    assert data['success'] is True


def test_restore_product(client):
    """Restoring a product should set status to available."""
    response = client.put('/api/products/3/restore')
    assert response.status_code == 200
    data = response.get_json()
    assert data['success'] is True


def test_hidden_product_not_in_active_list(client):
    """Hidden products should not appear in the active list."""
    client.put('/api/products/3/hide')
    response = client.get('/api/products')
    data = response.get_json()
    ids = [p['product_id'] for p in data]
    assert 3 not in ids
    # Restore for next tests
    client.put('/api/products/3/restore')


def test_hidden_product_in_all_list(client):
    """Hidden products should still appear in the all list."""
    client.put('/api/products/3/hide')
    response = client.get('/api/products/all')
    data = response.get_json()
    ids = [p['product_id'] for p in data]
    assert 3 in ids
    # Restore for next tests
    client.put('/api/products/3/restore')

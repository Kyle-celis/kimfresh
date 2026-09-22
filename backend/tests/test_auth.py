"""
Unit tests for authentication routes.

These tests check that login works correctly for all user types
and that wrong credentials are properly rejected.
"""

import pytest
import sys
import os

# Add parent folder to path so we can import app
sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), '..')))

from app import app


@pytest.fixture
def client():
    """Create a test client for the Flask app."""
    app.config['TESTING'] = True
    with app.test_client() as client:
        yield client


# ---------- LOGIN SUCCESS TESTS ----------

def test_login_admin_success(client):
    """Admin login should succeed with any password."""
    response = client.post('/api/login', json={
        'email': 'admin@kimfresh.com',
        'password': 'password123'
    })
    assert response.status_code == 200
    data = response.get_json()
    assert data['success'] is True
    assert data['role'] == 'admin'


def test_login_retailer_success(client):
    """Retailer login should succeed with correct password."""
    response = client.post('/api/login', json={
        'email': 'retailer@kimfresh.com',
        'password': 'password123'
    })
    assert response.status_code == 200
    data = response.get_json()
    assert data['success'] is True
    assert data['role'] == 'retailer'


def test_login_driver_success(client):
    """Driver login should succeed with correct password."""
    response = client.post('/api/login', json={
        'email': 'driver@kimfresh.com',
        'password': 'password123'
    })
    assert response.status_code == 200
    data = response.get_json()
    assert data['success'] is True
    assert data['role'] == 'driver'


# ---------- LOGIN FAILURE TESTS ----------

def test_login_wrong_password(client):
    """Retailer with wrong password should be rejected."""
    response = client.post('/api/login', json={
        'email': 'retailer@kimfresh.com',
        'password': 'wrongpassword'
    })
    assert response.status_code == 401
    data = response.get_json()
    assert data['success'] is False


def test_login_unknown_email(client):
    """Unknown email should return 401."""
    response = client.post('/api/login', json={
        'email': 'nobody@kimfresh.com',
        'password': 'anything'
    })
    assert response.status_code == 401
    data = response.get_json()
    assert data['success'] is False


def test_login_missing_email(client):
    """Missing email should return 400."""
    response = client.post('/api/login', json={
        'password': 'password123'
    })
    assert response.status_code == 400


def test_login_missing_password(client):
    """Missing password should return 400."""
    response = client.post('/api/login', json={
        'email': 'retailer@kimfresh.com'
    })
    assert response.status_code == 400


def test_login_empty_payload(client):
    """Empty JSON should return 400."""
    response = client.post('/api/login', json={})
    assert response.status_code == 400

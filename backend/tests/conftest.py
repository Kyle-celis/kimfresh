"""
Shared pytest fixtures for KimFresh tests.
"""
import os
os.environ['TESTING'] = '1'
os.environ['JWT_SECRET'] = 'test-secret-for-pytest-only-32chars-minimum'

import pytest
import sys
import os

sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), '..')))

from app import app
from utils.auth_token import create_token


@pytest.fixture
def client():
    """Create a test client for the Flask app."""
    app.config['TESTING'] = True
    with app.test_client() as client:
        yield client


@pytest.fixture
def admin_token():
    """Token for an admin user."""
    return create_token(user_id=1, role='admin', email='admin@kimfresh.com')


@pytest.fixture
def retailer_1_token():
    """Token for retailer 1."""
    return create_token(user_id=1, role='retailer', email='retailer@kimfresh.com')


@pytest.fixture
def retailer_2_token():
    """Token for retailer 2."""
    return create_token(user_id=2, role='retailer', email='retailer2@kimfresh.com')


@pytest.fixture
def driver_token():
    """Token for a driver."""
    return create_token(user_id=1, role='driver', email='driver@kimfresh.com')

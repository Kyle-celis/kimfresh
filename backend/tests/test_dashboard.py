"""
Unit tests for dashboard summary and chart routes.

These tests check that summary counts and weekly order charts
return correct data.
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


# ---------- DASHBOARD SUMMARY ----------

def test_summary_returns_data(client):
    """Summary should return a JSON object."""
    response = client.get('/api/dashboard/summary')
    assert response.status_code == 200
    data = response.get_json()
    assert isinstance(data, dict)


def test_summary_has_all_fields(client):
    """Summary should have all expected fields."""
    response = client.get('/api/dashboard/summary')
    data = response.get_json()

    expected_fields = [
        'total_products',
        'total_drivers',
        'total_orders',
        'pending_orders',
        'approved_orders',
        'rejected_orders',
        'low_stock',
        'this_week',
    ]

    for field in expected_fields:
        assert field in data


def test_summary_counts_are_numbers(client):
    """All summary counts should be numbers."""
    response = client.get('/api/dashboard/summary')
    data = response.get_json()

    for key, value in data.items():
        assert isinstance(value, (int, float))


def test_summary_counts_non_negative(client):
    """All summary counts should be >= 0."""
    response = client.get('/api/dashboard/summary')
    data = response.get_json()

    for key, value in data.items():
        assert value >= 0


def test_summary_total_orders_matches_sum(client):
    """Total orders should be >= sum of pending, approved, rejected."""
    response = client.get('/api/dashboard/summary')
    data = response.get_json()

    status_sum = data['pending_orders'] + data['approved_orders'] + data['rejected_orders']
    assert data['total_orders'] >= status_sum


# ---------- ORDERS PER WEEK ----------

def test_orders_per_week_returns_data(client):
    """Weekly orders should return a JSON object."""
    response = client.get('/api/dashboard/orders-per-week')
    assert response.status_code == 200
    data = response.get_json()
    assert isinstance(data, dict)


def test_orders_per_week_has_seven_days(client):
    """Weekly data should always have exactly 7 days."""
    response = client.get('/api/dashboard/orders-per-week')
    data = response.get_json()

    assert 'days' in data
    assert 'counts' in data
    assert len(data['days']) == 7
    assert len(data['counts']) == 7


def test_orders_per_week_counts_are_numbers(client):
    """All weekly counts should be numbers."""
    response = client.get('/api/dashboard/orders-per-week')
    data = response.get_json()

    for count in data['counts']:
        assert isinstance(count, (int, float))


def test_orders_per_week_with_custom_start(client):
    """Weekly data should work with a custom start date."""
    response = client.get('/api/dashboard/orders-per-week?week_start=2026-09-01')
    assert response.status_code == 200
    data = response.get_json()

    assert data['week_start'] == '2026-09-01'
    assert data['week_end'] == '2026-09-07'
    assert len(data['days']) == 7


def test_orders_per_week_returns_start_and_end(client):
    """Weekly data should return week_start and week_end."""
    response = client.get('/api/dashboard/orders-per-week')
    data = response.get_json()

    assert 'week_start' in data
    assert 'week_end' in data

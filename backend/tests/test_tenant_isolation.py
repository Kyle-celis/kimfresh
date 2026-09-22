"""
Unit tests for tenant isolation.

Checks that one retailer cannot see another retailer's orders.
"""


def test_retailer_can_see_own_orders(client, retailer_1_token):
    response = client.get('/api/orders/1', headers={
        'Authorization': f'Bearer {retailer_1_token}'
    })
    assert response.status_code == 200


def test_admin_can_see_any_orders(client, admin_token):
    response = client.get('/api/orders/1', headers={
        'Authorization': f'Bearer {admin_token}'
    })
    assert response.status_code == 200


def test_retailer_cannot_see_other_retailer_orders(client, retailer_1_token):
    response = client.get('/api/orders/2', headers={
        'Authorization': f'Bearer {retailer_1_token}'
    })
    assert response.status_code == 403


def test_retailer_cannot_see_other_retailer_orders_reverse(client, retailer_2_token):
    response = client.get('/api/orders/1', headers={
        'Authorization': f'Bearer {retailer_2_token}'
    })
    assert response.status_code == 403


def test_no_token_is_blocked(client):
    response = client.get('/api/orders/1')
    assert response.status_code == 401


def test_invalid_token_is_blocked(client):
    response = client.get('/api/orders/1', headers={
        'Authorization': 'Bearer fake.token.here'
    })
    assert response.status_code == 401


def test_wrong_role_is_blocked(client, driver_token):
    response = client.get('/api/orders/1', headers={
        'Authorization': f'Bearer {driver_token}'
    })
    assert response.status_code == 403

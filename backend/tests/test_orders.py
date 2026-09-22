"""Unit tests for order routes."""


def test_place_order_requires_token(client):
    response = client.post('/api/orders', json={
        'retailer_id': 1,
        'items': [{'product_id': 1, 'quantity': 1}]
    })
    assert response.status_code == 401


def test_place_order_success(client, retailer_1_token):
    response = client.post('/api/orders', json={
        'retailer_id': 1,
        'items': [{'product_id': 1, 'quantity': 1}]
    }, headers={'Authorization': f'Bearer {retailer_1_token}'})
    assert response.status_code == 200
    data = response.get_json()
    assert data['success'] is True
    assert float(data['total']) > 0


def test_place_order_reduces_stock(client, retailer_1_token):
    res1 = client.get('/api/products/1')
    stock_before = res1.get_json()['stock_quantity']

    client.post('/api/orders', json={
        'retailer_id': 1,
        'items': [{'product_id': 1, 'quantity': 2}]
    }, headers={'Authorization': f'Bearer {retailer_1_token}'})

    res2 = client.get('/api/products/1')
    stock_after = res2.get_json()['stock_quantity']

    assert stock_after == stock_before - 2


def test_place_order_insufficient_stock(client, retailer_1_token):
    response = client.post('/api/orders', json={
        'retailer_id': 1,
        'items': [{'product_id': 1, 'quantity': 99999}]
    }, headers={'Authorization': f'Bearer {retailer_1_token}'})
    assert response.status_code == 400
    assert 'Not enough stock' in response.get_json()['message']


def test_place_order_missing_items(client, retailer_1_token):
    response = client.post('/api/orders', json={'retailer_id': 1},
                           headers={'Authorization': f'Bearer {retailer_1_token}'})
    assert response.status_code == 400


def test_get_all_orders_requires_admin(client, retailer_1_token):
    response = client.get('/api/orders/all', headers={
        'Authorization': f'Bearer {retailer_1_token}'
    })
    assert response.status_code == 403


def test_get_all_orders_as_admin(client, admin_token):
    response = client.get('/api/orders/all', headers={
        'Authorization': f'Bearer {admin_token}'
    })
    assert response.status_code == 200


def test_update_order_status_as_admin(client, admin_token, retailer_1_token):
    res = client.post('/api/orders', json={
        'retailer_id': 1,
        'items': [{'product_id': 1, 'quantity': 1}]
    }, headers={'Authorization': f'Bearer {retailer_1_token}'})
    
    print("RESPONSE:", res.get_json())  # ← ADD THIS
    
    order_id = res.get_json()['order_id']
    response = client.put(f'/api/orders/{order_id}/status', json={
        'status': 'approved'
    }, headers={'Authorization': f'Bearer {admin_token}'})
    assert response.status_code == 200

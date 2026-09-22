"""Unit tests for product routes."""


def test_get_active_products(client):
    response = client.get('/api/products')
    assert response.status_code == 200
    assert isinstance(response.get_json(), list)


def test_get_all_products(client):
    response = client.get('/api/products/all')
    assert response.status_code == 200


def test_get_product_by_id(client):
    response = client.get('/api/products/1')
    assert response.status_code == 200
    assert response.get_json()['product_id'] == 1


def test_get_product_invalid_id(client):
    response = client.get('/api/products/99999')
    assert response.status_code == 404


def test_add_product_requires_admin(client):
    response = client.post('/api/products', json={
        'name': 'Test Kimchi Auth',
        'unit_price': 99.99,
        'reorder_level': 10
    })
    assert response.status_code == 401


def test_add_product_as_admin(client, admin_token):
    response = client.post('/api/products', json={
        'name': 'Test Kimchi Auth',
        'unit_price': 99.99,
        'reorder_level': 10
    }, headers={'Authorization': f'Bearer {admin_token}'})
    assert response.status_code == 200
    assert response.get_json()['success'] is True


def test_add_product_as_retailer_forbidden(client, retailer_1_token):
    response = client.post('/api/products', json={
        'name': 'Retailer Product',
        'unit_price': 50,
        'reorder_level': 5
    }, headers={'Authorization': f'Bearer {retailer_1_token}'})
    assert response.status_code == 403


def test_hide_product_as_admin(client, admin_token):
    response = client.put('/api/products/3/hide', headers={
        'Authorization': f'Bearer {admin_token}'
    })
    assert response.status_code == 200


def test_restore_product_as_admin(client, admin_token):
    response = client.put('/api/products/3/restore', headers={
        'Authorization': f'Bearer {admin_token}'
    })
    assert response.status_code == 200


def test_hide_product_without_token(client):
    response = client.put('/api/products/3/hide')
    assert response.status_code == 401

"""Unit tests for driver routes."""


def test_get_drivers(client):
    response = client.get('/api/drivers')
    assert response.status_code == 200


def test_add_driver_requires_admin(client):
    response = client.post('/api/drivers', json={'name': 'No Token'})
    assert response.status_code == 401


def test_add_driver_as_admin(client, admin_token):
    response = client.post('/api/drivers', json={
        'name': 'Test Driver XYZ',
        'email': 'testdriverxyz@kimfresh.com',
        'phone': '09111111111',
        'license_number': 'TEST-XYZ'
    }, headers={'Authorization': f'Bearer {admin_token}'})
    assert response.status_code == 200


def test_add_driver_missing_name(client, admin_token):
    response = client.post('/api/drivers', json={
        'email': 'noname@kimfresh.com'
    }, headers={'Authorization': f'Bearer {admin_token}'})
    assert response.status_code == 400


def test_assign_order_requires_admin(client, retailer_1_token):
    response = client.post('/api/drivers/2/assign', json={'order_id': 1},
                           headers={'Authorization': f'Bearer {retailer_1_token}'})
    assert response.status_code == 403


def test_assign_nonexistent_order(client, admin_token):
    response = client.post('/api/drivers/2/assign', json={'order_id': 99999},
                           headers={'Authorization': f'Bearer {admin_token}'})
    assert response.status_code == 400


def test_get_driver_deliveries(client, driver_token):
    response = client.get('/api/driver/1/deliveries', headers={
        'Authorization': f'Bearer {driver_token}'
    })
    assert response.status_code == 200


def test_update_delivery_status_requires_driver(client, admin_token):
    response = client.put('/api/deliveries/1/status', json={'delivery_status': 'delivered'},
                          headers={'Authorization': f'Bearer {admin_token}'})
    assert response.status_code == 403

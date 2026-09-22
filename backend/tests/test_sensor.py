"""Unit tests for sensor routes."""


def test_save_sensor_requires_driver(client):
    response = client.post('/api/sensor', json={
        'delivery_id': 1, 'temperature': 28.5, 'humidity': 85.0
    })
    assert response.status_code == 401


def test_save_sensor_as_driver(client, driver_token):
    response = client.post('/api/sensor', json={
        'delivery_id': 1,
        'temperature': 28.5,
        'humidity': 85.0,
        'is_alert': False
    }, headers={'Authorization': f'Bearer {driver_token}'})
    assert response.status_code == 200


def test_save_sensor_missing_delivery_id(client, driver_token):
    response = client.post('/api/sensor', json={
        'temperature': 28.5, 'humidity': 85.0
    }, headers={'Authorization': f'Bearer {driver_token}'})
    assert response.status_code == 400


def test_get_sensor_data(client):
    response = client.get('/api/sensor/1')
    assert response.status_code == 200
    assert isinstance(response.get_json(), list)


def test_get_sensor_data_invalid_delivery(client):
    response = client.get('/api/sensor/99999')
    assert response.status_code == 200
    assert response.get_json() == []

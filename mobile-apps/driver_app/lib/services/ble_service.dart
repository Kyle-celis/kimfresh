import 'dart:async';
import 'package:flutter/foundation.dart';
import 'package:flutter_blue_plus/flutter_blue_plus.dart';

class BleService {
  BluetoothDevice? _connectedDevice;
  BluetoothCharacteristic? _tempCharacteristic;
  BluetoothCharacteristic? _humCharacteristic;

  final _readingController = StreamController<Map<String, dynamic>>.broadcast();
  Stream<Map<String, dynamic>> get readingStream => _readingController.stream;

  // 1. SCAN FOR DEVICES
  Stream<List<ScanResult>> scanForDevices() {
    FlutterBluePlus.startScan(timeout: const Duration(seconds: 10));
    return FlutterBluePlus.scanResults;
  }

  void stopScan() {
    FlutterBluePlus.stopScan();
  }

  // 2. CONNECT TO A SPECIFIC DEVICE
  Future<bool> connectToDevice(BluetoothDevice device) async {
    try {
      await device.connect(timeout: const Duration(seconds: 15));
      _connectedDevice = device;

      // Listen for disconnection
      device.connectionState.listen((state) {
        if (state == BluetoothConnectionState.disconnected) {
          _readingController.add({'type': 'disconnected'});
          _connectedDevice = null;
        }
      });

      _readingController.add({'type': 'connected'});
      await _discoverServices(device);
      return true;
    } catch (e) {
      debugPrint('Connection failed: $e');
      return false;
    }
  }

  // 3. DISCOVER SERVICES & CHARACTERISTICS
  Future<void> _discoverServices(BluetoothDevice device) async {
    List<BluetoothService> services = await device.discoverServices();

    for (var service in services) {
      for (var characteristic in service.characteristics) {
        // We need to match this UUID with the one in your ESP32 code!
        // If you are using the standard environmental sensing UUID, it looks like this:
        if (characteristic.uuid.toString().toLowerCase().contains('181a')) {

          // Subscribe to notifications
          if (characteristic.properties.notify) {
            await characteristic.setNotifyValue(true);

            // We need to distinguish between Temp and Hum.
            // Usually, ESP32 sends them as a single JSON string or comma-separated string.
            characteristic.onValueReceived.listen((value) {
              String rawData = String.fromCharCodes(value);

              // Example parse: "Temp:25.5,Hum:60.2"
              try {
                // Adjust this parsing based on exactly what your ESP32 sends
                final parts = rawData.split(',');
                double temp = double.parse(parts[0].split(':')[1]);
                double hum = double.parse(parts[1].split(':')[1]);

                _readingController.add({
                  'type': 'live',
                  'temperature': temp,
                  'humidity': hum,
                  'is_alert': temp > 10.0, // Example alert logic
                });
              } catch (e) {
                debugPrint('Error parsing BLE data: $e');
              }
            });
          }
        }
      }
    }
  }

  // 4. DISCONNECT
  Future<void> disconnect() async {
    if (_connectedDevice != null) {
      await _connectedDevice!.disconnect();
      _connectedDevice = null;
    }
  }
}
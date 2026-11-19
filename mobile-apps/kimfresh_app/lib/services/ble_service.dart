import 'dart:async';
import 'package:flutter/foundation.dart';
import 'package:flutter_blue_plus/flutter_blue_plus.dart';

class BleService {
  // ✅ SINGLETON INSTANCE
  static final BleService instance = BleService();

  BluetoothDevice? _connectedDevice;
  BluetoothCharacteristic? _dataCharacteristic;

  final _readingController = StreamController<Map<String, dynamic>>.broadcast();
  Stream<Map<String, dynamic>> get readingStream => _readingController.stream;

  bool get isConnected => _connectedDevice != null;

  // 1. SCAN FOR DEVICES
  Stream<List<ScanResult>> scanForDevices() async* {
    if (await FlutterBluePlus.isSupported == false) {
      debugPrint("BLE not supported");
      return;
    }

    final adapterState = await FlutterBluePlus.adapterState.first;
    if (adapterState != BluetoothAdapterState.on) {
      debugPrint("Waiting for Bluetooth to turn on...");
      await FlutterBluePlus.adapterState.where((s) => s == BluetoothAdapterState.on).first;
    }

    try {
      if (await FlutterBluePlus.isScanning.first) {
        await FlutterBluePlus.stopScan();
      }
      await FlutterBluePlus.startScan(
        withServices: [],
        timeout: const Duration(seconds: 15),
        continuousUpdates: true,
      );
    } catch (e) {
      debugPrint("Scan error: $e");
    }

    yield* FlutterBluePlus.scanResults;
  }

  void stopScan() {
    FlutterBluePlus.stopScan();
  }

  // 2. CONNECT TO A SPECIFIC DEVICE
  Future<bool> connectToDevice(BluetoothDevice device) async {
    try {
      if (await FlutterBluePlus.isScanning.first) {
        await FlutterBluePlus.stopScan();
      }
      await Future.delayed(const Duration(milliseconds: 1000));

      debugPrint("🔌 Attempting to connect to ${device.remoteId}...");

      await device.connect(timeout: const Duration(seconds: 15));
      _connectedDevice = device;

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

  // HARDCODED CONNECT: Bypasses the scan and connects directly
  Future<bool> connectToSavedDevice() async {
    try {
      final savedMac = "30:76:F5:93:83:F2"; // Your ESP32 MAC
      final device = BluetoothDevice.fromId(savedMac);
      return await connectToDevice(device);
    } catch (e) {
      debugPrint('Saved connection failed: $e');
      return false;
    }
  }

  // 3. DISCOVER SERVICES & CHARACTERISTICS
  Future<void> _discoverServices(BluetoothDevice device) async {
    List<BluetoothService> services = await device.discoverServices();

    for (var service in services) {
      if (service.uuid.toString().toLowerCase().contains('abcd')) {
        for (var characteristic in service.characteristics) {
          if (characteristic.uuid.toString().toLowerCase().contains('1234')) {
            _dataCharacteristic = characteristic;

            if (characteristic.properties.notify) {
              await characteristic.setNotifyValue(true);
              debugPrint('✅ Subscribed to notifications!');

              if (characteristic.properties.read) {
                try {
                  final initialValue = await characteristic.read();
                  if (initialValue.isNotEmpty) {
                    String rawData = String.fromCharCodes(initialValue);
                    debugPrint('📥 BLE Initial Read: $rawData');
                  }
                } catch(e) {
                  debugPrint('Failed to read initial value: $e');
                }
              }

              characteristic.onValueReceived.listen((value) {
                String rawData = String.fromCharCodes(value);
                debugPrint('📥 BLE Raw Data: $rawData');

                try {
                  // ✅ FIXED REGEX: Added (-?[\d.]+) to catch negative temperatures like -5.9
                  final regExp = RegExp(r'T:(-?[\d.]+)C H:([\d.]+)%');
                  final match = regExp.firstMatch(rawData);

                  if (match != null) {
                    double temp = double.parse(match.group(1)!);
                    double hum = double.parse(match.group(2)!);

                    // ✅ THRESHOLD LOGIC
                    String alertLevel = 'safe';
                    if (temp >= 15.0) {
                      alertLevel = 'critical'; // Red / 10 beeps
                    } else if (temp >= 5.0) {
                      alertLevel = 'warning';  // Yellow / 3 beeps
                    }

                    _readingController.add({
                      'type': 'live',
                      'temperature': temp,
                      'humidity': hum,
                      'is_alert': alertLevel != 'safe',
                      'alert_level': alertLevel, // Passed to UI
                    });
                  } else {
                    // Debugging: Log if the regex fails to match
                    debugPrint('⚠️ Regex failed to match data: $rawData');
                  }
                } catch (e) {
                  debugPrint('Error parsing BLE data: $e');
                }
              });
            }
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
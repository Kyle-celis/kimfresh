import 'dart:async';
import 'package:flutter/foundation.dart';
import 'package:flutter_blue_plus/flutter_blue_plus.dart';

class BleService {
  BluetoothDevice? _connectedDevice;
  BluetoothCharacteristic? _dataCharacteristic;

  final _readingController = StreamController<Map<String, dynamic>>.broadcast();
  Stream<Map<String, dynamic>> get readingStream => _readingController.stream;

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

    // 🔥 FIX: Clear Android's BLE cache before starting a new scan
    // This forces Android to drop any ghost connections to the ESP32
    try {
      if (await FlutterBluePlus.isScanning.first) {
        await FlutterBluePlus.stopScan();
      }
      // This clears the internal device cache
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
      // 1. Force stop ANY active scan
      if (await FlutterBluePlus.isScanning.first) {
        await FlutterBluePlus.stopScan();
      }

      // 2. Wait for Android to fully shut down the scanner (CRITICAL)
      await Future.delayed(const Duration(milliseconds: 1000));

      debugPrint("🔌 Attempting to connect to ${device.remoteId}...");

      // 3. Connect
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

  // 🔥 HARDCODED CONNECT: Bypasses the scan and connects directly
  Future<bool> connectToSavedDevice() async {
    try {
      // Replace this with the actual MAC address from your nRF Connect screenshot
      final savedMac = "30:76:F5:93:83:F2";
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
      // Print every service so we can see it in the console
      debugPrint('🔍 Found Service: ${service.uuid}');

      // Match the service UUID "ABCD"
      if (service.uuid.toString().toLowerCase().contains('abcd')) {

        for (var characteristic in service.characteristics) {
          debugPrint('   ↳ Found Characteristic: ${characteristic.uuid}');

          // Match the characteristic UUID "1234"
          if (characteristic.uuid.toString().toLowerCase().contains('1234')) {
            _dataCharacteristic = characteristic;

            if (characteristic.properties.notify) {
              await characteristic.setNotifyValue(true);
              debugPrint('✅ Subscribed to notifications!');

              // 🔥 FIX: Read the current value immediately (catches the "CONNECTED|" message)
              if (characteristic.properties.read) {
                try {
                  final initialValue = await characteristic.read();
                  if (initialValue.isNotEmpty) {
                    String rawData = String.fromCharCodes(initialValue);
                    debugPrint('📥 BLE Initial Read: $rawData');
                    // We ignore the parsing here because the initial value is just "CONNECTED|123"
                  }
                } catch(e) {
                  debugPrint('Failed to read initial value: $e');
                }
              }

              characteristic.onValueReceived.listen((value) {
                String rawData = String.fromCharCodes(value);
                debugPrint('📥 BLE Raw Data: $rawData');

                // Parse the ESP32 format: "T:25.5C H:60.2% OK"
                try {
                  final regExp = RegExp(r'T:([\d.]+)C H:([\d.]+)%');
                  final match = regExp.firstMatch(rawData);

                  if (match != null) {
                    double temp = double.parse(match.group(1)!);
                    double hum = double.parse(match.group(2)!);
                    bool isAlert = rawData.contains('!');

                    _readingController.add({
                      'type': 'live',
                      'temperature': temp,
                      'humidity': hum,
                      'is_alert': isAlert,
                    });
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
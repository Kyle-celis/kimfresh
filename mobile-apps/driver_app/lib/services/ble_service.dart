import 'dart:async';
import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:flutter_blue_plus/flutter_blue_plus.dart';

class BleService {
  BluetoothDevice? _device;
  BluetoothCharacteristic? _characteristic;
  BluetoothCharacteristic? _commandCharacteristic;
  bool isConnected = false;
  String latestReading = '';
  double? temperature;
  double? humidity;
  bool isAlert = false;
  List<Map<String, dynamic>> storedReadings = [];

  StreamSubscription? _scanSub;
  StreamSubscription? _connectionSub;

  final _readingController = StreamController<Map<String, dynamic>>.broadcast();
  Stream<Map<String, dynamic>> get readingStream => _readingController.stream;

  Future<void> scanAndConnect() async {
    try {
      debugPrint('=== BLE SCAN STARTED ===');

      await _scanSub?.cancel();
      await FlutterBluePlus.stopScan();

      await FlutterBluePlus.startScan(timeout: const Duration(seconds: 15));

      _scanSub = FlutterBluePlus.scanResults.listen((results) async {
        for (ScanResult r in results) {
          final name = r.device.platformName;
          final id = r.device.remoteId.toString();

          if (name == 'Kimchi_Monitor' || id == '30:76:F5:93:83:F2') {
            debugPrint('=== FOUND ESP32! CONNECTING... ===');
            await FlutterBluePlus.stopScan();
            await _connect(r.device);
            return;
          }
        }
      });
    } catch (e) {
      debugPrint('BLE Scan failed: $e');
      _readingController.add({'type': 'error', 'message': 'Bluetooth scan failed'});
    }
  }

  Future<void> _connect(BluetoothDevice device) async {
    try {
      _device = device;
      await device.connect();
      isConnected = true;

      _connectionSub = device.connectionState.listen((state) {
        if (state == BluetoothConnectionState.disconnected) {
          isConnected = false;
          _readingController.add({'type': 'disconnected'});
        }
      });

      List<BluetoothService> services = await device.discoverServices();
      for (var service in services) {
        final serviceUuid = service.uuid.toString().toLowerCase();

        if (serviceUuid.contains('abcd')) {
          for (var c in service.characteristics) {
            final charUuid = c.uuid.toString().toLowerCase();

            if (charUuid.contains('1234')) {
              _characteristic = c;
              await c.setNotifyValue(true);
              c.onValueReceived.listen(_parseData);
              debugPrint('=== SUBSCRIBED TO DATA CHARACTERISTIC ===');
            }
            if (charUuid.contains('5678')) {
              _commandCharacteristic = c;
              debugPrint('=== FOUND COMMAND CHARACTERISTIC ===');
            }
          }
        }
      }

      await Future.delayed(const Duration(seconds: 1));
      await requestStoredReadings();

    } catch (e) {
      debugPrint('BLE Connect Error: $e');
      isConnected = false;
      _readingController.add({'type': 'disconnected'});
    }
  }

  Future<void> requestStoredReadings() async {
    if (_commandCharacteristic == null) return;
    try {
      await _commandCharacteristic!.write(utf8.encode('REQUEST_STORED'));
      debugPrint('=== SENT REQUEST_STORED ===');
    } catch (e) {
      debugPrint('Failed to send REQUEST_STORED: $e');
    }
  }

  void _parseData(List<int> data) {
    try {
      final text = utf8.decode(data);
      debugPrint('BLE Received: $text');

      if (text.startsWith('CONNECTED|')) return;

      if (text.startsWith('S')) {
        final colonIndex = text.indexOf(':');
        if (colonIndex == -1) return;
        final readingsStr = text.substring(colonIndex + 1);
        final parts = readingsStr.split(',');
        storedReadings.clear();

        for (var p in parts) {
          final alert = p.endsWith('!');
          final clean = p.replaceAll('!', '');
          final values = clean.split('/');

          if (values.length == 2) {
            final t = double.tryParse(values[0]);
            final h = double.tryParse(values[1]);
            if (t != null && h != null) {
              storedReadings.add({
                'temperature': t,
                'humidity': h,
                'is_alert': alert,
              });
            }
          }
        }
        _readingController.add({'type': 'stored', 'readings': storedReadings});
        return;
      }

      if (text.startsWith('T:')) {
        final tempMatch = RegExp(r'T:([\d.]+)').firstMatch(text);
        final humMatch = RegExp(r'H:([\d.]+)').firstMatch(text);

        if (tempMatch != null) temperature = double.tryParse(tempMatch.group(1)!);
        if (humMatch != null) humidity = double.tryParse(humMatch.group(1)!);

        isAlert = text.contains('!');
        latestReading = text;

        _readingController.add({
          'type': 'live',
          'temperature': temperature,
          'humidity': humidity,
          'is_alert': isAlert,
        });
      }
    } catch (e) {
      debugPrint('Error parsing BLE data: $e');
    }
  }

  Future<void> disconnect() async {
    await _scanSub?.cancel();
    await _connectionSub?.cancel();
    await _device?.disconnect();
    isConnected = false;
  }
}
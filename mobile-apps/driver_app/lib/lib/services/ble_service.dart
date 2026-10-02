import 'dart:async';
import 'dart:convert';
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

  final _readingController = StreamController<Map<String, dynamic>>.broadcast();
  Stream<Map<String, dynamic>> get readingStream => _readingController.stream;

  Future<void> scanAndConnect() async {
    print('=== BLE SCAN STARTED ===');
    await FlutterBluePlus.startScan(timeout: Duration(seconds: 15));

    FlutterBluePlus.scanResults.listen((results) async {
      for (ScanResult r in results) {
        final name = r.device.platformName;
        final id = r.device.remoteId.toString();
        print('Device: "$name" | ID: $id');

        if (name == 'Kimchi_Monitor' || id == '30:76:F5:93:83:F2') {
          print('=== FOUND ESP32! CONNECTING... ===');
          FlutterBluePlus.stopScan();
          await _connect(r.device);
          return;
        }
      }
    });
  }

  Future<void> _connect(BluetoothDevice device) async {
    _device = device;
    await device.connect();
    isConnected = true;

    List<BluetoothService> services = await device.discoverServices();
    for (var service in services) {
      if (service.uuid.toString().toUpperCase().contains('ABCD')) {
        for (var c in service.characteristics) {
          if (c.uuid.toString().toUpperCase().contains('1234')) {
            _characteristic = c;
            await c.setNotifyValue(true);
            c.onValueReceived.listen(_parseData);
            print('=== SUBSCRIBED TO 1234 ===');
          }
          if (c.uuid.toString().toUpperCase().contains('5678')) {
            _commandCharacteristic = c;
            print('=== FOUND COMMAND CHARACTERISTIC 5678 ===');
          }
        }
      }
    }

    // Wait for connection to stabilize, then request stored readings
    await Future.delayed(Duration(seconds: 1));
    await requestStoredReadings();
  }

  Future<void> requestStoredReadings() async {
    if (_commandCharacteristic == null) {
      print('Command characteristic not found');
      return;
    }
    await _commandCharacteristic!.write(utf8.encode('REQUEST_STORED'));
    print('=== SENT REQUEST_STORED ===');
  }

  void _parseData(List<int> data) {
    final text = utf8.decode(data);
    print('BLE Received: $text');

    if (text.startsWith('CONNECTED|')) {
      return;
    }

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
          storedReadings.add({
            'temperature': double.tryParse(values[0]),
            'humidity': double.tryParse(values[1]),
            'is_alert': alert,
          });
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
  }

  Future<void> disconnect() async {
    await _device?.disconnect();
    isConnected = false;
  }
}
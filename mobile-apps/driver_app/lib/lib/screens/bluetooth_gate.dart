import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_blue_plus/flutter_blue_plus.dart';

class BluetoothGate extends StatefulWidget {
  final Widget child;
  BluetoothGate({required this.child});

  @override
  _BluetoothGateState createState() => _BluetoothGateState();
}

class _BluetoothGateState extends State<BluetoothGate> {
  bool _checking = true;
  bool _btOn = false;
  Timer? _closeTimer;
  int _secondsLeft = 5;

  @override
  void initState() {
    super.initState();
    _checkBluetooth();
  }

  Future<void> _checkBluetooth() async {
    try {
      final state = await FlutterBluePlus.adapterState.first;
      final isOn = state == BluetoothAdapterState.on;

      if (!mounted) return;
      setState(() {
        _checking = false;
        _btOn = isOn;
      });

      if (!isOn) {
        _startCountdown();
      }
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _checking = false;
        _btOn = false;
      });
      _startCountdown();
    }
  }

  void _startCountdown() {
    _secondsLeft = 5;
    _closeTimer?.cancel();
    _closeTimer = Timer.periodic(Duration(seconds: 1), (timer) {
      if (!mounted) {
        timer.cancel();
        return;
      }
      setState(() {
        _secondsLeft--;
      });
      if (_secondsLeft <= 0) {
        timer.cancel();
        SystemNavigator.pop();
      }
    });
  }

  Future<void> _turnOnBluetooth() async {
    _closeTimer?.cancel();
    try {
      await FlutterBluePlus.turnOn();
    } catch (_) {}
    await Future.delayed(Duration(seconds: 2));
    if (mounted) {
      setState(() {
        _checking = true;
      });
      _checkBluetooth();
    }
  }

  @override
  void dispose() {
    _closeTimer?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (_checking) {
      return Scaffold(
        body: Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              CircularProgressIndicator(),
              SizedBox(height: 15),
              Text('Checking Bluetooth...'),
            ],
          ),
        ),
      );
    }

    if (!_btOn) {
      return Scaffold(
        body: Center(
          child: Padding(
            padding: EdgeInsets.all(20),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(Icons.bluetooth_disabled, size: 64, color: Colors.grey),
                SizedBox(height: 20),
                Text(
                  'Bluetooth Required',
                  style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold),
                ),
                SizedBox(height: 10),
                Text(
                  'KimFresh Driver needs Bluetooth to monitor temperature.',
                  textAlign: TextAlign.center,
                  style: TextStyle(fontSize: 16, color: Colors.grey[700]),
                ),
                SizedBox(height: 30),
                Text(
                  'Closing in $_secondsLeft seconds...',
                  style: TextStyle(fontSize: 18, color: Colors.red),
                ),
                SizedBox(height: 20),
                ElevatedButton(
                  onPressed: _turnOnBluetooth,
                  child: Text('Turn On Bluetooth'),
                ),
              ],
            ),
          ),
        ),
      );
    }

    return widget.child;
  }
}
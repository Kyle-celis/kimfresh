import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_blue_plus/flutter_blue_plus.dart';
import '../theme/app_theme.dart';

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
      if (!isOn) _startCountdown();
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
    _closeTimer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (!mounted) {
        timer.cancel();
        return;
      }
      setState(() => _secondsLeft--);
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
    await Future.delayed(const Duration(seconds: 2));
    if (mounted) {
      setState(() => _checking = true);
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
        backgroundColor: AppColors.canvas,
        body: Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: const [
              CircularProgressIndicator(color: AppColors.burgundy),
              SizedBox(height: 16),
              Text(
                'Checking Bluetooth…',
                style: TextStyle(color: AppColors.textSecondary),
              ),
            ],
          ),
        ),
      );
    }

    if (!_btOn) {
      return Scaffold(
        backgroundColor: AppColors.canvas,
        body: Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Container(
              padding: const EdgeInsets.all(28),
              decoration: BoxDecoration(
                color: AppColors.surface,
                borderRadius: BorderRadius.circular(20),
                border: Border.all(color: AppColors.border),
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Container(
                    width: 76,
                    height: 76,
                    decoration: BoxDecoration(
                      color: AppColors.burgundy.withOpacity(0.08),
                      shape: BoxShape.circle,
                    ),
                    alignment: Alignment.center,
                    child: const Icon(Icons.bluetooth_disabled_rounded,
                        size: 38, color: AppColors.burgundy),
                  ),
                  const SizedBox(height: 22),
                  const Text(
                    'Bluetooth required',
                    style: TextStyle(
                      fontSize: 20,
                      fontWeight: FontWeight.w700,
                      color: AppColors.textPrimary,
                    ),
                  ),
                  const SizedBox(height: 8),
                  const Text(
                    'KimFresh Driver needs Bluetooth to monitor delivery temperature.',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      fontSize: 14,
                      height: 1.45,
                      color: AppColors.textSecondary,
                    ),
                  ),
                  const SizedBox(height: 24),
                  Container(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 14, vertical: 8),
                    decoration: BoxDecoration(
                      color: AppColors.danger.withOpacity(0.08),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Text(
                      'Closing in $_secondsLeft s',
                      style: const TextStyle(
                        color: AppColors.danger,
                        fontWeight: FontWeight.w600,
                        fontSize: 13,
                      ),
                    ),
                  ),
                  const SizedBox(height: 20),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton.icon(
                      onPressed: _turnOnBluetooth,
                      icon: const Icon(Icons.bluetooth_rounded),
                      label: const Text('Turn on Bluetooth'),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      );
    }

    return widget.child;
  }
}
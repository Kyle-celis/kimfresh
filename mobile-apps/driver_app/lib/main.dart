import 'package:flutter/material.dart';
import 'package:flutter_foreground_task/flutter_foreground_task.dart';
import 'screens/bluetooth_gate.dart';
import 'services/location_gate.dart';
import 'screens/login_screen.dart';
import 'theme/app_theme.dart';
import 'services/foreground_service.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();

  ForegroundService.init();
  FlutterForegroundTask.initCommunicationPort();

  runApp(const KimFreshDriverApp());
}

class KimFreshDriverApp extends StatelessWidget {
  const KimFreshDriverApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'KimFresh Driver',
      theme: AppTheme.build(),
      // LocationGate -> BluetoothGate -> app
      home: LocationGate(
        child: BluetoothGate(child: LoginScreen()),
      ),
    );
  }
}
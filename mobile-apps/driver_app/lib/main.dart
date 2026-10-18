import 'package:flutter/material.dart';
import 'package:flutter_foreground_task/flutter_foreground_task.dart';
import 'screens/bluetooth_gate.dart';
import 'screens/login_screen.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  FlutterForegroundTask.initCommunicationPort();
  runApp(KimFreshDriverApp());
}

class KimFreshDriverApp extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'KimFresh Driver',
      theme: ThemeData(primarySwatch: Colors.blue),
      home: BluetoothGate(child: LoginScreen()),
    );
  }
}
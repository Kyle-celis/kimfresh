import 'package:flutter/material.dart';
import 'screens/login_screen.dart';

void main() {
  runApp(KimFreshApp());
}

class KimFreshApp extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'KimFresh Retailer',
      theme: ThemeData(primarySwatch: Colors.red),
      home: LoginScreen(),
    );
  }
}
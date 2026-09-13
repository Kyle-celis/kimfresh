import 'package:flutter/material.dart';
import 'screens/login_screen.dart';

void main() {
  runApp(KimFreshDriverApp());
}

class KimFreshDriverApp extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'KimFresh Driver',
      theme: ThemeData(primarySwatch: Colors.blue),
      home: LoginScreen(),
    );
  }
}
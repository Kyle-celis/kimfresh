import 'package:flutter/material.dart';
import 'package:flutter_foreground_task/flutter_foreground_task.dart';
import 'screens/role_selection_screen.dart';
import 'theme/app_theme.dart';
import 'services/foreground_service.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  ForegroundService.init();
  FlutterForegroundTask.initCommunicationPort();
  runApp(const KimFreshApp());
}

class KimFreshApp extends StatelessWidget {
  const KimFreshApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'KimFresh',
      theme: AppTheme.build(),
      home: RoleSelectionScreen(), // REMOVED const here
      debugShowCheckedModeBanner: false,
    );
  }
}
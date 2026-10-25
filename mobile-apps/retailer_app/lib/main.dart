import 'package:flutter/material.dart';
import 'package:permission_handler/permission_handler.dart';
import 'theme/colors.dart';
import 'screens/login_screen.dart';

void main() {
  runApp(KimFreshApp());
}

class KimFreshApp extends StatefulWidget {
  @override
  State<KimFreshApp> createState() => _KimFreshAppState();
}

class _KimFreshAppState extends State<KimFreshApp> {
  @override
  void initState() {
    super.initState();
    // Request permission AFTER the UI is built so the dialog can show properly
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _requestLocationPermission();
    });
  }

  Future<void> _requestLocationPermission() async {
    PermissionStatus status = await Permission.location.status;

    if (status.isGranted) {
      return; // Already granted
    }

    if (status.isPermanentlyDenied) {
      _showSettingsDialog();
      return;
    }

    // Show the native Android/iOS popup
    status = await Permission.location.request();

    if (status.isPermanentlyDenied) {
      _showSettingsDialog();
    }
  }

  void _showSettingsDialog() {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Location Required'),
        content: const Text(
          'This app needs location access to show the delivery route. '
              'Please enable it in your device settings.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Cancel'),
          ),
          TextButton(
            onPressed: () {
              openAppSettings(); // Opens the OS settings page
              Navigator.pop(ctx);
            },
            child: const Text('Open Settings'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final scheme = ColorScheme.fromSeed(
      seedColor: AppColors.brand,
      primary: AppColors.brand,
      surface: AppColors.surface,
      error: AppColors.error,
    );

    return MaterialApp(
      title: 'KimFresh Retailer',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        useMaterial3: true,
        colorScheme: scheme,
        scaffoldBackgroundColor: AppColors.background,
        appBarTheme: const AppBarTheme(
          backgroundColor: AppColors.background,
          foregroundColor: AppColors.text,
          elevation: 0,
          centerTitle: false,
          titleTextStyle: TextStyle(
            fontSize: 17,
            fontWeight: FontWeight.w600,
            color: AppColors.text,
          ),
        ),
        snackBarTheme: SnackBarThemeData(
          behavior: SnackBarBehavior.floating,
          backgroundColor: AppColors.text,
          contentTextStyle: const TextStyle(color: Colors.white),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(AppColors.rMd),
          ),
        ),
        elevatedButtonTheme: ElevatedButtonThemeData(
          style: ElevatedButton.styleFrom(
            backgroundColor: AppColors.brand,
            foregroundColor: Colors.white,
            elevation: 0,
            textStyle: const TextStyle(
              fontWeight: FontWeight.w600,
              fontSize: 14,
            ),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(AppColors.rMd),
            ),
          ),
        ),
        outlinedButtonTheme: OutlinedButtonThemeData(
          style: OutlinedButton.styleFrom(
            foregroundColor: AppColors.brand,
            side: const BorderSide(color: AppColors.brand, width: 1.2),
            textStyle: const TextStyle(
              fontWeight: FontWeight.w600,
              fontSize: 14,
            ),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(AppColors.rMd),
            ),
          ),
        ),
        inputDecorationTheme: InputDecorationTheme(
          filled: true,
          fillColor: AppColors.background,
          contentPadding: const EdgeInsets.symmetric(
            vertical: 16,
            horizontal: 16,
          ),
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(AppColors.rMd),
            borderSide: const BorderSide(color: AppColors.border),
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(AppColors.rMd),
            borderSide: const BorderSide(color: AppColors.border),
          ),
          focusedBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(AppColors.rMd),
            borderSide: const BorderSide(
              color: AppColors.brand,
              width: 1.5,
            ),
          ),
        ),
      ),
      home: LoginScreen(),
    );
  }
}